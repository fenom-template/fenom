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

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Fenom\Error\CompileException;
use Fenom\Error\InvalidUsageException;
use Fenom\Error\UnexpectedTokenException;

/**
 * Compilers collection
 * @package Fenom
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Compiler
{
    /**
     * Tag {include ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @throws \LogicException
     * @return string
     */
    public static function tagInclude(Tokenizer $tokens, Tag $tag)
    {
        $tpl   = $tag->tpl;
        $name  = false;
        $cname = $tpl->parsePlainArg($tokens, $name);
        $p     = $tpl->parseParams($tokens);
        if ($name) {
            if ($tpl->getStorage()->getOptions() & \Fenom::FORCE_INCLUDE) {
                $_t = $tpl;
                $recursion = false;
                while($_t->parent) {
                    if($_t->parent->getName() == $name) { // recursion detected
                        $recursion = true;
                    }
                    $_t = $_t->parent;
                }
                if(!$recursion) {
                    $inc = $tpl->getStorage()->getRawTemplate($tpl);
                    $inc->load($name, true);
                    $tpl->addDepend($inc);
                    $var = $tpl->tmpVar();
                    if ($p) {
                        return $var . ' = $var; $var = ' . self::toArray($p) . ' + $var; ?>' . $inc->getBody() . '<?php $var = ' . $var . '; unset(' . $var . ');';
                    } else {
                        return $var . ' = $var; ?>' . $inc->getBody() . '<?php $var = ' . $var . '; unset(' . $var . ');';
                    }
                }
            } elseif (!$tpl->getStorage()->templateExists($name)) {
                throw new \LogicException("Template $name not found");
            }
        }
        if ($p) {
            return '$tpl->getStorage()->getTemplate(' . $cname . ')->display(' . self::toArray($p) . ' + $var);';
        } else {
            return '$tpl->getStorage()->getTemplate(' . $cname . ')->display($var);';
        }
    }

    /**
     * Tag {insert ...}
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @throws Error\InvalidUsageException
     * @return string
     */
    public static function tagInsert(Tokenizer $tokens, Tag $tag)
    {
        $tpl = $tag->tpl;
        $tpl->parsePlainArg($tokens, $name);
        if (!$name) {
            throw new InvalidUsageException("Tag {insert} accept only static template name");
        }
        $inc = $tpl->getStorage()->compile($name, false);
        $tpl->addDepend($inc);
        return '?>' . $inc->getBody() . '<?php';
    }


    /**
     * Open tag {if ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function ifOpen(Tokenizer $tokens, Tag $scope)
    {
        $scope["else"] = false;
        return 'if(' . $scope->tpl->parseExpr($tokens) . ') {';
    }

    /**
     * Tag {elseif ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagElseIf(Tokenizer $tokens, Tag $scope)
    {
        if ($scope["else"]) {
            throw new InvalidUsageException('Incorrect use of the tag {elseif}');
        }
        return '} elseif(' . $scope->tpl->parseExpr($tokens) . ') {';
    }

    /**
     * Tag {else}
     *
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function tagElse($tokens, Tag $scope)
    {
        $scope["else"] = true;
        return '} else {';
    }

    /**
     * Open tag {foreach ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @throws UnexpectedTokenException
     * @throws InvalidUsageException
     * @return string
     */
    public static function foreachOpen(Tokenizer $tokens, Tag $scope)
    {
        $scope["else"] = false;
        $scope["key"] = null;
        $scope["prepend"] = "";
        $scope["before"] = array();
        $scope["after"] = array();
        $scope["body"] = array();

        if ($tokens->is('[')) { // array
            $count = 0;
            $scope['from'] = $scope->tpl->parseArray($tokens, $count);
            $scope['check'] = $count;
            $scope["var"] = $scope->tpl->tmpVar();
            $scope['prepend'] = $scope["var"].' = '.$scope['from'].';';
            $scope['from']  = $scope["var"];
        } else { // expression
            $scope['from'] = $scope->tpl->parseExpr($tokens, $is_var);
            if($is_var) {
                $scope['check'] = '!empty('.$scope['from'].') && (is_array('.$scope['from'].') || '.$scope['from'].' instanceof \Traversable)';
            } else {
                $scope["var"] = $scope->tpl->tmpVar();
                $scope['prepend'] = $scope["var"].' = '.$scope['from'].';';
                $scope['from']  = $scope["var"];
                $scope['check'] = 'is_array('.$scope['from'].') && count('.$scope['from'].') || ('.$scope['from'].' instanceof \Traversable)';
            }
        }
        if($tokens->is(T_AS)) {
            $tokens->next();
            $value = $scope->tpl->parseVariable($tokens);
            if ($tokens->is(T_DOUBLE_ARROW)) {
                $tokens->next();
                $scope["key"]   = $value;
                $scope["value"] = $scope->tpl->parseVariable($tokens);
            } else {
                $scope["value"] = $value;
            }
        } else {
            $scope["value"] = '$_un';
        }
        while ($token = $tokens->key()) {
            $param = $tokens->get(T_STRING);
            $var_name = self::foreachProp($scope, $param);
            $tokens->getNext("=");
            $tokens->next();
            $scope['before'][] = $scope->tpl->parseVariable($tokens)." = &". $var_name;
        }

        return '';
    }

    /**
     * Tag {foreachelse}
     *
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function foreachElse($tokens, Tag $scope)
    {
        $scope["no-break"] = $scope["no-continue"] = $scope["else"] = true;
        $after = $scope["after"]  ? implode("; ", $scope["after"]) . ";" : "";
        return " {$after} } } else {";
    }

    /**
     * @param Tag $scope
     * @param string $prop
     * @return string
     * @throws CompileException
     */
    public static function foreachProp(Tag $scope, $prop) {
        if(empty($scope["props"][$prop])) {
            $var_name = $scope["props"][$prop] = $scope->tpl->tmpVar()."_".$prop;
            switch($prop) {
                case "index":
                    $scope["before"][] = $var_name . ' = 0';
                    $scope["after"][]  = $var_name . '++';
                    break;
                case "first":
                    $scope["before"][] = $var_name . ' = true';
                    $scope["after"][]  = $var_name . ' && (' . $var_name . ' = false )';
                    break;
                case "last":
                    $scope["before"][] = $var_name . ' = false';
                    $scope["uid"]      = $scope->tpl->tmpVar();
                    $scope["before"][] = $scope["uid"] . " = count({$scope["from"]})";
                    $scope["body"][]   = 'if(!--' . $scope["uid"] . ') ' . $var_name . ' = true';
                    break;
                default:
                    throw new CompileException("Unknown foreach property '$prop'");
            }
        }

        return $scope["props"][$prop];
    }

    /**
     * Close tag {/foreach}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function foreachClose($tokens, Tag $scope)
    {
        $before         = $scope["before"] ? implode("; ", $scope["before"]) . ";" : "";
        $head           = $scope["body"]   ? implode("; ", $scope["body"]) . ";" : "";
        $body           = $scope->getContent();
        if ($scope["key"]) {
            $code = "<?php {$scope["prepend"]} if({$scope["check"]}) {\n $before foreach({$scope["from"]} as {$scope["key"]} => {$scope["value"]}) { $head?>$body";
        } else {
            $code = "<?php {$scope["prepend"]} if({$scope["check"]}) {\n $before foreach({$scope["from"]} as {$scope["value"]}) { $head?>$body";
        }
        $scope->replaceContent($code);
        if ($scope["else"]) {
            return '}';
        } else {
            $after = $scope["after"]  ? implode("; ", $scope["after"]) . ";" : "";
            return " {$after} } }";
        }
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @throws Error\UnexpectedTokenException
     * @throws Error\InvalidUsageException
     * @return string
     * @codeCoverageIgnore
     */
    public static function forOpen(Tokenizer $tokens, Tag $scope)
    {
        trigger_error("Fenom: tag {for} deprecated, use {foreach 1..4 as \$value} (in {$scope->tpl->getName()}:{$scope->line})", E_USER_DEPRECATED);
        $p = array(
            "index" => false,
            "first" => false,
            "last"  => false,
            "step"  => 1,
            "to"    => false,
//            "max"   => false,
//            "min"   => false
        );
        $scope["after"] = $before = $body = array();
        $i              = array('', '');
        $c              = "";
        $var            = $scope->tpl->parseTerm($tokens, $is_var);
        if (!$is_var) {
            throw new UnexpectedTokenException($tokens);
        }
        $tokens->get("=");
        $tokens->next();
        $val = $scope->tpl->parseExpr($tokens);
        $p   = $scope->tpl->parseParams($tokens, $p);

        if (is_numeric($p["step"])) {
            if ($p["step"] > 0) {
                $condition = "$var <= {$p['to']}";
                if ($p["last"]) {
                    $c = "($var + {$p['step']}) > {$p['to']}";
                }
            } elseif ($p["step"] < 0) {
                $condition = "$var >= {$p['to']}";
                if ($p["last"]) {
                    $c = "($var + {$p['step']}) < {$p['to']}";
                }
            } else {
                throw new InvalidUsageException("Invalid step value");
            }
        } else {
            $condition = "({$p['step']} > 0 && $var <= {$p['to']} || {$p['step']} < 0 && $var >= {$p['to']})";
            if ($p["last"]) {
                $c = "({$p['step']} > 0 && ($var + {$p['step']}) <= {$p['to']} || {$p['step']} < 0 && ($var + {$p['step']}) >= {$p['to']})";
            }
        }

        if ($p["first"]) {
            $before[]         = $p["first"] . ' = true';
            $scope["after"][] = $p["first"] . ' && (' . $p["first"] . ' = false )';
        }
        if ($p["last"]) {
            $before[] = $p["last"] . ' = false';
            $body[]   = "if($c) {$p['last']} = true";
        }

        if ($p["index"]) {
            $i[0] .= $p["index"] . ' = 0,';
            $i[1] .= $p["index"] . '++,';
        }

        $scope["else"]      = false;
        $scope["else_cond"] = "$var==$val";
        $before             = $before ? implode("; ", $before) . ";" : "";
        $body               = $body ? implode("; ", $body) . ";" : "";
        $scope["after"]     = $scope["after"] ? implode("; ", $scope["after"]) . ";" : "";

        return "$before for({$i[0]} $var=$val; $condition;{$i[1]} $var+={$p['step']}) { $body";
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     * @codeCoverageIgnore
     */
    public static function forElse($tokens, Tag $scope)
    {
        $scope["no-break"] = $scope["no-continue"] = true;
        $scope["else"]     = true;
        return " } if({$scope['else_cond']}) {";
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     * @codeCoverageIgnore
     */
    public static function forClose($tokens, Tag $scope)
    {
        if ($scope["else"]) {
            return '}';
        } else {
            return " {$scope['after']} }";
        }
    }

    /**
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function whileOpen(Tokenizer $tokens, Tag $scope)
    {
        return 'while(' . $scope->tpl->parseExpr($tokens) . ') {';
    }

    /**
     * Open tag {switch}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function switchOpen(Tokenizer $tokens, Tag $scope)
    {
        $expr             = $scope->tpl->parseExpr($tokens);
        $scope["case"]    = array();
        $scope["last"]    = array();
        $scope["default"] = '';
        $scope["var"]     = $scope->tpl->tmpVar();
        $scope["expr"]    = $scope["var"] . ' = strval(' . $expr . ')';
        // lazy init
        return '';
    }

    /**
     * Resort cases for {switch}
     * @param Tag $scope
     */
    private static function _caseResort(Tag $scope)
    {
        $content = $scope->cutContent();
        foreach ($scope["last"] as $case) {
            if($case === false) {
                $scope["default"] .= $content;
            } else {
                if (!isset($scope["case"][$case])) {
                    $scope["case"][$case] = "";
                }
                $scope["case"][$case] .= $content;
            }
        }
        $scope["last"] = array();
    }

    /**
     * Tag {case ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function tagCase(Tokenizer $tokens, Tag $tag)
    {
        self::_caseResort($tag);
        do {
            if($tokens->is(T_DEFAULT)) {
                $tag["last"][] = false;
                $tokens->next();
            } else {
                $tag["last"][] = $tag->tpl->parseScalar($tokens);
            }
            if ($tokens->is(',')) {
                $tokens->next();
            } else {
                break;
            }
        } while (true);
        return '';
    }


    /**
     * Tag {default}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function tagDefault($tokens, Tag $scope)
    {
        self::_caseResort($scope);
        $scope["last"][] = false;
        return '';
    }

    /**
     * Close tag {switch}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function switchClose($tokens, Tag $scope)
    {
        self::_caseResort($scope);
        $expr    = $scope["var"];
        $code    = $scope["expr"] . ";\n";
        $default = $scope["default"];
        foreach ($scope["case"] as $case => $content) {
            if (is_numeric($case)) {
                $case = "'$case'";
            }
            $code .= "if($expr == $case) {\n?>$content<?php\n} else";
        }
        $code .= " {\n?>$default<?php\n}\nunset(" . $scope["var"] . ")";
        return $code;
    }

    /**
     * Tag {continue}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagContinue($tokens, Tag $scope)
    {
        if (empty($scope["no-continue"])) {
            return 'continue;';
        } else {
            throw new InvalidUsageException("Improper usage of the tag {continue}");
        }
    }

    /**
     * Tag {break}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @throws InvalidUsageException
     * @return string
     */
    public static function tagBreak($tokens, Tag $scope)
    {
        if (empty($scope["no-break"])) {
            return 'break;';
        } else {
            throw new InvalidUsageException("Improper usage of the tag {break}");
        }
    }

    /**
     * Dispatch {extends} tag
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @throws Error\InvalidUsageException
     * @return string
     */
    public static function tagExtends(Tokenizer $tokens, Tag $tag)
    {
        $tpl = $tag->tpl;
        if ($tpl->extends) {
            throw new InvalidUsageException("Only one {extends} allowed");
        } elseif ($tpl->getStackSize()) {
            throw new InvalidUsageException("Tag {extends} can not be nested");
        }
        $cname = $tpl->parsePlainArg($tokens, $name);
        if ($name) {
            $tpl->extends = $name;
        } else {
            $tpl->dynamic_extends = $cname;
        }
        if (!$tpl->extend_body) {
            $tpl->addPostCompile(__CLASS__ . "::extendBody");
            $tpl->extend_body = true;
        }
    }

    /**
     * Post compile action for {extends ...} tag
     * @param Template $tpl
     * @param string $body
     */
    public static function extendBody($tpl, &$body)
    {
        if ($tpl->dynamic_extends) {
            if (!$tpl->ext_stack) {
                $tpl->ext_stack[] = $tpl->getName();
            }
            foreach ($tpl->ext_stack as &$t) {
                $stack[] = "'$t'";
            }
            $stack[] = $tpl->dynamic_extends;
            $body    = '<?php $tpl->getStorage()->display(array(' . implode(', ', $stack) . '), $var); ?>';
        } else {
            $child = $tpl;
            while ($child && $child->extends) {
                $parent = $tpl->extend($child->extends);
                $child  = $parent->extends ? $parent : false;
            }
            $tpl->extends = false;
        }
        $tpl->extend_body = false;
    }

    /**
     * Tag {use ...}
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @throws Error\InvalidUsageException
     * @return string
     */
    public static function tagUse(Tokenizer $tokens, Tag $tag)
    {
        $tpl = $tag->tpl;
        if ($tpl->getStackSize()) {
            throw new InvalidUsageException("Tag {use} can not be nested");
        }
        $tpl->parsePlainArg($tokens, $name);
        if ($name) {
            $tpl->importBlocks($name);
        } else {
            throw new InvalidUsageException('Invalid template name for tag {use}');
        }
    }

    /**
     * Tag {block ...}
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @throws \RuntimeException
     * @return string
     */
    public static function tagBlockOpen(Tokenizer $tokens, Tag $scope)
    {
        $scope["cname"] = $scope->tpl->parsePlainArg($tokens, $name);
        if (!$name) {
            throw new \RuntimeException("Invalid block name");
        }
        $scope["name"]       = $name;
        $scope["use_parent"] = false;
    }

    /**
     * @param Tokenizer $tokens
     * @param Tag $scope
     */
    public static function tagBlockClose($tokens, Tag $scope)
    {
        $tpl  = $scope->tpl;
        $name = $scope["name"];
        if (isset($tpl->blocks[$name])) { // block defined
            $block = & $tpl->blocks[$name];
            if ($block['use_parent']) {
                $parent = $scope->getContent();
                $block['block'] = str_replace($block['use_parent'] . " ?>", "?>" . $parent, $block['block']);
            }
            if (!$block["import"]) { // not from {use} - redefine block
                $scope->replaceContent($block["block"]);
                return;
            } elseif ($block["import"] != $tpl->getName()) { // tag {use} was in another template
                $tpl->blocks[$scope["name"]]["import"] = false;
                $scope->replaceContent($block["block"]);
            }
        }

        $tpl->blocks[$scope["name"]] = array(
            "from"       => $tpl->getName(),
            "import"     => false,
            "use_parent" => $scope["use_parent"],
            "block"      => $scope->getContent()
        );
    }

    /**
     * Tag {parent}
     *
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function tagParent($tokens, Tag $scope)
    {
        $block_scope = $scope->tpl->getParentScope('block');
        if (!$block_scope['use_parent']) {
            $block_scope['use_parent'] = "/* %%parent#{$scope['name']}%% */";
        }
        return $block_scope['use_parent'];
    }

    /**
     * Standard close tag {/...}
     *
     * @return string
     */
    public static function stdClose()
    {
        return '}';
    }

    /**
     * Standard function parser
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function stdFuncParser(Tokenizer $tokens, Tag $tag)
    {
        if(is_string($tag->callback)) {
            return $tag->out($tag->callback . "(" . self::toArray($tag->tpl->parseParams($tokens)) . ', $tpl, $var)');
        } else {
            return '$info = $tpl->getStorage()->getTag('.var_export($tag->name, true).');'.PHP_EOL.
            $tag->out('call_user_func_array($info["function"], array('.self::toArray($tag->tpl->parseParams($tokens)).', $tpl, &$var))');
        }
    }

    /**
     * Smart function parser
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function smartFuncParser(Tokenizer $tokens, Tag $tag)
    {
        if (strpos($tag->callback, "::") || is_array($tag->callback)) {
            list($class, $method) = explode("::", $tag->callback, 2);
            $ref = new \ReflectionMethod($class, $method);
        } else {
            $ref = new \ReflectionFunction($tag->callback);
        }
        $args   = array();
        $params = $tag->tpl->parseParams($tokens);
        foreach ($ref->getParameters() as $param) {
            if (isset($params[$param->getName()])) {
                $args[] = $params[$param->getName()];
            } elseif (isset($params[$param->getPosition()])) {
                $args[] = $params[$param->getPosition()];
            } elseif ($param->isOptional()) {
                $args[] = var_export($param->getDefaultValue(), true);
            }
        }
        return $tag->out($tag->callback . "(" . implode(", ", $args) . ')');
    }

    /**
     * Standard function open tag parser
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function stdFuncOpen(Tokenizer $tokens, Tag $tag)
    {
        $tag["params"] = self::toArray($tag->tpl->parseParams($tokens));
        $tag->setOption(\Fenom::AUTO_ESCAPE, false);
        return 'ob_start();';
    }

    /**
     * Standard function close tag parser
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function stdFuncClose($tokens, Tag $tag)
    {
        $tag->restore(\Fenom::AUTO_ESCAPE);
        if(is_string($tag->callback)) {
            return $tag->out($tag->callback . "(" . $tag["params"] . ', ob_get_clean(), $tpl, $var)');
        } else {
            return '$info = $tpl->getStorage()->getTag('.var_export($tag->name, true).');'.PHP_EOL.
            $tag->out('call_user_func_array($info["function"], array(' . $tag["params"] . ', ob_get_clean(), $tpl, &$var))');
        }
    }

    /**
     * Convert array of code to string array
     * @param $params
     * @return string
     */
    public static function toArray($params)
    {
        $_code = array();
        foreach ($params as $k => $v) {
            $_code[] = '"' . $k . '" => ' . $v;
        }

        return 'array(' . implode(",", $_code) . ')';
    }

    /**
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function setOpen(Tokenizer $tokens, Tag $scope)
    {
        if($tokens->is(T_VARIABLE)) {
            $var = $scope->tpl->parseVariable($tokens);
        } elseif($tokens->is('$')) {
            $var = $scope->tpl->parseAccessor($tokens, $is_var);
            if(!$is_var) {
                throw new InvalidUsageException("Accessor is not writable");
            }
        } else {
            throw new InvalidUsageException("{set} and {add} accept only variable");
        }
        $before = $after = "";
        if($scope->name == 'add') {
            $before = "if(!isset($var)) {\n";
            $after = "\n}";
        }
        if ($tokens->is(Tokenizer::MACRO_EQUALS, '[')) { // inline tag {var ...}
            $equal = $tokens->getAndNext();
            if($equal == '[') {
                $tokens->need(']')->next()->need('=')->next();
                $equal = '[]=';
            }
            $scope->close();
            if ($tokens->is("[")) {
                return $before.$var . $equal . $scope->tpl->parseArray($tokens) . ';'.$after;
            } else {
                return $before.$var . $equal . $scope->tpl->parseExpr($tokens) . ';'.$after;
            }
        } else {
            $scope["name"] = $var;
            if ($tokens->is('|')) {
                $scope["value"] = $before . $scope->tpl->parseModifier($tokens, "ob_get_clean()").';'.$after;
            } else {
                $scope["value"] = $before . "ob_get_clean();" . $after;
            }
            return 'ob_start();';
        }
    }

    /**
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function setClose($tokens, Tag $scope)
    {
        return $scope["name"] . '=' . $scope["value"] . ';';
    }

    public static function tagDo($tokens, Tag $scope)
    {
        return $scope->tpl->parseExpr($tokens).';';
    }


    /**
     * @param Tokenizer $tokens
     * @param Tag $scope
     * @return string
     */
    public static function filterOpen($tokens, Tag $scope)
    {
        $scope["filter"] = $scope->tpl->parseModifier($tokens, "ob_get_clean()");
        return "ob_start();";
    }

    /**
     * @param $tokens
     * @param Tag $scope
     * @return string
     */
    public static function filterClose($tokens, Tag $scope)
    {
        return "echo " . $scope["filter"] . ";";
    }

    /**
     * Tag {cycle}
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @throws Error\InvalidUsageException
     * @return string
     */
    public static function tagCycle(Tokenizer $tokens, Tag $tag)
    {
        $tpl = $tag->tpl;
        if ($tokens->is("[")) {
            $exp = $tpl->parseArray($tokens);
        } else {
            $exp = $tpl->parseExpr($tokens);
        }
        if ($tokens->valid()) {
            $p = $tpl->parseParams($tokens);
            if (empty($p["index"])) {
                throw new InvalidUsageException("Cycle may contain only index attribute");
            } else {
                return 'echo ' . __CLASS__ . '::cycle(' . $exp . ', ' . $p["index"] . ')';
            }
        } else {
            $var = $tpl->tmpVar();
            return 'echo ' . __CLASS__ . '::cycle(' . $exp . ", isset($var) ? ++$var : ($var = 0) )";
        }
    }

    /**
     * Runtime cycle callback
     * @param mixed $vals
     * @param $index
     * @return mixed
     */
    public static function cycle($vals, $index)
    {
        return $vals[$index % count($vals)];
    }

    /**
     * Import macros from templates
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @throws Error\UnexpectedTokenException
     * @throws Error\InvalidUsageException
     * @return string
     */
    public static function tagImport(Tokenizer $tokens, Tag $tag)
    {
        $tpl    = $tag->tpl;
        $import = array();
        if ($tokens->is('[')) {
            $tokens->next();
            while ($tokens->valid()) {
                if ($tokens->is(Tokenizer::MACRO_STRING)) {
                    $import[$tokens->current()] = true;
                    $tokens->next();
                } elseif ($tokens->is(']')) {
                    $tokens->next();
                    break;
                } elseif ($tokens->is(',')) {
                    $tokens->next();
                } else {
                    break;
                }
            }
            if ($tokens->current() != "from") {
                throw new UnexpectedTokenException($tokens);
            }
            $tokens->next();
        }

        $tpl->parsePlainArg($tokens, $name);
        if (!$name) {
            throw new InvalidUsageException("Invalid template name");
        }
        if ($tokens->is(T_AS)) {
            $alias = $tokens->next()->get(Tokenizer::MACRO_STRING);
            if ($alias === "macro") {
                $alias = "";
            }
            $tokens->next();
        } else {
            $alias = "";
        }
        $donor = $tpl->getStorage()->getRawTemplate()->load($name, true);
        if ($donor->macros) {
            foreach ($donor->macros as $name => $macro) {
                if ($p = strpos($name, ".")) {
                    $name = substr($name, $p);
                }
                if ($import && !isset($import[$name])) {
                    continue;
                }
                if ($alias) {
                    $name = $alias . '.' . $name;
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
     * @param Tag $scope
     * @throws InvalidUsageException
     */
    public static function macroOpen(Tokenizer $tokens, Tag $scope)
    {
        $scope["name"]      = $tokens->get(Tokenizer::MACRO_STRING);
        $scope["recursive"] = false;
        $args               = array();
        $defaults           = array();
        if (!$tokens->valid()) {
            return;
        }
        $tokens->next();
        if ($tokens->is('(') || !$tokens->isNext(')')) {
            $tokens->next();
            while ($tokens->is(Tokenizer::MACRO_STRING, T_VARIABLE)) {
                $param = $tokens->current();
                if ($tokens->is(T_VARIABLE)) {
                    $param = ltrim($param, '$');
                }
                $tokens->next();
                $args[] = $param;
                if ($tokens->is('=')) {
                    $tokens->next();
                    if ($tokens->is(T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DNUMBER) || $tokens->isSpecialVal()) {
                        $defaults[$param] = $tokens->getAndNext();
                    } else {
                        throw new InvalidUsageException("Macro parameters may have only scalar defaults");
                    }
                }
                $tokens->skipIf(',');
            }
            $tokens->skipIf(')');
        }
        $scope["macro"] = array(
            "name"      => $scope["name"],
            "args"      => $args,
            "defaults"  => $defaults,
            "body"      => "",
            "recursive" => false
        );
        return;
    }

    /**
     * @param Tokenizer $tokens
     * @param Tag $scope
     */
    public static function macroClose($tokens, Tag $scope)
    {
        if ($scope["recursive"]) {
            $scope["macro"]["recursive"] = true;
        }
        $scope["macro"]["body"]             = $scope->cutContent();
        $scope->tpl->macros[$scope["name"]] = $scope["macro"];
    }

    /**
     * Output value as is, without escaping
     *
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function tagRaw(Tokenizer $tokens, Tag $tag)
    {
        return 'echo ' . $tag->tpl->parseExpr($tokens);
    }

    /**
     * @param Tokenizer $tokens
     * @param Tag $tag
     */
    public static function escapeOpen(Tokenizer $tokens, Tag $tag)
    {
        $expected = ($tokens->get(T_STRING) == "true" ? true : false);
        $tokens->next();
        $tag->setOption(\Fenom::AUTO_ESCAPE, $expected);
    }

    /**
     * Do nothing
     */
    public static function nope()
    {
    }

    /**
     * @param Tokenizer $tokens
     * @param Tag $tag
     */
    public static function stripOpen(Tokenizer $tokens, Tag $tag)
    {
        $expected = ($tokens->get(T_STRING) == "true" ? true : false);
        $tokens->next();
        $tag->setOption(\Fenom::AUTO_STRIP, $expected);
    }

    /**
     * Tag {ignore}
     * @param Tokenizer $tokens
     * @param Tag $tag
     */
    public static function ignoreOpen($tokens, Tag $tag)
    {
        $tag->tpl->ignore('ignore');
    }

    /**
     * Tag {unset ...}
     * @param Tokenizer $tokens
     * @param Tag $tag
     * @return string
     */
    public static function tagUnset(Tokenizer $tokens, Tag $tag)
    {
        $unset = array();
        while($tokens->valid()) {
            $unset[] = $tag->tpl->parseVariable($tokens);
        }
        return 'unset('.implode(", ", $unset).')';
    }

    public static function tagPaste(Tokenizer $tokens, Tag $tag)
    {
        $name = $tokens->get(T_CONSTANT_ENCAPSED_STRING);
        $tokens->next();
        if(isset($tag->tpl->blocks[$name])) {
            return "?>".substr($tag->tpl->blocks[$name]["block"], 1, -1)."<?php ";
        } else {
            return "";
        }
    }
}
