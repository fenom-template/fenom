<?php
namespace Aspect;

use Aspect;

/**
 * Aspect template compiler
 */
class Template extends Render {

    const DENY_ARRAY = 1;
    const DENY_MODS = 2;

    /**
     * @var int shared counter
     */
    public $i = 1;
    /**
     * Template PHP code
     * @var string
     */
    public $_body;
    /**
     * Call stack
     * @var Scope[]
     */
    private $_stack = array();

    /**
     * Template source
     * @var string
     */
    private $_src;
    /**
     * @var int
     */
    private $_pos = 0;
    private $_line = 1;
    private $_trim = false;
    private $_post = array();
    /**
     * @var bool
     */
    private $_ignore = false;
    /**
     * Options
     * @var int
     */
    private $_options = 0;

    /** System variables {$smarty.<...>} or {$aspect.<...>}
     * @var array
     */
    public static $sysvar = array('$aspect' => 1, '$smarty' => 1);

    public static function factory(Aspect $aspect) {
        return new static($aspect);
    }

    /**
     * @param Aspect $aspect Template storage
     */
    public function __construct(Aspect $aspect) {
        $this->_aspect = $aspect;
        $this->_options = $this->_aspect->getOptions();
    }

    /**
     * Load source from provider
     * @param string $name
     * @param bool $compile
     * @return \Aspect\Template
     */
    public function load($name, $compile = true) {
        $this->_name = $name;
        if($provider = strstr($name, ":", true)) {
            $this->_scm = $provider;
            $this->_base_name = substr($name, strlen($provider));
        } else {
            $this->_base_name = $name;
        }
        $this->_provider = $this->_aspect->getProvider($provider);
        $this->_src = $this->_provider->getSource($name, $this->_time);
        if($compile) {
            $this->compile();
        }
        return $this;
    }

    /**
     * Load custom source
     * @param string $name template name
     * @param string $src template source
     * @param bool $compile
     * @return \Aspect\Template
     */
    public function source($name, $src, $compile = true) {
        $this->_name = $name;
        $this->_src = $src;
        if($compile) {
            $this->compile();
        }
        return $this;
    }

    /**
     * Convert template to PHP code
     *
     * @throws CompileException
     */
    public function compile() {
        if(!isset($this->_src)) {
            return;
        }
        $pos = 0;
        while(($start = strpos($this->_src, '{', $pos)) !== false) { // search open-char of tags
            switch($this->_src[$start + 1]) { // check next char
                case "\n": case "\r": case "\t": case " ": case "}": // ignore the tag
                $pos = $start + 1; // try find tags after the current char
                continue 2;
                case "*": // if comment block
                    $end = strpos($this->_src, '*}', $start); // finding end of the comment block
                    $frag = substr($this->_src, $this->_pos, $start - $end); // read the comment block for precessing
                    $this->_line += substr_count($frag, "\n"); // count skipped lines
                    $pos = $end + 1; // trying finding tags after the comment block
                    continue 2;
            }
            $end = strpos($this->_src, '}', $start); // search close-char of the tag
            if(!$end) { // if unexpected end of template
                throw new CompileException("Unclosed tag in line {$this->_line}", 0, 1, $this->_name, $this->_line);
            }
            $frag = substr($this->_src, $this->_pos, $start - $this->_pos);  // variable $frag contains chars after last '}' and next '{'
            $tag = substr($this->_src, $start, $end - $start + 1); // variable $tag contains aspect tag '{...}'
            $this->_line += substr_count($this->_src, "\n", $this->_pos, $end - $start + 1); // count lines in $frag and $tag (using original text $code)
            $pos = $this->_pos = $end + 1; // move search-pointer to end of the tag
            //if($this->_trim) { // if previous tag has trim flag
            //    $frag = ltrim($frag);
            //}

            $this->_body .= str_replace("<?", '<?php echo "<?" ?>', $frag);

            $tag = $this->_tag($tag, $this->_trim); // dispatching tags

            //if($this->_trim) { // if current tag has trim flag
            //    $frag = rtrim($frag);
            //}
            $this->_body .= $tag;
        }
        $this->_body .= str_replace("<?", '<?php echo "<?" ?>', substr($this->_src, $this->_pos));
        if($this->_stack) {
            $_names = array();
            $_line = 0;
            foreach($this->_stack as $scope) {
                if(!$_line) {
                    $_line = $scope->line;
                }
                $_names[] = $scope->name.' defined on line '.$scope->line;
            }
            throw new CompileException("Unclosed block tags: ".implode(", ", $_names), 0, 1, $this->_name, $_line);
        }
        unset($this->_src);
        if($this->_post) {
            foreach($this->_post as $cb) {
                call_user_func_array($cb, array(&$this->_body, $this));
            }
        }
    }

