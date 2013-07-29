<?php
/*
 * This file is part of Fenom.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
namespace Fenom;
use Fenom\Tokenizer;
use Fenom\Template;
use Fenom\Scope;

/**
 * Compilers collection
 * @package Fenom
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Compiler {
    /**
     * Tag {include ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagInclude(Tokenizer $tokens, Template $tpl) {
        $cname = $tpl->parsePlainArg($tokens, $name);
        $p = $tpl->parseParams($tokens);
        if($p) { // if we have additionally variables
            if($name && ($tpl->getStorage()->getOptions() & \Fenom::FORCE_INCLUDE)) { // if FORCE_INCLUDE enabled and template name known
                $inc = $tpl->getStorage()->compile($name, false);
                $tpl->addDepend($inc);
                return '$_tpl = (array)$tpl; $tpl->exchangeArray('.self::toArray($p).'+$_tpl); ?>'.$inc->_body.'<?php $tpl->exchangeArray($_tpl); unset($_tpl);';
            } else {
                return '$tpl->getStorage()->getTemplate('.$cname.')->display('.self::toArray($p).'+(array)$tpl);';
            }
        } else {
            if($name && ($tpl->getStorage()->getOptions() & \Fenom::FORCE_INCLUDE)) { // if FORCE_INCLUDE enabled and template name known
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
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagElseIf(Tokenizer $tokens, Scope $scope) {
        if($scope["else"]) {
            throw new InvalidUsageException('Incorrect use of the tag {elseif}');
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
     * @throws InvalidUsageException
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
                throw new InvalidUsageException("Unknown parameter '$param' in {foreach}");
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
     * @throws InvalidUsageException
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
                throw new InvalidUsageException("Invalid step value if {for}");
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
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagContinue($tokens, Scope $scope) {
        if(empty($scope["no-continue"])) {
            return 'continue;';
        } else {
            throw new InvalidUsageException("Improper usage of the tag {continue}");
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
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagBreak($tokens, Scope $scope) {
        if(empty($scope["no-break"])) {
            return 'break;';
        } else {
            throw new InvalidUsageException("Improper usage of the tag {break}");
        }
    }

    /**
     * Dispatch {extends} tag
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagExtends(Tokenizer $tokens, Template $tpl) {
        if(!empty($tpl->_extends)) {
            throw new InvalidUsageException("Only one {extends} allowed");
        } elseif($tpl->getStackSize()) {
            throw new InvalidUsageException("Tags {extends} can not be nested");
        }
        $tpl_name = $tpl->parsePlainArg($tokens, $name);
        if(empty($tpl->_extended)) {
            $tpl->addPostCompile(__CLASS__."::extendBody");
        }
        if($tpl->getOptions() & Template::DYNAMIC_EXTEND) {
            $tpl->_compatible = true;
        }
        if($name) { // static extends
            $tpl->_extends = $tpl->getStorage()->getRawTemplate()->load($name, false);
            if(!isset($tpl->_compatible)) {
                $tpl->_compatible = &$tpl->_extends->_compatible;
            }
            $tpl->addDepend($tpl->_extends);
            return "";
        } else { // dynamic extends
            if(!isset($tpl->_compatible)) {
                $tpl->_compatible = true;
            }
            $tpl->_extends = $tpl_name;
            return '$parent = $tpl->getStorage()->getTemplate('.$tpl_name.', \Fenom\Template::EXTENDED);';
        }
    }

    /**
     * Post compile action for {extends ...} tag
     * @param string $body
     * @param Template $tpl
     */
    public static function extendBody(&$body, $tpl) {
        $t = $tpl;
        if($tpl->uses) {
            $tpl->blocks += $tpl->uses;
        }
        while(isset($t->_extends)) {
            $t = $t->_extends;
            if(is_object($t)) {
                /* @var \Fenom\Template $t */
                $t->_extended = true;
                $tpl->addDepend($t);
                $t->_compatible = &$tpl->_compatible;
                $t->blocks = &$tpl->blocks;
                $t->compile();
                if($t->uses) {
                    $tpl->blocks += $t->uses;
                }
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
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagUse(Tokenizer $tokens, Template $tpl) {
        if($tpl->getStackSize()) {
            throw new InvalidUsageException("Tags {use} can not be nested");
        }
        $cname = $tpl->parsePlainArg($tokens, $name);
        if($name) {
            $donor = $tpl->getStorage()->getRawTemplate()->load($name, false);
            $donor->_extended = true;
            $donor->_extends = $tpl;
            $donor->_compatible = &$tpl->_compatible;
            //$donor->blocks = &$tpl->blocks;
            $donor->compile();
            $blocks = $donor->blocks;
            foreach($blocks as $name => $code) {
                if(isset($tpl->blocks[$name])) {
                    $tpl->blocks[$name] = $code;
                    unset($blocks[$name]);
                }
            }
            $tpl->uses = $blocks + $tpl->uses;
            $tpl->addDepend($donor);
            return '?>'.$donor->getBody().'<?php ';
        } else {
//            throw new InvalidUsageException('template name must be given explicitly yet');
            // under construction
            $tpl->_compatible = true;
            return '$donor = $tpl->getStorage()->getTemplate('.$cname.', \Fenom\Template::EXTENDED);'.PHP_EOL.
            '$donor->fetch((array)$tpl);'.PHP_EOL.
            '$tpl->b += (array)$donor->b';
        }
    }

    /**
     * Tag {block ...}
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     * @throws InvalidUsageException
     */
    public static function tagBlockOpen(Tokenizer $tokens, Scope $scope) {
        if($scope->level > 0) {
            var_dump("".$scope->tpl);
            $scope->tpl->_compatible = true;
        }
        $scope["cname"] = $scope->tpl->parsePlainArg($tokens, $name);
        $scope["name"]  = $name;
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
                if($tpl->_compatible) { // is compatible mode
                    $scope->replaceContent(
                        '<?php /* 1) Block '.$tpl.': '.$scope["cname"].' */'.PHP_EOL.' if(empty($tpl->b['.$scope["cname"].'])) { '.
                        '$tpl->b['.$scope["cname"].'] = function($tpl) { ?>'.PHP_EOL.
                        $scope->getContent().
                        "<?php };".
                        "} ?>".PHP_EOL
                    );
                } elseif(!isset($tpl->blocks[ $scope["name"] ])) { // is block not registered
                    $tpl->blocks[ $scope["name"] ] = $scope->getContent();
                    $scope->replaceContent(
                        '<?php /* 2) Block '.$tpl.': '.$scope["cname"].' '.$tpl->_compatible.' */'.PHP_EOL.' $tpl->b['.$scope["cname"].'] = function($tpl) { ?>'.PHP_EOL.
                        $scope->getContent().
                        "<?php }; ?>".PHP_EOL
                    );
                }
            } else { // dynamic name
                $tpl->_compatible = true; // enable compatible mode
                $scope->replaceContent(
                    '<?php /* 3) Block '.$tpl.': '.$scope["cname"].' */'.PHP_EOL.' if(empty($tpl->b['.$scope["cname"].'])) { '.
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
                        '<?php /* 4) Block '.$tpl.': '.$scope["cname"].' */'.PHP_EOL.' if(isset($tpl->b['.$scope["cname"].'])) { echo $tpl->b['.$scope["cname"].']->__invoke($tpl); } else {?>'.PHP_EOL.
                        $tpl->blocks[ $scope["name"] ].
                        '<?php } ?>'.PHP_EOL
                    );

                } else {
                    $scope->replaceContent($tpl->blocks[ $scope["name"] ]);
                }
//            } elseif(isset($tpl->_extended) || !empty($tpl->_compatible)) {
            } elseif(isset($tpl->_extended) && $tpl->_compatible || empty($tpl->_extended)) {
                $scope->replaceContent(
                    '<?php /* 5) Block '.$tpl.': '.$scope["cname"].' */'.PHP_EOL.' if(isset($tpl->b['.$scope["cname"].'])) { echo $tpl->b['.$scope["cname"].']->__invoke($tpl); } else {?>'.PHP_EOL.
                    $scope->getContent().
                    '<?php } ?>'.PHP_EOL
                );
            }
        }
        return '';

    }

    public static function tagParent($tokens, Scope $scope) {
        if(empty($scope->tpl->_extends)) {
            throw new InvalidUsageException("Tag {parent} may be declared in children");
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
     * @param mixed              $function
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @return string
     */
    public static function stdFuncParser($function, Tokenizer $tokens, Template $tpl) {
        return "$function(".self::toArray($tpl->parseParams($tokens)).', $tpl)';
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
                $args[] = var_export($param->getDefaultValue(), true);
            }
        }
        return "$function(".implode(", ", $args).')';
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
        return $scope["function"].'('.$scope["params"].', ob_get_clean(), $tpl)';
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

    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function varOpen(Tokenizer $tokens, Scope $scope) {
        $var = $scope->tpl->parseVariable($tokens, Template::DENY_MODS);
        if($tokens->is('=')) { // inline tag {var ...}
            $scope->is_closed = true;
            $tokens->next();
            if($tokens->is("[")) {
                return $var.'='.$scope->tpl->parseArray($tokens);
            } else {
                return $var.'='.$scope->tpl->parseExp($tokens, true);
            }
        } else {
            $scope["name"] = $var;
            if($tokens->is('|')) {
                $scope["value"] = $scope->tpl->parseModifier($tokens, "ob_get_clean()");
            } else {
                $scope["value"] = "ob_get_clean()";
            }
            return 'ob_start();';
        }
    }

    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function varClose(Tokenizer $tokens, Scope $scope) {
        return $scope["name"].'='.$scope["value"].';';
    }


    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function filterOpen(Tokenizer $tokens, Scope $scope) {
        $scope["filter"] = $scope->tpl->parseModifier($tokens, "ob_get_clean()");
        return "ob_start();";
    }

    /**
     * @param $tokens
     * @param Scope $scope
     * @return string
     */
    public static function filterClose($tokens, Scope $scope) {
        return "echo ".$scope["filter"].";";
    }

    /**
     * Tag {cycle}
     *
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     * @throws InvalidUsageException
     */
    public static function tagCycle(Tokenizer $tokens, Template $tpl) {
        if($tokens->is("[")) {
            $exp = $tpl->parseArray($tokens);
        } else {
            $exp = $tpl->parseExp($tokens, true);
        }
        if($tokens->valid()) {
            $p = $tpl->parseParams($tokens);
            if(empty($p["index"])) {
                throw new InvalidUsageException("Cycle may contain only index attribute");
            } else {
                return 'echo '.__CLASS__.'::cycle('.$exp.', '.$p["index"].')';
            }
        } else {
            $var = $tpl->tmpVar();
            return 'echo '.__CLASS__.'::cycle('.$exp.", isset($var) ? ++$var : ($var = 0) )";
        }
    }

    /**
     * Runtime cycle callback
     * @param mixed $vals
     * @param $index
     * @return mixed
     */
    public static function cycle($vals, $index) {
        return $vals[$index % count($vals)];
    }

    /**
     * Import macros from templates
     *
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @throws UnexpectedTokenException
     * @throws InvalidUsageException
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
            throw new InvalidUsageException("Invalid usage tag {import}");
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
     * @throws InvalidUsageException
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
                    throw new InvalidUsageException("Macro parameters may have only scalar defaults");
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

    /**
     * Output value as is, without escaping
     *
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagRaw(Tokenizer $tokens, Template $tpl) {
        $escape = (bool)$tpl->escape;
        $tpl->escape = false;
        if($tokens->is(':')) {
            $func = $tokens->getNext(Tokenizer::MACRO_STRING);
            $tag = $tpl->getStorage()->getFunction($func);
            if($tag["type"] == \Fenom::INLINE_FUNCTION) {
                $code = $tpl->parseAct($tokens);
            } elseif ($tag["type"] == \Fenom::BLOCK_FUNCTION) {
                $code = $tpl->parseAct($tokens);
                $tpl->getLastScope()->escape = false;
                return $code;
            } else {
                throw new InvalidUsageException("Raw mode allow for expressions or functions");
            }
        } else {
            $code = $tpl->out($tpl->parseExp($tokens, true));
        }
        $tpl->escape = $escape;
        return $code;
    }

    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     */
    public static function autoescapeOpen(Tokenizer $tokens, Scope $scope) {
        $boolean = ($tokens->get(T_STRING) == "true" ? true : false);
        $scope["escape"] = $scope->tpl->escape;
        $scope->tpl->escape = $boolean;
        $tokens->next();
    }

    /**
     * @param Tokenizer $tokens
     * @param Scope $scope
     */
    public static function autoescapeClose(Tokenizer $tokens, Scope $scope) {
        $scope->tpl->escape = $scope["escape"];
    }
}
