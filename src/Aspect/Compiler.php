<?php
namespace Aspect;
use Aspect\Tokenizer;
use Aspect\Template;
use Aspect\Scope;

class Compiler {
    /**
     * Tag {include ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @throws \Exception
     * @return string
     */
    public static function tagInclude(Tokenizer $tokens, Template $tpl) {

		$p = $tpl->parseParams($tokens);
		if(isset($p[0])) {
            $file_name = $p[0];
        } elseif (isset($p["file"])) {
            $file_name = $p["file"];
        } else {
			throw new \Exception("{include} require 'file' parameter");
		}
        unset($p["file"], $p[0]);
        if($p) {
            return '$tpl->getStorage()->getTemplate('.$file_name.')->display('.self::_toArray($p).'+(array)$tpl);';
        } else {
		    return '$tpl->getStorage()->getTemplate('.$file_name.')->display((array)$tpl);';
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
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @throws \Exception
     * @internal param \Exception $
     * @return string
     */
    public static function tagElseIf(Tokenizer $tokens, Scope $scope) {
        if($scope["else"]) {
            throw new \Exception('Incorrect use of the tag {else if}');
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
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @throws \Exception
     * @internal param \Exception $
     * @return string
     */
    public static function foreachOpen(Tokenizer $tokens, Scope $scope) {
        $p = array("index" => false, "first" => false, "last" => false);
        $key = null;
        $before = $body = array();
        if($tokens->is(T_VARIABLE)) {
            $from = $scope->tpl->parseVar($tokens, Template::DENY_MODS);
            $prepend = "";
        } elseif($tokens->is('[')) {
            $from = $scope->tpl->parseArray($tokens);
            $uid = '$v'.$scope->tpl->i++;
            $prepend = $uid.' = '.$from.';';
            $from = $uid;
        } else {

            if($tokens->valid()) {
                throw new \Exception("Unexpected token '".$tokens->current()."' in 'foreach'");
            } else {
                throw new \Exception("Unexpected end of 'foreach'");
            }
        }
        $tokens->get(T_AS);
        $tokens->next();
        $value = $scope->tpl->parseVar($tokens, Template::DENY_MODS | Template::DENY_ARRAY);
        if($tokens->is(T_DOUBLE_ARROW)) {
            $tokens->next();
            $key = $value;
            $value = $scope->tpl->parseVar($tokens, Template::DENY_MODS | Template::DENY_ARRAY);
        }

        $scope["after"] = array();
        $scope["else"] = false;

        while($token = $tokens->key()) {
            $param = $tokens->get(T_STRING);
            if(!isset($p[ $param ])) {
                throw new \Exception("Unknown parameter '$param'");
            }
            $tokens->getNext("=");
            $tokens->next();
            $p[ $param ] = $scope->tpl->parseVar($tokens, Template::DENY_MODS | Template::DENY_ARRAY);
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
     * @internal param $
     * @param Scope              $scope
     * @return string
     */
    public static function foreachElse(Tokenizer $tokens, Scope $scope) {
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
    public static function foreachClose(Tokenizer $tokens, Scope $scope) {
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
     * @throws \Exception
     */
    public static function forOpen(Tokenizer $tokens, Scope $scope) {
        $p = array("index" => false, "first" => false, "last" => false, "step" => 1, "to" => false, "max" => false, "min" => false);
        $scope["after"] = $before = $body = array();
        $i = array('', '');
        $c = "";
        $var = $scope->tpl->parseVar($tokens, Template::DENY_MODS);
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
                throw new \Exception("Invalid step value");
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
    public static function forClose(Tokenizer $tokens, Scope $scope) {
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
        // lazy switch init
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
     * @throws \Exception
     * @return string
     */
    public static function tagContinue(Tokenizer $tokens, Scope $scope) {
        if(empty($scope["no-continue"])) {
		    return 'continue;';
        } else {
            throw new \Exception("Incorrect use of the tag {continue}");
        }
	}

    /**
     * Tag {default}
     *
     * @static
     * @return string
     */
    public static function tagDefault(Tokenizer $tokens, Scope $scope) {
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
     * @param Scope              $scope
     * @throws \Exception
     * @return string
     */
    public static function tagBreak(Tokenizer $tokens, Scope $scope) {
        if(empty($scope["no-break"])) {
            return 'break;';
        } else {
            throw new \Exception("Incorrect use of the tag {break}");
        }
	}

    public static function tagExtends(Tokenizer $tokens, Template $tpl) {
        if(!empty($tpl->_extends)) {
            throw new \Exception("Only one {extends} allowed");
        }
        $p = $tpl->parseParams($tokens);
        if(isset($p[0])) {
            $tpl_name = $p[0];
        } elseif (isset($p["file"])) {
            $tpl_name = $p["file"];
        } else {
            throw new \Exception("{extends} require 'file' parameter");
        }
        $tpl->addPostCompile(__CLASS__."::extendBody");
        $tpl->_extends = $tpl_name;
        return '$parent = $tpl->getStorage()->getTemplate('.$tpl_name.');';
    }

    public static function extendBody(&$body, Template $tpl) {
        $body = '<?php if(!isset($tpl->blocks)) {$tpl->blocks = array();}  ob_start(); ?>'.$body.'<?php ob_end_clean(); $parent->blocks = &$tpl->blocks; $parent->display((array)$tpl); unset($tpl->blocks, $parent->blocks); ?>';
    }

    public static function tagBlockOpen(Tokenizer $tokens, Scope $scope) {
        $p = $scope->tpl->parseParams($tokens);
        if(isset($p["name"])) {
            $scope["name"] = $p["name"];
        } elseif (isset($p[0])) {
            $scope["name"] = $p[0];
        } else {
            throw new \Exception("{block} require name parameter");
        }

        if($scope->closed) {
            return 'isset($tpl->blocks['.$scope["name"].']) ? $tpl->blocks[] : "" ;';
        } else {
            return 'ob_start();';
        }
    }

    public static function tagBlockClose(Tokenizer $tokens, Scope $scope) {
        if(isset($scope->tpl->_extends)) {
            $var = '$i'.$scope->tpl->i++;
            return $var.' = ob_get_clean(); if('.$var.') $tpl->blocks['.$scope["name"].'] = '.$var.';';
        } else {
            return 'if(empty($tpl->blocks['.$scope["name"].'])) { ob_end_flush(); } else { print($tpl->blocks['.$scope["name"].']); ob_end_clean(); }';
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
     * Standard function tag parser
     *
     * @static
     * @param                    $function
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @return string
     */
    public static function stdFuncParser($function, Tokenizer $tokens, Template $tpl) {
        return "echo $function(".self::_toArray($tpl->parseParams($tokens)).', $tpl);';
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
        $scope["params"] = self::_toArray($scope->tpl->parseParams($tokens));
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
    public static function stdFuncClose(Tokenizer $tokens, Scope $scope) {
        return "echo ".$scope["function"].'('.$scope["params"].', ob_get_clean(), $tpl);';
    }

    private static function _toArray($params) {
        $_code = array();
        foreach($params as $k => $v) {
            $_code[] = '"'.$k.'" => '.$v;
        }

        return 'array('.implode(",", $_code).')';
    }

    /**
     * Tag {var ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @param Template           $tpl
     * @return string
     */
    public static function assign(Tokenizer $tokens, Template $tpl) {
        return self::setVar($tokens, $tpl).';';
    }

    public static function setVar(Tokenizer $tokens, Template $tpl, $allow_array = true) {
        $var = $tpl->parseVar($tokens, $tpl::DENY_MODS);

        $tokens->get('=');
        $tokens->next();
        if($tokens->is("[") && $allow_array) {
            return $var.'='.$tpl->parseArray($tokens);
        } else {
            return $var.'='.$tpl->parseExp($tokens, true);
        }
    }

}