    public function addPostCompile($cb) {
        $this->_post[] = $cb;
    }

    /**
     * Return PHP code of template
     * @return string
     */
    public function getBody() {
        return $this->_body;
    }

    /**
     * Return PHP code for saving to file
     * @return string
     */
    public function getTemplateCode() {
        return "<?php \n".
            "/** Aspect template '".$this->_name."' compiled at ".date('Y-m-d H:i:s')." */\n".
            "return new Aspect\\Render(\$this, ".$this->_getClosureSource().", ".var_export(array(
	            //"options" => $this->_options,
                "provider" => $this->_scm,
                "name" => $this->_name,
                "base_name" => $this->_base_name,
                "time" => $this->_time,
	            "depends" => $this->_depends
            ), true).");\n";
    }

    /**
     * Return closure code
     * @return string
     */
    private function _getClosureSource() {
        return "function (\$tpl) {\n?>{$this->_body}<?php\n}";
    }

    /**
     * Runtime execute template.
     *
     * @param array $values input values
     * @throws CompileException
     * @return Render
     */
    public function display(array $values) {
        if(!$this->_code) {
            // evaluate template's code
            eval("\$this->_code = ".$this->_getClosureSource().";");
            if(!$this->_code) {
                throw new CompileException("Fatal error while creating the template");
            }
        }
        return parent::display($values);

    }

	/**
	 * Add depends from template
	 * @param Render $tpl
	 */
	public function addDepend(Render $tpl) {

		$this->_depends[$tpl->getName()] = $tpl->getCompileTime();
	}

    /**
     * Execute template and return result as string
     * @param array $values for template
     * @throws CompileException
     * @return string
     */
    public function fetch(array $values) {
        if(!$this->_code) {
            eval("\$this->_code = ".$this->_getClosureSource().";");
            if(!$this->_code) {
                throw new CompileException("Fatal error while creating the template");
            }
        }
        return parent::fetch($values);
    }

