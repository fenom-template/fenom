<?php
/*
 * This file is part of Cytro.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
namespace Cytro;
use Cytro\Tokenizer;
use Cytro\Template;
use Cytro\Scope;

/**
 * Compilers collection
 * @package Cytro
 */
class Compiler {
    /**
     * Tag {include ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @throws ImproperUseException
     * @return string
     */
    public static function tagInclude(Tokenizer $tokens, Template $tpl) {
        $cname = $tpl->parsePlainArg($tokens, $name);
        $p = $tpl->parseParams($tokens);
        if($p) { // if we have additionally variables
            if($name && ($tpl->getStorage()->getOptions() & \Cytro::FORCE_INCLUDE)) { // if FORCE_INCLUDE enabled and template name known
                $inc = $tpl->getStorage()->compile($name, false);
                $tpl->addDepend($inc);
                return '$_tpl = (array)$tpl; $tpl->exchangeArray('.self::toArray($p).'+$_tpl); ?>'.$inc->_body.'<?php $tpl->exchangeArray($_tpl); unset($_tpl);';
            } else {
                return '$tpl->getStorage()->getTemplate('.$cname.')->display('.self::toArray($p).'+(array)$tpl);';
            }
        } else {
            if($name && ($tpl->getStorage()->getOptions() & \Cytro::FORCE_INCLUDE)) { // if FORCE_INCLUDE enabled and template name known
                $inc = $tpl->getStorage()->compile($name, false);
                $tpl->addDepend($inc);
                return '$_tpl = (array)$tpl; ?>'.$inc->_body.'<?php $tpl->exchangeArray($_tpl); unset($_tpl);';
            } else {
                return '$tpl->getStorage()->getTemplate('.$cname.')->display((array)$tpl);';
            }
        }
    }


    /**
     * Open tag {if ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function ifOpen(Tokenizer $tokens, Scope $scope) {
        $scope["else"] = false;
        return 'if('.$scope->tpl->parseExp($tokens, true).') {';
    }

    /**
     * Tag {elseif ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @throws ImproperUseException
     * @return string
     */
    public static function tagElseIf(Tokenizer $tokens, Scope $scope) {
        if($scope["else"]) {
            throw new ImproperUseException('Incorrect use of the tag {elseif}');
        }
        return '} elseif('.$scope->tpl->parseExp($tokens, true).') {';
    }

    /**
     * Tag {else}
     *
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @internal param $
     * @param Scope              $scope
     * @return string
     */
    public static function tagElse(Tokenizer $tokens, Scope $scope) {
        $scope["else"] = true;
        return '} else {';
    }

    /**
     * Open tag {foreach ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope     $scope
     * @throws UnexpectedTokenException
     * @throws ImproperUseException
     * @return string
     */
    public static function foreachOpen(Tokenizer $tokens, Scope $scope) {
        $p = array("index" => false, "first" => false, "last" => false);
        $key = null;
        $before = $body = array();
        if($tokens->is(T_VARIABLE)) {
            $from = $scope->tpl->parseVariable($tokens, Template::DENY_MODS);
            $prepend = "";
        } elseif($tokens->is('[')) {
            $from = $scope->tpl->parseArray($tokens);
            $uid = '$v'.$scope->tpl->i++;
            $prepend = $uid.' = '.$from.';';
            $from = $uid;
        } else {
            throw new UnexpectedTokenException($tokens, null, "tag {foreach}");
        }
        $tokens->get(T_AS);
        $tokens->next();
        $value = $scope->tpl->parseVariable($tokens, Template::DENY_MODS | Template::DENY_ARRAY);
        if($tokens->is(T_DOUBLE_ARROW)) {
            $tokens->next();
            $key = $value;
            $value = $scope->tpl->parseVariable($tokens, Template::DENY_MODS | Template::DENY_ARRAY);
        }

        $scope["after"] = array();
        $scope["else"] = false;

        while($token = $tokens->key()) {
            $param = $tokens->get(T_STRING);
            if(!isset($p[ $param ])) {
                throw new ImproperUseException("Unknown parameter '$param' in {foreach}");
            }
            $tokens->getNext("=");
            $tokens->next();
            $p[ $param ] = $scope->tpl->parseVariable($tokens, Template::DENY_MODS | Template::DENY_ARRAY);
        }

        if($p["index"]) {
            $before[] = $p["index"].' = 0';
            $scope["after"][] = $p["index"].'++';
        }
        if($p["first"]) {
            $before[] = $p["first"].' = true';
            $scope["after"][] = $p["first"] .' && ('. $p["first"].' = false )';
        }
        if($p["last"]) {
            $before[] = $p["last"].' = false';
            $scope["uid"] = "v".$scope->tpl->i++;
            $before[] = '$'.$scope["uid"]." = count($from)";
            $body[] = 'if(!--$'.$scope["uid"].') '.$p["last"].' = true';
        }

        $before = $before ? implode("; ", $before).";" : "";
        $body = $body ? implode("; ", $body).";" : "";
        $scope["after"] = $scope["after"] ? implode("; ", $scope["after"]).";" : "";
        if($key) {
            return "$prepend if($from) { $before foreach($from as $key => $value) { $body";
        } else {
            return "$prepend if($from) { $before foreach($from as $value) { $body";
        }
    }

