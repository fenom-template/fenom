<?php
namespace Aspect;
use Aspect\Tokenizer;
use Aspect\Template;
use Aspect\Scope;

/**
 * Compilers collection
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

		$p = $tpl->parseParams($tokens);
		if(isset($p[0])) {
            $file_name = $p[0];
        } elseif (isset($p["file"])) {
            $file_name = $p["file"];
        } else {
			throw new ImproperUseException("The tag {include} requires 'file' parameter");
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
     * @param Tokenizer $tokens
     * @param Scope              $scope
     * @throws ImproperUseException
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
            throw new UnexpectedException($tokens, null, "tag {foreach}");
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
                throw new ImproperUseException("Unknown parameter '$param' in {foreach}");
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
     * check if value is scalar, like "string", 2, 2.2, true, false, null
     * @param string $value
     * @return bool
     * @todo add 'string' support
     */
    public static function isScalar($value) {
        return json_decode($value);
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
        $p = $tpl->parseParams($tokens);
        if(isset($p[0])) {
            $tpl_name = $p[0];
        } elseif (isset($p["file"])) {
            $tpl_name = $p["file"];
        } else {
            throw new ImproperUseException("{extends} require 'file' parameter");
        }
		$tpl->addPostCompile(__CLASS__."::extendBody");
        if($name = self::isScalar($tpl_name)) { // static extends
	        $tpl->_extends = $tpl->getStorage()->compile($name, false);
	        $tpl->addDepend($tpl->getStorage()->getTemplate($name)); // for valid compile-time need take template from storage
	        return "/* Static extends */";
        } else { // dynamic extends
	        $tpl->_extends = $tpl_name;
            return '/* Dynamic extends */'."\n".'$parent = $tpl->getStorage()->getTemplate('.$tpl_name.');';
        }
    }

    /**
     * Post compile method for {extends ...} tag
     * @param $body
     * @param Template $tpl
     */
    public static function extendBody(&$body, $tpl) {
	    if(isset($tpl->_extends)) { // is child
		    if(is_object($tpl->_extends)) {  // static extends
			    $t = $tpl;
			    while(isset($t->_extends)) {
                    $t->compile();
				    $t->_blocks += (array)$t->_extends->_blocks;
					$t = $t->_extends;

			    }

                if(empty($t->_blocks)) {
                    $body = $t->getBody();
                } else {
                    $b = $t->getBody();
                    foreach($t->_blocks as $name => $pos) {

                    }
                }
		    } else {        // dynamic extends
			    $body .= '<?php $parent->blocks = &$tpl->blocks; $parent->display((array)$tpl); unset($tpl->blocks, $parent->blocks); ?>';
			    //return '$tpl->blocks['.$scope["name"].'] = ob_get_clean();';
		    }
	    }
        /*$body = '<?php if(!isset($tpl->blocks)) {$tpl->blocks = array();}  ob_start(); ?>'.$body.'<?php ob_end_clean(); $parent->blocks = &$tpl->blocks; $parent->display((array)$tpl); unset($tpl->blocks, $parent->blocks); ?>';*/
    }


	public static function tagUse(Tokenizer $tokens, Template $tpl) {

	}

    /**
     * Tag {block ...}
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     * @throws ImproperUseException
     */
    public static function tagBlockOpen(Tokenizer $tokens, Scope $scope) {
        $p = $scope->tpl->parseParams($tokens);
        if(isset($p["name"])) {
            $scope["name"] = $p["name"];
        } elseif (isset($p[0])) {
            $scope["name"] = $p[0];
        } else {
            throw new ImproperUseException("{block} must be named");
        }
	    if(isset($scope->tpl->_extends)) { // is child
			if(is_object($scope->tpl->_extends)) {  // static extends
				$code = "";
			} else {        // dynamic extends
				$code = 'if(empty($tpl->blocks['.$scope["name"].'])) { ob_start();';
			}
	    } else {        // is parent
            if(isset($scope->tpl->_blocks[ $scope["name"] ])) { // skip own block and insert child's block after
                $scope["body"] = $scope->tpl->_body;
                $scope->tpl->_body = "";
                return '';
            } else {
		        $code = 'if(isset($tpl->blocks['.$scope["name"].'])) { echo $tpl->blocks['.$scope["name"].']; } else {';
            }
	    }
	    $scope["offset"] = strlen($scope->tpl->getBody()) + strlen($code);
	    return $code;
    }

    /**
     * Close tag {/block}
     * @param Tokenizer $tokens
     * @param Scope $scope
     * @return string
     */
    public static function tagBlockClose($tokens, Scope $scope) {
	    $scope->tpl->_blocks[ self::isScalar($scope["name"]) ] = substr($scope->tpl->getBody(), $scope["offset"]);
	    if(isset($scope->tpl->_extends)) { // is child
		    if(is_object($scope->tpl->_extends)) {  // static extends
			    return "";
		    } else {        // dynamic extends
			    return '$tpl->blocks['.$scope["name"].'] = ob_get_clean(); }';
		    }
	    } else {     // is parent
            if(isset($scope["body"])) {
                $scope->tpl->_body = $scope["body"].$scope->tpl->_blocks[ $scope["name"] ];
                return "";
            } else {
		        return '}';
            }
	    }
		/*    $scope->tpl->_blocks[ $scope["name"] ] = substr($scope->tpl->getBody(), $scope["offset"]);
	    return '}';*/
	    /*if(isset($scope->tpl->_extends) && is_object($scope->tpl->_extends)) {

		    //var_dump("fetched block ".$scope->tpl->_blocks[ $scope["name"] ]);
	    } else {
			return '}';
	    }*/
        /*if(isset($scope->tpl->_extends)) {
            $var = '$i'.$scope->tpl->i++;
            return $var.' = ob_get_clean(); if('.$var.') $tpl->blocks['.$scope["name"].'] = '.$var.';';
        } else {
            return 'if(empty($tpl->blocks['.$scope["name"].'])) { ob_end_flush(); } else { print($tpl->blocks['.$scope["name"].']); ob_end_clean(); }';
        }*/
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
        return "echo $function(".self::_toArray($tpl->parseParams($tokens)).', $tpl);';
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
            $ref = new \ReflectionMethod($function);
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
    public static function stdFuncClose($tokens, Scope $scope) {
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

    /**
     * Set variable expression parser
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @param bool $allow_array
     * @return string
     */
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

	public static function tagModifyOpen(Tokenizer $tokens, Scope $scope) {
		$scope["modifiers"] = $scope->tpl->parseModifier($tokens, "ob_get_clean()");
		return "ob_start();";
	}

	public static function tagModifyClose($tokens, Scope $scope) {
		return "echo ".$scope["modifiers"].";";
	}

}