    /**
     * Internal tags router
     * @param string $src
     * @param bool $trim
     * @throws UnexpectedException
     * @throws CompileException
     * @throws SecurityException
     * @return string
     */
    private function _tag($src, &$trim = false) {
        if($src[strlen($src) - 2] === "-") {
            $token = substr($src, 1, -2);
            $trim = true;
        } else {
            $token = substr($src, 1, -1);
            $trim = false;
        }
        $token = trim($token);
        if($this->_ignore) {
            if($token === '/ignore') {
                $this->_ignore = false;
                return '';
            } else {
                return $src;
            }
        }

        $tokens = new Tokenizer($token);
        try {
            switch($token[0]) {
                case '"':
                case '\'':
                case '$':
                    $code = "echo ".$this->parseExp($tokens).";";
                    break;
                case '/':
                    $code = $this->_end($tokens);
                    break;
                default:
                    $code = $this->_parseAct($tokens);
                    break;
            }

            if($tokens->key()) { // if tokenizer still have tokens
                throw new UnexpectedException($tokens);
            }
	        if(!$code) {
		        return "";
	        } else {
                return "<?php\n/* {$this->_name}:{$this->_line}: {$src} */\n {$code} ?>";
            }
        } catch (ImproperUseException $e) {
	        throw new CompileException($e->getMessage()." in {$this} line {$this->_line}", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\LogicException $e) {
            throw new SecurityException($e->getMessage()." in {$this} line {$this->_line}, near '{".$tokens->getSnippetAsString(0,0)."' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\Exception $e) {
            throw new CompileException($e->getMessage()." in {$this} line {$this->_line}, near '{".$tokens->getSnippetAsString(0,0)."' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
        }
    }

    /**
     * Close tag handler
     * @param Tokenizer $tokens
     * @return mixed
     * @throws TokenizeException
     */
    private function _end(Tokenizer $tokens) {
        $name = $tokens->getNext(Tokenizer::MACRO_STRING);
        $tokens->next();
        if(!$this->_stack) {
            throw new TokenizeException("Unexpected closing of the tag '$name', the tag hasn't been opened");
        }
        /** @var Scope $scope */
        $scope = array_pop($this->_stack);
        if($scope->name !== $name) {
            throw new TokenizeException("Unexpected closing of the tag '$name' (expecting closing of the tag {$scope->name}, opened on line {$scope->line})");
        }
        return $scope->close($tokens);
    }

    /**
     * Parse action {action ...} or {action(...) ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @throws TokenizeException
     * @return string
     */
    private function _parseAct(Tokenizer $tokens) {

        if($tokens->is(Tokenizer::MACRO_STRING)) {
            $action = $tokens->current();
        } else {
            return 'echo '.$this->parseExp($tokens).';';
        }

        if($action === "ignore") {
            $this->_ignore = true;
            $tokens->next();
            return '';
        }
        if($tokens->isNext("(")) {
            return "echo ".$this->parseExp($tokens).";";
        }

        if($act = $this->_aspect->getFunction($action)) {
            $tokens->next();
            switch($act["type"]) {
                case Aspect::BLOCK_COMPILER:
                    $scope = new Scope($action, $this, $this->_line, $act);
                    array_push($this->_stack, $scope);
                    return $scope->open($tokens);
                case Aspect::INLINE_COMPILER:
                    return call_user_func($act["parser"], $tokens, $this);
                case Aspect::INLINE_FUNCTION:
                    return call_user_func($act["parser"], $act["function"], $tokens, $this);
                case Aspect::BLOCK_FUNCTION:
                    $scope = new Scope($action, $this, $this->_line, $act);
                    $scope->setFuncName($act["function"]);
                    array_push($this->_stack, $scope);
                    return $scope->open($tokens);
            }
        }

        for($j = $i = count($this->_stack)-1; $i>=0; $i--) {
            if($this->_stack[$i]->hasTag($action, $j - $i)) {
                $tokens->next();
                return $this->_stack[$i]->tag($action, $tokens);
            }
        }
        if($tags = $this->_aspect->getTagOwners($action)) {
            throw new TokenizeException("Unexpected tag '$action' (this tag can be used with '".implode("', '", $tags)."')");
        } else {
            throw new TokenizeException("Unexpected tag $action");
        }
    }

    /**
     * Parse expressions. The mix of math operations, boolean operations, scalars, arrays and variables.
     *
     * @static
     * @param Tokenizer $tokens
     * @param bool               $required
     * @throws \LogicException
     * @throws UnexpectedException
     * @throws TokenizeException
     * @return string
     */
    public function parseExp(Tokenizer $tokens, $required = false) {
        $_exp = "";
        $brackets = 0;
        $term = false;
        $cond = false;
        while($tokens->valid()) {
            if(!$term && $tokens->is(Tokenizer::MACRO_SCALAR, '"', '`', T_ENCAPSED_AND_WHITESPACE)) {

                $_exp .= $this->parseScalar($tokens, true);
                $term = 1;
            } elseif(!$term && $tokens->is(T_VARIABLE)) {
                $pp = $tokens->isPrev(Tokenizer::MACRO_INCDEC);
                $_exp .= $this->parseVar($tokens, 0, $only_var);
                if($only_var && !$pp) {
                    $term = 2;
                } else {
                    $term = 1;
                }
            } elseif(!$term && $tokens->is("(")) {
                $_exp .= $tokens->getAndNext();
                $brackets++;
                $term = false;
            } elseif($term && $tokens->is(")")) {
                if(!$brackets) {
                    break;
                }
                $brackets--;
                $_exp .= $tokens->getAndNext();
                $term = 1;
            } elseif(!$term && $tokens->is(T_STRING)) {
                if($tokens->isSpecialVal()) {
                    $_exp .= $tokens->getAndNext();
                } elseif($tokens->isNext("(")) {
                    $func = $this->_aspect->getModifier($tokens->current());
                    $tokens->next();
                    $_exp .= $func.$this->parseArgs($tokens);
                } else {
                    break;
                }
                $term = 1;
            } elseif(!$term && $tokens->is(T_ISSET, T_EMPTY)) {
                $_exp .= $tokens->getAndNext();
                if($tokens->is("(") && $tokens->isNext(T_VARIABLE)) {
                    $_exp .= $this->parseArgs($tokens);
                } else {
                    throw new TokenizeException("Unexpected token ".$tokens->getNext().", isset() and empty() accept only variables");
                }
                $term = 1;
            } elseif(!$term && $tokens->is(Tokenizer::MACRO_UNARY)) {
                if(!$tokens->isNext(T_VARIABLE, T_DNUMBER, T_LNUMBER, T_STRING, T_ISSET, T_EMPTY)) {
                    break;
                }
                $_exp .= $tokens->getAndNext();
                $term = 0;
            } elseif($tokens->is(Tokenizer::MACRO_BINARY)) {
                if(!$term) {
                    throw new UnexpectedException($tokens);
                }
                if($tokens->isLast()) {
                    break;
                }
                if($tokens->is(Tokenizer::MACRO_COND)) {
                    if($cond) {
                        break;
                    }
                    $cond = true;
                } elseif ($tokens->is(Tokenizer::MACRO_BOOLEAN)) {
                    $cond = false;
                }
                $_exp .= " ".$tokens->getAndNext()." ";
                $term = 0;
            } elseif($tokens->is(Tokenizer::MACRO_INCDEC)) {
                if($term === 2) {
                    $term = 1;
                } elseif(!$tokens->isNext(T_VARIABLE)) {
                    break;
                }
                $_exp .= $tokens->getAndNext();
            } elseif($term && !$cond && !$tokens->isLast()) {
                if($tokens->is(Tokenizer::MACRO_EQUALS) && $term === 2) {
                    $_exp .= ' '.$tokens->getAndNext().' ';
                    $term = 0;
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        if($term === 0) {
            throw new UnexpectedException($tokens);
        }
        if($brackets) {
            throw new TokenizeException("Brackets don't match");
        }
        if($required && $_exp === "") {
            throw new UnexpectedException($tokens);
        }
        return $_exp;
    }


    /**
     * Parse variable
     * $var.foo[bar]["a"][1+3/$var]|mod:3:"w":$var3|mod3
     *
     * @see parseModifier
     * @static
     * @param Tokenizer $tokens
     * @param int                $deny
     * @param bool               $pure_var
     * @throws \LogicException
     * @return string
     */
    public function parseVar(Tokenizer $tokens, $deny = 0, &$pure_var = true) {
        $var = $tokens->get(T_VARIABLE);
        $pure_var = true;
        if(isset(self::$sysvar[ $var ])) {
            $_var = $this->_parseSystemVar($tokens);
        } else {
            $_var = '$tpl["'.ltrim($var,'$').'"]';
        }
        $tokens->next();
        while($t = $tokens->key()) {
            if($t === "." && !($deny & self::DENY_ARRAY)) {
                $key = $tokens->getNext();
                if($tokens->is(T_VARIABLE)) {
                    $key = "[ ".$this->parseVar($tokens, self::DENY_ARRAY)." ]";
                } elseif($tokens->is(Tokenizer::MACRO_STRING)) {
                    if($tokens->isNext("(")) {
                        $key = "[".$this->parseExp($tokens)."]";
                    } else {
                        $key = '["'.$key.'"]';
                        $tokens->next();
                    }
                } elseif($tokens->is(Tokenizer::MACRO_SCALAR, '"')) {
                    $key = "[".$this->parseScalar($tokens, false)."]";
                } else {
                    break;
                }
                $_var .= $key;
            } elseif($t === "[" && !($deny & self::DENY_ARRAY)) {
                $tokens->next();
                if($tokens->is(Tokenizer::MACRO_STRING)) {
                    if($tokens->isNext("(")) {
                        $key = "[".$this->parseExp($tokens)."]";
                    } else {
                        $key = '["'.$tokens->current().'"]';
                        $tokens->next();
                    }
                } else {
                    $key = "[".$this->parseExp($tokens, true)."]";
                }
                $tokens->get("]");
                $tokens->next();
                $_var .= $key;
            } elseif($t === "|" && !($deny & self::DENY_MODS)) {
                $pure_var = false;
                return $this->parseModifier($tokens, $_var);
            } elseif($t === T_OBJECT_OPERATOR) {
                $prop = $tokens->getNext(T_STRING);
                if($tokens->isNext("(")) {
                    if($this->_options & Aspect::DENY_METHODS) {
                        throw new \LogicException("Forbidden to call methods");
                    }
                    $pure_var = false;
                    $tokens->next();
                    $_var .= '->'.$prop.$this->parseArgs($tokens);
                } else {
                    $tokens->next();
                    $_var .= '->'.$prop;
                }
            } elseif($t === T_DNUMBER) {
                $_var .= '['.substr($tokens->getAndNext(), 1).']';
            } elseif($t === "?" || $t === "!") {
                $pure_var = false;
                $empty = ($t === "?");
                $tokens->next();
                if($tokens->is(":")) {
                    $tokens->next();
                    if($empty) {
                        return '(empty('.$_var.') ? ('.$this->parseExp($tokens, true).') : '.$_var.')';
                    } else {
                        return '(isset('.$_var.') ? '.$_var.' : ('.$this->parseExp($tokens, true).'))';
                    }
                } elseif($tokens->is(Tokenizer::MACRO_BINARY, Tokenizer::MACRO_BOOLEAN, Tokenizer::MACRO_MATH) || !$tokens->valid()) {
                    if($empty) {
                        return '!empty('.$_var.')';
                    } else {
                        return 'isset('.$_var.')';
                    }
                } else {
	                $expr1 = $this->parseExp($tokens, true);
	                if(!$tokens->is(":")) {
		                throw new UnexpectedException($tokens, null, "ternary operator");
	                }
	                $expr2 = $this->parseExp($tokens, true);
                    if($empty) {
	                    return '(empty('.$_var.') ? '.$expr2.' : '.$expr1;
                    } else {
                        return '(isset('.$_var.') ? '.$expr1.' : '.$expr2;
                    }
                }
            } elseif($t === "!") {
                $pure_var = false;
                $tokens->next();
                return 'isset('.$_var.')';
            } else {
                break;
            }
        }
        return $_var;
    }

    /**
     * Parse scalar values
     *
     * @param Tokenizer $tokens
     * @param bool $allow_mods
     * @return string
     * @throws TokenizeException
     */
    public function parseScalar(Tokenizer $tokens, $allow_mods = true) {
        $_scalar = "";
        if($token = $tokens->key()) {
            switch($token) {
                case T_CONSTANT_ENCAPSED_STRING:
                case T_LNUMBER:
                case T_DNUMBER:
                    $_scalar .= $tokens->getAndNext();
                    break;
                case T_ENCAPSED_AND_WHITESPACE:
                case '"':
                    $_scalar .= $this->parseSubstr($tokens);
                    break;
                default:
                    throw new TokenizeException("Unexpected scalar token '".$tokens->current()."'");
            }
            if($allow_mods && $tokens->is("|")) {
                return $this->parseModifier($tokens, $_scalar);
            }
        }
        return $_scalar;
    }

    /**
     * Parse string with or without variable
     *
     * @param Tokenizer $tokens
     * @throws UnexpectedException
     * @return string
     */
    public function parseSubstr(Tokenizer $tokens) {
        ref: {
            if($tokens->is('"',"`")) {
                $p = $tokens->p;
                $stop = $tokens->current();
                $_str = '"';
                $tokens->next();
                while($t = $tokens->key()) {
                    if($t === T_ENCAPSED_AND_WHITESPACE) {
                        $_str .= $tokens->current();
                        $tokens->next();
                    } elseif($t === T_VARIABLE) {
                        $_str .= '".$tpl["'.substr($tokens->current(), 1).'"]."';
                        $tokens->next();
                    } elseif($t === T_CURLY_OPEN) {
                        $tokens->getNext(T_VARIABLE);
                        $_str .= '".('.$this->parseExp($tokens).')."';
                    } elseif($t === "}") {
                        $tokens->next();
                    } elseif($t === $stop) {
                        $tokens->next();
                        return $_str.'"';
                    } else {

                        break;
                    }
                }
                if($more = $this->_getMoreSubstr($stop)) {
                    $tokens->append("}".$more, $p);
                    goto ref;
                }
                throw new UnexpectedException($tokens);
            } elseif($tokens->is(T_CONSTANT_ENCAPSED_STRING)) {
                return $tokens->getAndNext();
            } elseif($tokens->is(T_ENCAPSED_AND_WHITESPACE)) {
                $p = $tokens->p;
                if($more = $this->_getMoreSubstr($tokens->curr[1][0])) {
                    $tokens->append("}".$more, $p);
                    goto ref;
                }
                throw new UnexpectedException($tokens);
            } else {
                return "";
            }
        }
    }

    private function _getMoreSubstr($after) {
        $end = strpos($this->_src, $after, $this->_pos);
        $end = strpos($this->_src, "}", $end);
        if(!$end) {
            return false;
        }
        $fragment = substr($this->_src, $this->_pos, $end - $this->_pos);
        $this->_pos = $end + 1;
        return $fragment;
    }

    /**
     * Parse modifiers
     * |modifier:1:2.3:'string':false:$var:(4+5*$var3)|modifier2:"str {$var+3} ing":$arr.item
     *
     * @param Tokenizer $tokens
     * @param                    $value
     * @throws \LogicException
     * @throws \Exception
     * @return string
     */
    public function parseModifier(Tokenizer $tokens, $value) {
        while($tokens->is("|")) {
            $mods = $this->_aspect->getModifier( $tokens->getNext(Tokenizer::MACRO_STRING) );
            $tokens->next();
            $args = array();

            while($tokens->is(":")) {
                $token = $tokens->getNext(Tokenizer::MACRO_SCALAR, T_VARIABLE, '"', Tokenizer::MACRO_STRING, "(", "[");

                if($tokens->is(Tokenizer::MACRO_SCALAR) || $tokens->isSpecialVal()) {
                    $args[] = $token;
                    $tokens->next();
                } elseif($tokens->is(T_VARIABLE)) {
                    $args[] = $this->parseVar($tokens, self::DENY_MODS);
                } elseif($tokens->is('"', '`', T_ENCAPSED_AND_WHITESPACE)) {
                    $args[] = $this->parseSubstr($tokens);
                } elseif($tokens->is('(')) {
                    $args[] = $this->parseExp($tokens);
                } elseif($tokens->is('[')) {
                    $args[] = $this->parseArray($tokens);
                } elseif($tokens->is(T_STRING) && $tokens->isNext('('))  {
                    $args[] = $tokens->getAndNext().$this->parseArgs($tokens);
                } else {
                    break;
                }
            }


            if($args) {
                $value = $mods.'('.$value.', '.implode(", ", $args).')';
            } else {
                $value = $mods.'('.$value.')';
            }
        }
        return $value;
    }

    /**
     * Parse array
     * [1, 2.3, 5+7/$var, 'string', "str {$var+3} ing", $var2, []]
     *
     * @param Tokenizer $tokens
     * @throws UnexpectedException
     * @return string
     */
    public function parseArray(Tokenizer $tokens) {
        if($tokens->is("[")) {
            $_arr = "array(";
            $key = $val = false;
            $tokens->next();
            while($tokens->valid()) {
                if($tokens->is(',') && $val) {
                    $key = true;
                    $val = false;
                    $_arr .= $tokens->getAndNext().' ';
                } elseif($tokens->is(Tokenizer::MACRO_SCALAR, T_VARIABLE, T_STRING, T_EMPTY, T_ISSET, "(") && !$val) {
                    $_arr .= $this->parseExp($tokens, true);
                    $key = false;
                    $val = true;
                } elseif($tokens->is('"') && !$val) {
                    $_arr .= $this->parseSubstr($tokens);
                    $key = false;
                    $val = true;
                } elseif($tokens->is(T_DOUBLE_ARROW) && $val) {
                    $_arr .= ' '.$tokens->getAndNext().' ';
                    $key = true;
                    $val = false;
                } elseif(!$val && $tokens->is('[')) {
                    $_arr .= $this->parseArray($tokens);
                    $key = false;
                    $val = true;
                } elseif($tokens->is(']') && !$key) {
                    $tokens->next();
                    return $_arr.')';
                } else {
                    break;
                }
            }
        }
        throw new UnexpectedException($tokens);
    }

    /**
     * Parse system variable, like $aspect, $smarty
     *
     * @param Tokenizer $tokens
     * @throws \LogicException
     * @return mixed|string
     */
    private function _parseSystemVar(Tokenizer $tokens) {
        $tokens->getNext(".");
        $key = $tokens->getNext(T_STRING, T_CONST);
        switch($key) {
            case 'get':  return '$_GET';
            case 'post': return '$_POST';
            case 'cookies': return '$_COOKIES';
            case 'session': return '$_SESSION';
            case 'request': return '$_REQUEST';
            case 'now': return 'time()';
            case 'line': return $this->_line;
            case 'tpl_name': return '$tpl->getName()';
            case 'const':
                $tokens->getNext(".");
                return $tokens->getNext(T_STRING);
            default:
                throw new \LogicException("Unexpected key '".$tokens->current()."' in system variable");
        }
    }

    /**
     * Parse argument list
     * (1 + 2.3, 'string', $var, [2,4])
     *
     * @static
     * @param Tokenizer $tokens
     * @throws TokenizeException
     * @return string
     */
    public function parseArgs(Tokenizer $tokens) {
        $_args = "(";
        $tokens->next();
        $arg = $colon = false;
        while($tokens->valid()) {
            if(!$arg && $tokens->is(T_VARIABLE, T_STRING, "(", Tokenizer::MACRO_SCALAR, '"', Tokenizer::MACRO_UNARY, Tokenizer::MACRO_INCDEC)) {
                $_args .= $this->parseExp($tokens, true);
                $arg = true;
                $colon = false;
            } elseif(!$arg && $tokens->is('[')) {
                $_args .= $this->parseArray($tokens);
                $arg = true;
                $colon = false;
            } elseif($arg && $tokens->is(',')) {
                $_args .= $tokens->getAndNext().' ';
                $arg = false;
                $colon = true;
            } elseif(!$colon && $tokens->is(')')) {
                $tokens->next();
                return $_args.')';
            } else {
                break;
            }
        }

        throw new TokenizeException("Unexpected token '".$tokens->current()."' in argument list");
    }

    public function parseFirstArg(Tokenizer $tokens, &$static) {
        if($tokens->is(T_CONSTANT_ENCAPSED_STRING)) {
            $str = $tokens->getAndNext();
            $static = stripslashes(substr($str, 1, -1));
            return $str;
        } elseif($tokens->is(Tokenizer::MACRO_STRING)) {
            return $static = $tokens->getAndNext();
        } else {
            return $this->parseExp($tokens, true);
        }
    }

    /**
     * Parse parameters as $key=$value
     * param1=$var param2=3 ...
     *
     * @static
     * @param Tokenizer $tokens
     * @param array     $defaults
     * @throws \Exception
     * @return array
     */
    public function parseParams(Tokenizer $tokens, array $defaults = null) {
        $params = array();
        while($tokens->valid()) {
            if($tokens->is(Tokenizer::MACRO_STRING)) {
                $key = $tokens->getAndNext();
                if($defaults && !isset($defaults[$key])) {
                    throw new \Exception("Unknown parameter '$key'");
                }
                if($tokens->is("=")) {
                    $tokens->next();
                    $params[ $key ] = $this->parseExp($tokens);
                } else {
                    $params[ $key ] = true;
                    $params[] = '"'.$key.'"';
                }
            } elseif($tokens->is(Tokenizer::MACRO_SCALAR, '"', '`', T_VARIABLE, "[", '(')) {
                $params[] = $this->parseExp($tokens);
            } else {
                break;
            }
        }
        if($defaults) {
            $params += $defaults;
        }

        return $params;
    }
}



class CompileException extends \ErrorException {}
class SecurityException extends CompileException {}
class ImproperUseException extends \LogicException {}