    /**
     * Tag {foreachelse}
     *
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function foreachElse($tokens, Scope $scope) {
        $scope["no-break"] = $scope["no-continue"] = $scope["else"] = true;
        return " {$scope['after']} } } else {";
    }

    /**
     * Close tag {/foreach}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function foreachClose($tokens, Scope $scope) {
        if($scope["else"]) {
            return '}';
        } else {
            return " {$scope['after']} } }";
        }
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     * @throws ImproperUseException
     */
    public static function forOpen(Tokenizer $tokens, Scope $scope) {
        $p = array("index" => false, "first" => false, "last" => false, "step" => 1, "to" => false, "max" => false, "min" => false);
        $scope["after"] = $before = $body = array();
        $i = array('', '');
        $c = "";
        $var = $scope->tpl->parseVariable($tokens, Template::DENY_MODS);
        $tokens->get("=");
        $tokens->next();
        $val = $scope->tpl->parseExp($tokens, true);
        $p = $scope->tpl->parseParams($tokens, $p);

        if(is_numeric($p["step"])) {
            if($p["step"] > 0) {
                $condition = "$var <= {$p['to']}";
                if($p["last"]) $c = "($var + {$p['step']}) > {$p['to']}";
            } elseif($p["step"] < 0) {
                $condition = "$var >= {$p['to']}";
                if($p["last"]) $c = "($var + {$p['step']}) < {$p['to']}";
            } else {
                throw new ImproperUseException("Invalid step value if {for}");
            }
        } else {
            $condition = "({$p['step']} > 0 && $var <= {$p['to']} || {$p['step']} < 0 && $var >= {$p['to']})";
            if($p["last"]) $c = "({$p['step']} > 0 && ($var + {$p['step']}) <= {$p['to']} || {$p['step']} < 0 && ($var + {$p['step']}) >= {$p['to']})";
        }

        if($p["first"]) {
            $before[] = $p["first"].' = true';
            $scope["after"][] = $p["first"] .' && ('. $p["first"].' = false )';
        }
        if($p["last"]) {
            $before[] = $p["last"].' = false';
            $body[] = "if($c) {$p['last']} = true";
        }

        if($p["index"]) {
            $i[0] .= $p["index"].' = 0,';
            $i[1] .= $p["index"].'++,';
        }

        $scope["else"] = false;
        $scope["else_cond"] = "$var==$val";
        $before = $before ? implode("; ", $before).";" : "";
        $body = $body ? implode("; ", $body).";" : "";
        $scope["after"] = $scope["after"] ? implode("; ", $scope["after"]).";" : "";

        return "$before for({$i[0]} $var=$val; $condition;{$i[1]} $var+={$p['step']}) { $body";
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function forElse(Tokenizer $tokens, Scope $scope) {
        $scope["no-break"] = $scope["no-continue"] = true;
        $scope["else"] = true;
        return " } if({$scope['else_cond']}) {";
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function forClose($tokens, Scope $scope) {
        if($scope["else"]) {
            return '}';
        } else {
            return " {$scope['after']} }";
        }
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function whileOpen(Tokenizer $tokens, Scope $scope) {
        return 'while('.$scope->tpl->parseExp($tokens, true).') {';
    }

    /**
     * Open tag {switch}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function switchOpen(Tokenizer $tokens, Scope $scope) {
        $scope["no-break"] = $scope["no-continue"] = true;
        $scope["switch"] = 'switch('.$scope->tpl->parseExp($tokens, true).') {';
        // lazy init
        return '';
    }

    /**
     * Tag {case ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function tagCase(Tokenizer $tokens, Scope $scope) {
        $code = 'case '.$scope->tpl->parseExp($tokens, true).': ';
        if($scope["switch"]) {
            unset($scope["no-break"], $scope["no-continue"]);
            $code = $scope["switch"]."\n".$code;
            $scope["switch"] = "";
        }
        return $code;
    }

    /**
     * Tag {continue}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @throws ImproperUseException
     * @return string
     */
    public static function tagContinue($tokens, Scope $scope) {
        if(empty($scope["no-continue"])) {
            return 'continue;';
        } else {
            throw new ImproperUseException("Improper usage of the tag {continue}");
        }
    }

    /**
     * Tag {default}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function tagDefault($tokens, Scope $scope) {
        $code = 'default: ';
        if($scope["switch"]) {
            unset($scope["no-break"], $scope["no-continue"]);
            $code = $scope["switch"]."\n".$code;
            $scope["switch"] = "";
        }
        return $code;
    }

    /**
     * Tag {break}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope     $scope
     * @throws ImproperUseException
     * @return string
     */
    public static function tagBreak($tokens, Scope $scope) {
        if(empty($scope["no-break"])) {
            return 'break;';
        } else {
            throw new ImproperUseException("Improper usage of the tag {break}");
        }
    }

    /**
     * Dispatch {extends} tag
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @throws ImproperUseException
     * @return string
     */
    public static function tagExtends(Tokenizer $tokens, Template $tpl) {
        if(!empty($tpl->_extends)) {
            throw new ImproperUseException("Only one {extends} allowed");
        }
        $tpl_name = $tpl->parsePlainArg($tokens, $name);
        if(empty($tpl->_extended)) {
            $tpl->addPostCompile(__CLASS__."::extendBody");
        }
        /*if($tpl->getOptions() & Template::EXTENDED) {
            $tpl->_compatible = true;
        } else {
            $tpl->_compatible = false;
        }*/
        if($name) { // static extends
            $tpl->_extends = $tpl->getStorage()->getRawTemplate()->load($name, false);
            $tpl->_compatible = &$tpl->_extends->_compatible;
            $tpl->addDepend($tpl->_extends);
            return "";
        } else { // dynamic extends
            $tpl->_extends = $tpl_name;
            return '$parent = $tpl->getStorage()->getTemplate('.$tpl_name.', \Cytro\Template::EXTENDED);';
        }
    }

    /**
     * Post compile action for {extends ...} tag
     * @param $body
     * @param Template $tpl
     */
    public static function extendBody(&$body, $tpl) {
        $t = $tpl;
        while(isset($t->_extends)) {
            $t = $t->_extends;
            if(is_object($t)) {
                $t->_extended = true;
                $t->_compatible = &$tpl->_compatible;
                $t->blocks = &$tpl->blocks;
                $t->compile();
                if(!isset($t->_extends)) { // last item => parent
                    if(empty($tpl->_compatible)) {
                        $body = $t->getBody();
                    } else {
                        $body = '<?php ob_start(); ?>'.$body.'<?php ob_end_clean(); ?>'.$t->getBody();
                    }
                    return;
                } else {
                    $body .= $t->getBody();
                }
            } else {
                $body = '<?php ob_start(); ?>'.$body.'<?php ob_end_clean(); $parent->b = &$tpl->b; $parent->display((array)$tpl); unset($tpl->b, $parent->b); ?>';
                return;
            }
        }
    }

    /**
     * Tag {use ...}
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @throws ImproperUseException
     * @return string
     */
    public static function tagUse(Tokenizer $tokens, Template $tpl) {
        $tpl->parsePlainArg($tokens, $name);
        if($name) {
            $donor = $tpl->getStorage()->getRawTemplate()->load($name, false);
            $donor->_extended = true;
            $tpl->_compatible = &$donor->_compatible;
            $donor->compile();
            if(empty($tpl->_compatible)) {
                $tpl->blocks += $donor->blocks;
            }
            return '?>'.$donor->getBody().'<?php ';
        } else {
            throw new ImproperUseException('template name must be given explicitly');
            //return '';
            //return '$donor = $tpl->getStorage()->getTemplate('.$cname.'); ';

            //$tpl->_compatible = true;
            //$tpl->_ = false;
        }
    }

    /**
     * Tag {block ...}
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     * @throws ImproperUseException
     */
    public static function tagBlockOpen(Tokenizer $tokens, Scope $scope) {
        $p = $scope->tpl->parsePlainArg($tokens, $name);
        $scope["name"]  = $name;
        $scope["cname"] = $p;
    }

    /**
     * Close tag {/block}
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function tagBlockClose($tokens, Scope $scope) {
        $tpl = $scope->tpl;
        if(isset($tpl->_extends)) { // is child
            if($scope["name"]) { // is scalar name
                if(!isset($tpl->blocks[ $scope["name"] ])) { // is block still doesn't preset
                    if($tpl->_compatible) { // is compatible mode
                        $scope->replaceContent(
                            '<?php if(empty($tpl->blocks['.$scope["cname"].'])) { '.
                                '$tpl->b['.$scope["cname"].'] = function($tpl) { ?>'.PHP_EOL.
                                    $scope->getContent().
                                "<?php };".
                            "} ?>".PHP_EOL
                        );
                    } else {
                        $tpl->blocks[ $scope["name"] ] = $scope->getContent();
                        $scope->replaceContent(
                            '<?php $tpl->b['.$scope["cname"].'] = function($tpl) { ?>'.PHP_EOL.
                                $scope->getContent().
                            "<?php }; ?>".PHP_EOL
                        );
                    }
                }
            } else { // dynamic name
                $tpl->_compatible = true; // enable compatible mode
                $scope->replaceContent(
                    '<?php if(empty($tpl->b['.$scope["cname"].'])) { '.
                        '$tpl->b['.$scope["cname"].'] = function($tpl) { ?>'.PHP_EOL.
                            $scope->getContent().
                        "<?php };".
                    "} ?>".PHP_EOL
                );
            }
        } else {     // is parent
            if(isset($tpl->blocks[ $scope["name"] ])) { // has block
                if($tpl->_compatible) { // compatible mode enabled
                    $scope->replaceContent(
                        '<?php if(isset($tpl->b['.$scope["cname"].'])) { echo $tpl->b['.$scope["cname"].']->__invoke($tpl); } else {?>'.PHP_EOL.
                            $tpl->blocks[ $scope["name"] ].
                        '<?php } ?>'.PHP_EOL
                    );

                } else {
                    $scope->replaceContent($tpl->blocks[ $scope["name"] ]);
                }
            } elseif(isset($tpl->_extended) && $tpl->_compatible || empty($tpl->_extended)) {
                $scope->replaceContent(
                    '<?php if(isset($tpl->b['.$scope["cname"].'])) { echo $tpl->b['.$scope["cname"].']->__invoke($tpl); } else {?>'.PHP_EOL.
                        $scope->getContent().
                    '<?php } ?>'.PHP_EOL
                );
            }
        }
        return '';
    }

    public static function tagParent($tokens, Scope $scope) {
        if(empty($scope->tpl->_extends)) {
            throw new ImproperUseException("Tag {parent} may be declared in childs");
        }
    }

    /**
     * Standard close tag {/...}
     *
     * @static
     * @return string
     */
    public static function stdClose() {
        return '}';
    }

    /**
     * Standard function parser
     *
     * @static
     * @param                    $function
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @return string
     */
    public static function stdFuncParser($function, Tokenizer $tokens, Template $tpl) {
        return "echo $function(".self::toArray($tpl->parseParams($tokens)).', $tpl);';
    }

    /**
     * Smart function parser
     *
     * @static
     * @param                    $function
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @return string
     */
    public static function smartFuncParser($function, Tokenizer $tokens, Template $tpl) {
        if(strpos($function, "::")) {
            list($class, $method) = explode("::", $function, 2);
            $ref = new \ReflectionMethod($class, $method);
        } else {
            $ref = new \ReflectionFunction($function);
        }
        $args = array();
        $params = $tpl->parseParams($tokens);
        foreach($ref->getParameters() as $param) {
            if(isset($params[ $param->getName() ])) {
                $args[] = $params[ $param->getName() ];
            } elseif(isset($params[ $param->getPosition() ])) {
                $args[] = $params[ $param->getPosition() ];
            } elseif($param->isOptional()) {
                $args[] = $param->getDefaultValue();
            }
        }
        return "echo $function(".implode(", ", $args).');';
    }

    /**
     * Standard function open tag parser
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function stdFuncOpen(Tokenizer $tokens, Scope $scope) {
        $scope["params"] = self::toArray($scope->tpl->parseParams($tokens));
        return 'ob_start();';
    }

    /**
     * Standard function close tag parser
     *
     * @static
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @return string
     */
    public static function stdFuncClose($tokens, Scope $scope) {
        return "echo ".$scope["function"].'('.$scope["params"].', ob_get_clean(), $tpl);';
    }

    /**
     * Convert array of code to string array
     * @param $params
     * @return string
     */
    public static function toArray($params) {
        $_code = array();
        foreach($params as $k => $v) {
            $_code[] = '"'.$k.'" => '.$v;
        }

        return 'array('.implode(",", $_code).')';
    }

    public static function varOpen(Tokenizer $tokens, Scope $scope) {
        $scope->is_closed = true;
        return self::setVar($tokens, $scope->tpl).';';
    }

    public static function varClose() {
        return '';
    }

    /**
     * Tag {var ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @return string
     */
    //public static function assign(Tokenizer $tokens, Template $tpl) {
      //  return self::setVar($tokens, $tpl).';';
    //}

    /**
     * Set variable expression
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @param bool $allow_array
     * @return string
     */
    public static function setVar(Tokenizer $tokens, Template $tpl, $allow_array = true) {
        $var = $tpl->parseVariable($tokens, $tpl::DENY_MODS);

        $tokens->get('=');
        $tokens->next();
        if($tokens->is("[") && $allow_array) {
            return $var.'='.$tpl->parseArray($tokens);
        } else {
            return $var.'='.$tpl->parseExp($tokens, true);
        }
    }

    public static function filterOpen(Tokenizer $tokens, Scope $scope) {
        $scope["filter"] = $scope->tpl->parseModifier($tokens, "ob_get_clean()");
        return "ob_start();";
    }

    public static function filterClose($tokens, Scope $scope) {
        return "echo ".$scope["filter"].";";
    }

    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function captureOpen(Tokenizer $tokens, Scope $scope) {
        if($tokens->is("|")) {
            $scope["value"] = $scope->tpl->parseModifier($tokens, "ob_get_clean()");
        } else {
            $scope["value"] = "ob_get_clean()";
        }

        $scope["var"] = $scope->tpl->parseVariable($tokens, Template::DENY_MODS);

        return "ob_start();";
    }

    public static function captureClose($tokens, Scope $scope) {
        return $scope["var"]." = ".$scope["value"].";";
    }

    /**
     * Tag {cycle}
     *
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     * @throws ImproperUseException
     */
    public static function tagCycle(Tokenizer $tokens, Template $tpl) {
        $exp = $tpl->parseExp($tokens, true);
        if($tokens->valid()) {
            $p = $tpl->parseParams($tokens);
            if(empty($p["index"])) {
                throw new ImproperUseException("Cycle may contain only index attribute");
            } else {
                return __CLASS__.'::cycle((array)'.$exp.', '.$p["index"].');';
            }
        } else {
            $var = $tpl->tmpVar();
            return "is_array($exp) ? ".__CLASS__.'::cycle('.$exp.", isset($var) ? $var++ : ($var = 0) ) : $exp";
        }
    }

    public static function cycle($vals, $index) {
        return $vals[$index % count($vals)];
    }

    /**
     * Import macros from templates
     *
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @throws UnexpectedTokenException
     * @throws ImproperUseException
     * @return string
     */
    public static function tagImport(Tokenizer $tokens, Template $tpl) {
        $import = array();
        if($tokens->is('[')) {
            $tokens->next();
            while($tokens->valid()) {
                if($tokens->is(Tokenizer::MACRO_STRING)) {
                    $import[ $tokens->current() ] = true;
                    $tokens->next();
                } elseif($tokens->is(']')) {
                    $tokens->next();
                    break;
                } elseif($tokens->is(',')) {
                    $tokens->next();
                } else {
                    break;
                }
            }
            if($tokens->current() != "from") {
                throw new UnexpectedTokenException($tokens);
            }
            $tokens->next();
        }

        $tpl->parsePlainArg($tokens, $name);
        if(!$name) {
            throw new ImproperUseException("Invalid usage tag {import}");
        }
        if($tokens->is(T_AS)) {
            $alias = $tokens->next()->get(Tokenizer::MACRO_STRING);
            if($alias === "macro") {
                $alias = "";
            }
            $tokens->next();
        } else {
            $alias = "";
        }
        $donor = $tpl->getStorage()->getRawTemplate()->load($name, true);
        if($donor->macros) {
            foreach($donor->macros as $name => $macro) {
                if($p = strpos($name, ".")) {
                    $name = substr($name, $p);
                }
                if($import && !isset($import[$name])) {
                    continue;
                }
                if($alias) {
                    $name = $alias.'.'.$name;
                }

                $tpl->macros[$name] = $macro;
            }
            $tpl->addDepend($donor);
        }
        return '';

    }

    /**
     * Define macro
     *
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @throws ImproperUseException
     */
    public static function macroOpen(Tokenizer $tokens, Scope $scope) {
        $scope["name"] = $tokens->get(Tokenizer::MACRO_STRING);
        $scope["args"] = array();
        $scope["defaults"] = array();
        if(!$tokens->valid()) {
            return;
        }
        $tokens->next()->need('(')->next();
        if($tokens->is(')')) {
            return;
        }
        while($tokens->is(Tokenizer::MACRO_STRING)) {
            $scope["args"][] = $param = $tokens->getAndNext();
            if($tokens->is('=')) {
                $tokens->next();
                if($tokens->is(T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER) || $tokens->isSpecialVal()) {
                    $scope["defaults"][ $param ] = $tokens->getAndNext();
                } else {
                    throw new ImproperUseException("Macro parameters may have only scalar defaults");
                }
            }
            $tokens->skipIf(',');
        }
        $tokens->skipIf(')');

        return;
    }

    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     */
    public static function macroClose(Tokenizer $tokens, Scope $scope) {
        $scope->tpl->macros[ $scope["name"] ] = array(
            "body" => $content = $scope->getContent(),
            "args" => $scope["args"],
            "defaults" => $scope["defaults"]
        );
        $scope->tpl->_body = substr($scope->tpl->_body, 0, strlen($scope->tpl->_body) - strlen($content));
    }

}
