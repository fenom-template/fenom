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
use Fenom;
use Fenom\Error\UnexpectedTokenException;
use Fenom\Error\CompileException;
use Fenom\Error\InvalidUsageException;
use Fenom\Error\SecurityException;
use Fenom\Error\TokenizeException;

/**
 * Template compiler
 *
 * @package    Fenom
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Template extends Render
{

    /**
     * Disable array parser.
     */
    const DENY_ARRAY = 1;
    /**
     * Disable modifier parser.
     */
    const DENY_MODS = 2;

    /**
     * Template was extended
     */
    const DYNAMIC_EXTEND = 0x1000;
    const EXTENDED = 0x2000;
    const DYNAMIC_BLOCK = 0x4000;

    /**
     * @var int shared counter
     */
    public $i = 1;
    /**
     * @var array of macros
     */
    public $macros = array();

    /**
     * @var array of blocks
     */
    public $blocks = array();

    public $uses = array();

    public $parents = array();

    /**
     * Escape outputs value
     * @var bool
     */
    public $escape = false;

    public $_extends;
    public $_extended = false;
    public $_compatible;

    /**
     * Template PHP code
     * @var string
     */
    private $_body;

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
    private $_line = 1;
    private $_post = array();
    /**
     * @var bool
     */
    private $_ignore = false;

    private $_before;

    private $_filters = array();

    private static $_checkers = array(
        'integer' => 'is_int(%s)',
        'int' => 'is_int(%s)',
        'float' => 'is_float(%s)',
        'double' => 'is_float(%s)',
        'decimal' => 'is_float(%s)',
        'string' => 'is_string(%s)',
        'bool' => 'is_bool(%s)',
        'boolean' => 'is_bool(%s)',
        'number' => 'is_numeric(%s)',
        'numeric' => 'is_numeric(%s)',
        'scalar' => 'is_scalar(%s)',
        'object' => 'is_object(%s)',
        'callable' => 'is_callable(%s)',
        'callback' => 'is_callable(%s)',
        'array' => 'is_array(%s)',
        'iterable' => '\Fenom\Modifier::isIterable(%s)',
        'const' => 'defined(%s)',
        'template' => '$tpl->getStorage()->templateExists(%s)',
        'empty' => 'empty(%s)',
        'set' => 'isset(%s)',
        '_empty' => '!%s', // for none variable
        '_set' => '(%s !== null)', // for none variable
        'odd' => '(%s & 1)',
        'even' => '!(%s %% 2)',
        'third' => '!(%s %% 3)'
    );

    /**
     * @param Fenom $fenom Template storage
     * @param int $options
     * @return \Fenom\Template
     */
    public function __construct(Fenom $fenom, $options)
    {
        $this->_fenom = $fenom;
        $this->_options = $options;
        $this->_filters = $this->_fenom->getFilters();
    }

    /**
     * Get tag stack size
     * @return int
     */
    public function getStackSize()
    {
        return count($this->_stack);
    }

    /**
     * Load source from provider
     * @param string $name
     * @param bool $compile
     * @return self
     */
    public function load($name, $compile = true)
    {
        $this->_name = $name;
        if ($provider = strstr($name, ":", true)) {
            $this->_scm = $provider;
            $this->_base_name = substr($name, strlen($provider) + 1);
        } else {
            $this->_base_name = $name;
        }
        $this->_provider = $this->_fenom->getProvider($provider);
        $this->_src = $this->_provider->getSource($this->_base_name, $this->_time);
        if ($compile) {
            $this->compile();
        }
        return $this;
    }

    /**
     * Load custom source
     * @param string $name template name
     * @param string $src template source
     * @param bool $compile
     * @return \Fenom\Template
     */
    public function source($name, $src, $compile = true)
    {
        $this->_name = $name;
        $this->_src = $src;
        if ($compile) {
            $this->compile();
        }
        return $this;
    }

    /**
     * Convert template to PHP code
     *
     * @throws CompileException
     */
    public function compile()
    {
        $end = $pos = 0;
        $this->escape = $this->_options & Fenom::AUTO_ESCAPE;
        foreach ($this->_fenom->getPreFilters() as $filter) {
            $this->_src = call_user_func($filter, $this->_src, $this);
        }

        while (($start = strpos($this->_src, '{', $pos)) !== false) { // search open-symbol of tags
            switch ($this->_src[$start + 1]) { // check next character
                case "\n":
                case "\r":
                case "\t":
                case " ":
                case "}": // ignore the tag
                    $this->_appendText(substr($this->_src, $pos, $start - $pos + 2));
                    $end = $start + 1;
                    break;
                case "*": // comment block
                    $end = strpos($this->_src, '*}', $start); // find end of the comment block
                    if ($end === false) {
                        throw new CompileException("Unclosed comment block in line {$this->_line}", 0, 1, $this->_name, $this->_line);
                    }
                    $end++;
                    $this->_appendText(substr($this->_src, $pos, $start - $pos));
                    $comment = substr($this->_src, $start, $end - $start); // read the comment block for processing
                    $this->_line += substr_count($comment, "\n"); // count lines in comments
                    unset($comment); // cleanup
                    break;
                default:
                    $this->_appendText(substr($this->_src, $pos, $start - $pos));
                    $end = $start + 1;
                    do {
                        $need_more = false;
                        $end = strpos($this->_src, '}', $end + 1); // search close-symbol of the tag
                        if ($end === false) { // if unexpected end of template
                            throw new CompileException("Unclosed tag in line {$this->_line}", 0, 1, $this->_name, $this->_line);
                        }
                        $tag = substr($this->_src, $start, $end - $start + 1); // variable $tag contains fenom tag '{...}'

                        $_tag = substr($tag, 1, -1); // strip delimiters '{' and '}'

                        if ($this->_ignore) { // check ignore
                            if ($_tag === '/ignore') { // turn off ignore
                                $this->_ignore = false;
                            } else { // still ignore
                                $this->_appendText($tag);
                            }
                        } else {
                            $tokens = new Tokenizer($_tag); // tokenize the tag
                            if ($tokens->isIncomplete()) { // all strings finished?
                                $need_more = true;
                            } else {
                                $this->_appendCode($this->parseTag($tokens), $tag); // start the tag lexer
                                if ($tokens->key()) { // if tokenizer have tokens - throws exceptions
                                    throw new CompileException("Unexpected token '" . $tokens->current() . "' in {$this} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line);
                                }
                            }
                        }
                    } while ($need_more);
                    unset($_tag, $tag); // cleanup
                    break;
            }
            $pos = $end + 1; // move search-pointer to end of the tag
        }

        gc_collect_cycles();
        $this->_appendText(substr($this->_src, $end ? $end + 1 : 0)); // append tail of the template
        if ($this->_stack) {
            $_names = array();
            $_line = 0;
            foreach ($this->_stack as $scope) {
                if (!$_line) {
                    $_line = $scope->line;
                }
                $_names[] = '{' . $scope->name . '} opened on line ' . $scope->line;
            }
            throw new CompileException("Unclosed tag" . (count($_names) == 1 ? "" : "s") . ": " . implode(", ", $_names), 0, 1, $this->_name, $_line);
        }
        $this->_src = ""; // cleanup
        if ($this->_post) {
            foreach ($this->_post as $cb) {
                call_user_func_array($cb, array(&$this->_body, $this));
            }
        }
        $this->addDepend($this); // for 'verify' performance
        foreach ($this->_fenom->getPostFilters() as $filter) {
            $this->_body = call_user_func($filter, $this->_body, $this);
        }
    }

    /**
     * Execute some code at loading cache
     * @param $code
     * @return void
     */
    public function before($code)
    {
        $this->_before .= $code;
    }

    /**
     * Generate temporary internal template variable
     * @return string
     */
    public function tmpVar()
    {
        return '$t' . ($this->i++);
    }

    /**
     * Append plain text to template body
     *
     * @param string $text
     */
    private function _appendText($text)
    {
        $this->_line += substr_count($text, "\n");
        if ($this->_filters) {
            if (strpos($text, "<?") === false) {
                foreach ($this->_filters as $filter) {
                    $text = call_user_func($filter, $text, $this);
                }
                $this->_body .= $text;
            } else {
                $fragments = explode("<?", $text);
                foreach ($fragments as &$fragment) {
                    if ($fragment) {
                        foreach ($this->_filters as $filter) {
                            $fragment = call_user_func($filter, $fragment, $this);
                        }
                    }
                }
                $this->_body .= implode('<?php echo "<?"; ?>', $fragments);
            }
        } else {
            $this->_body .= str_replace("<?", '<?php echo "<?"; ?>' . PHP_EOL, $text);
        }
    }

    /**
     * Append PHP_EOL after each '?>'
     * @param int $code
     * @return string
     */
    private function _escapeCode($code)
    {
        $c = "";
        foreach (token_get_all($code) as $token) {
            if (is_string($token)) {
                $c .= $token;
            } elseif ($token[0] == T_CLOSE_TAG) {
                $c .= $token[1] . PHP_EOL;
            } else {
                $c .= $token[1];
            }
        }
        return $c;
    }

    /**
     * Append PHP code to template body
     *
     * @param string $code
     * @param $source
     */
    private function _appendCode($code, $source)
    {
        if (!$code) {
            return;
        } else {
            $this->_line += substr_count($source, "\n");
            if (strpos($code, '?>') !== false) {
                $code = $this->_escapeCode($code); // paste PHP_EOL
            }
            $this->_body .= "<?php\n/* {$this->_name}:{$this->_line}: {$source} */\n $code ?>" . PHP_EOL;
        }
    }

    /**
     * @param callable[] $cb
     */
    public function addPostCompile($cb)
    {
        $this->_post[] = $cb;
    }

    /**
     * Return PHP code of template
     *
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Return PHP code for saving to file
     *
     * @return string
     */
    public function getTemplateCode()
    {
        $before = $this->_before ? $this->_before . "\n" : "";
        return "<?php \n" .
        "/** Fenom template '" . $this->_name . "' compiled at " . date('Y-m-d H:i:s') . " */\n" .
        $before . // some code 'before' template
        "return new Fenom\\Render(\$fenom, " . $this->_getClosureSource() . ", " . var_export(array(
            "options" => $this->_options,
            "provider" => $this->_scm,
            "name" => $this->_name,
            "base_name" => $this->_base_name,
            "time" => $this->_time,
            "depends" => $this->_depends
        ), true) . ");\n";
    }

    /**
     * Return closure code
     * @return string
     */
    private function _getClosureSource()
    {
        return "function (\$tpl) {\n?>{$this->_body}<?php\n}";
    }

    /**
     * Runtime execute template.
     *
     * @param array $values input values
     * @throws CompileException
     * @return Render
     */
    public function display(array $values)
    {
        if (!$this->_code) {
            // evaluate template's code
            eval("\$this->_code = " . $this->_getClosureSource() . ";");
            if (!$this->_code) {
                throw new CompileException("Fatal error while creating the template");
            }
        }
        return parent::display($values);

    }

    /**
     * Add depends from template
     * @param Render $tpl
     */
    public function addDepend(Render $tpl)
    {
        $this->_depends[$tpl->getScm()][$tpl->getName()] = $tpl->getTime();
    }

    /**
     * Output the value
     *
     * @param $data
     * @return string
     */
    public function out($data)
    {
        if ($this->escape) {
            return "echo htmlspecialchars($data, ENT_COMPAT, 'UTF-8');";
        } else {
            return "echo $data;";
        }
    }

    /**
     * Tag router
     * @param Tokenizer $tokens
     *
     * @throws SecurityException
     * @throws CompileException
     * @return string executable PHP code
     */
    public function parseTag(Tokenizer $tokens)
    {
        try {
            if ($tokens->is(Tokenizer::MACRO_STRING)) {
                if ($tokens->current() === "ignore") {
                    $this->_ignore = true;
                    $tokens->next();
                    return '';
                } else {
                    return $this->parseAct($tokens);
                }
            } elseif ($tokens->is('/')) {
                return $this->parseEndTag($tokens);
            } elseif ($tokens->is('#')) {
                return $this->out($this->parseConst($tokens), $tokens);
            } else {
                return $this->out($this->parseExp($tokens), $tokens);
            }
        } catch (InvalidUsageException $e) {
            throw new CompileException($e->getMessage() . " in {$this} line {$this->_line}", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\LogicException $e) {
            throw new SecurityException($e->getMessage() . " in {$this} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\Exception $e) {
            throw new CompileException($e->getMessage() . " in {$this} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
        }
    }

    /**
     * Close tag handler
     *
     * @param Tokenizer $tokens
     * @return string
     * @throws TokenizeException
     */
    public function parseEndTag(Tokenizer $tokens)
    {
        $name = $tokens->getNext(Tokenizer::MACRO_STRING);
        $tokens->next();
        if (!$this->_stack) {
            throw new TokenizeException("Unexpected closing of the tag '$name', the tag hasn't been opened");
        }
        /** @var Scope $scope */
        $scope = array_pop($this->_stack);
        if ($scope->name !== $name) {
            throw new TokenizeException("Unexpected closing of the tag '$name' (expecting closing of the tag {$scope->name}, opened on line {$scope->line})");
        }
        if ($scope->is_compiler) {
            return $scope->close($tokens);
        } else {
            $code = $this->out($scope->close($tokens));
            $scope->tpl->escape = $scope->escape; // restore escape option
            return $code;
        }
    }

    /**
     * Get current scope
     * @return Scope
     */
    public function getLastScope()
    {
        return end($this->_stack);
    }

    /**
     * Parse action {action ...} or {action(...) ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @throws \LogicException
     * @throws TokenizeException
     * @return string
     */
    public function parseAct(Tokenizer $tokens)
    {
        if ($tokens->is(Tokenizer::MACRO_STRING)) {
            $action = $tokens->getAndNext();
        } else {
            return $this->out($this->parseExp($tokens)); // may be math and/or boolean expression
        }
        if ($tokens->is("(", T_NAMESPACE, T_DOUBLE_COLON) && !$tokens->isWhiteSpaced()) { // just invoke function or static method
            $tokens->back();
            return $this->out($this->parseExp($tokens));
        }

        if ($tokens->is('.')) {
            $name = $tokens->skip()->get(Tokenizer::MACRO_STRING);
            if ($action !== "macro") {
                $name = $action . "." . $name;
            }
            return $this->parseMacroCall($tokens, $name);
        }

        if ($tag = $this->_fenom->getTag($action, $this)) { // call some function
            switch ($tag["type"]) {
                case Fenom::BLOCK_COMPILER:
                    $scope = new Scope($action, $this, $this->_line, $tag, count($this->_stack), $this->_body);
                    $code = $scope->open($tokens);
                    if (!$scope->is_closed) {
                        array_push($this->_stack, $scope);
                    }
                    return $code;
                case Fenom::INLINE_COMPILER:
                    return call_user_func($tag["parser"], $tokens, $this);
                case Fenom::INLINE_FUNCTION:
                    return $this->out(call_user_func($tag["parser"], $tag["function"], $tokens, $this));
                case Fenom::BLOCK_FUNCTION:
                    $scope = new Scope($action, $this, $this->_line, $tag, count($this->_stack), $this->_body);
                    $scope->setFuncName($tag["function"]);
                    array_push($this->_stack, $scope);
                    $scope->escape = $this->escape;
                    $this->escape = false;
                    return $scope->open($tokens);
                default:
                    throw new \LogicException("Unknown function type");
            }
        }

        for ($j = $i = count($this->_stack) - 1; $i >= 0; $i--) { // call function's internal tag
            if ($this->_stack[$i]->hasTag($action, $j - $i)) {
                return $this->_stack[$i]->tag($action, $tokens);
            }
        }
        if ($tags = $this->_fenom->getTagOwners($action)) { // unknown template tag
            throw new TokenizeException("Unexpected tag '$action' (this tag can be used with '" . implode("', '", $tags) . "')");
        } else {
            throw new TokenizeException("Unexpected tag $action");
        }
    }

    /**
     * Parse expressions. The mix of operations and terms.
     *
     * @param Tokenizer $tokens
     * @param bool $required
     * @return string
     * @throws Error\UnexpectedTokenException
     */
    public function parseExp(Tokenizer $tokens, $required = false)
    {
        $exp = array();
        $var = false; // last term was: true - variable, false - mixed
        $op = false; // last exp was operator
        $cond = false; // was conditional operator
        while ($tokens->valid()) {
            // parse term
            $term = $this->parseTerm($tokens, $var);
            if ($term !== false) {
                $exp[] = $term;
                $op = false;
            } else {
                break;
            }

            if (!$tokens->valid()) {
                break;
            }

            // parse operator
            if ($tokens->is(Tokenizer::MACRO_BINARY)) {
                if ($tokens->is(Tokenizer::MACRO_COND)) {
                    if ($cond) {
                        break;
                    }
                    $cond = true;
                }
                $op = $tokens->getAndNext();
            } elseif ($tokens->is(Tokenizer::MACRO_EQUALS)) {
                if (!$var) {
                    break;
                }
                $op = $tokens->getAndNext();
            } elseif ($tokens->is(T_STRING)) {
                if (!$exp) {
                    break;
                }
                $operator = $tokens->current();
                if ($operator == "is") {
                    $item = array_pop($exp);
                    $exp[] = $this->parseIs($tokens, $item, $var);
                } elseif ($operator == "in" || ($operator == "not" && $tokens->isNextToken("in"))) {
                    $item = array_pop($exp);
                    $exp[] = $this->parseIn($tokens, $item, $var);
                } else {
                    break;
                }
            } elseif ($tokens->is('~')) {
                // string concat coming soon
            } else {
                break;
            }
            if ($op) {
                $exp[] = $op;
            }
        }

        if ($op) {
            throw new UnexpectedTokenException($tokens);
        }
        if ($required && !$exp) {
            throw new UnexpectedTokenException($tokens);
        }
        return implode(' ', $exp);
    }

    /**
     * Parse any term: -2, ++$var, 'adf'|mod:4
     *
     * @param Tokenizer $tokens
     * @param bool $is_var
     * @return bool|string
     * @throws Error\UnexpectedTokenException
     * @throws Error\TokenizeException
     * @throws \Exception
     */
    public function parseTerm(Tokenizer $tokens, &$is_var = false)
    {
        $is_var = false;
        $unary = "";
        term: {
        if ($tokens->is(T_LNUMBER, T_DNUMBER)) {
            return $unary . $this->parseScalar($tokens, true);
        } elseif ($tokens->is(T_CONSTANT_ENCAPSED_STRING, '"', T_ENCAPSED_AND_WHITESPACE)) {
            if ($unary) {
                throw new UnexpectedTokenException($tokens->back());
            }
            return $this->parseScalar($tokens, true);
        } elseif ($tokens->is(T_VARIABLE)) {
            $var = $this->parseVar($tokens);
            if ($tokens->is(Tokenizer::MACRO_INCDEC, "|", "!", "?")) {
                return $unary . $this->parseVariable($tokens, 0, $var);
            } elseif ($tokens->is("(") && $tokens->hasBackList(T_STRING)) { // method call
                return $unary . $this->parseVariable($tokens, 0, $var);
            } elseif ($unary) {
                return $unary . $var;
            } else {
                $is_var = true;
                return $var;
            }
        } elseif ($tokens->is(Tokenizer::MACRO_INCDEC)) {
            return $unary . $this->parseVariable($tokens);
        } elseif ($tokens->is("(")) {
            $tokens->next();
            $exp = $unary . "(" . $this->parseExp($tokens, true) . ")";
            $tokens->need(")")->next();
            return $exp;
        } elseif ($tokens->is(Tokenizer::MACRO_UNARY)) {
            if ($unary) {
                throw new UnexpectedTokenException($tokens);
            }
            $unary = $tokens->getAndNext();
            goto term;
        } elseif ($tokens->is(T_STRING)) {
            if ($tokens->isSpecialVal()) {
                return $unary . $tokens->getAndNext();
            } elseif ($tokens->isNext("(") && !$tokens->getWhitespace()) {
                $func = $this->_fenom->getModifier($tokens->current(), $this);
                if (!$func) {
                    throw new \Exception("Function " . $tokens->getAndNext() . " not found");
                }
                $tokens->next();
                $func = $func . $this->parseArgs($tokens);
                if ($tokens->is('|')) {
                    return $unary . $this->parseModifier($tokens, $func);
                } else {
                    return $unary . $func;
                }
            } else {
                return false;
            }
        } elseif ($tokens->is(T_ISSET, T_EMPTY)) {
            $func = $tokens->getAndNext();
            if ($tokens->is("(") && $tokens->isNext(T_VARIABLE)) {
                $tokens->next();
                $exp = $func . "(" . $this->parseVar($tokens) . ")";
                $tokens->need(')')->next();
                return $unary . $exp;
            } else {
                throw new TokenizeException("Unexpected token " . $tokens->getNext() . ", isset() and empty() accept only variables");
            }
        } elseif ($tokens->is('[')) {
            if ($unary) {
                throw new UnexpectedTokenException($tokens->back());
            }
            return $this->parseArray($tokens);
        } elseif ($unary) {
            $tokens->back();
            throw new UnexpectedTokenException($tokens);
        } else {
            return false;
        }
    }
    }

    /**
     * Parse simple variable (without modifier etc)
     *
     * @param Tokenizer $tokens
     * @param int $options
     * @return string
     */
    public function parseVar(Tokenizer $tokens, $options = 0)
    {
        $var = $tokens->get(T_VARIABLE);
        $_var = '$tpl["' . substr($var, 1) . '"]';
        $tokens->next();
        while ($t = $tokens->key()) {
            if ($t === "." && !($options & self::DENY_ARRAY)) {
                $key = $tokens->getNext();
                if ($tokens->is(T_VARIABLE)) {
                    $key = "[ " . $this->parseVariable($tokens, self::DENY_ARRAY) . " ]";
                } elseif ($tokens->is(Tokenizer::MACRO_STRING)) {
                    $key = '["' . $key . '"]';
                    $tokens->next();
                } elseif ($tokens->is(Tokenizer::MACRO_SCALAR, '"')) {
                    $key = "[" . $this->parseScalar($tokens, false) . "]";
                } else {
                    break;
                }
                $_var .= $key;
            } elseif ($t === "[" && !($options & self::DENY_ARRAY)) {
                $tokens->next();
                if ($tokens->is(Tokenizer::MACRO_STRING)) {
                    if ($tokens->isNext("(")) {
                        $key = "[" . $this->parseExp($tokens) . "]";
                    } else {
                        $key = '["' . $tokens->current() . '"]';
                        $tokens->next();
                    }
                } else {
                    $key = "[" . $this->parseExp($tokens, true) . "]";
                }
                $tokens->get("]");
                $tokens->next();
                $_var .= $key;
            } elseif ($t === T_DNUMBER) {
                $_var .= '[' . substr($tokens->getAndNext(), 1) . ']';
            } elseif ($t === T_OBJECT_OPERATOR) {
                $_var .= "->" . $tokens->getNext(T_STRING);
                $tokens->next();
            } else {
                break;
            }
        }
        if ($this->_options & Fenom::FORCE_VERIFY) {
            return 'isset(' . $_var . ') ? ' . $_var . ' : null';
        } else {
            return $_var;
        }
    }

    /**
     * Parse complex variable
     * $var.foo[bar]["a"][1+3/$var]|mod:3:"w":$var3|mod3
     * ++$var|mod
     * $var--|mod
     *
     * @see parseModifier
     * @static
     * @param Tokenizer $tokens
     * @param int $options set parser options
     * @param string $var already parsed plain variable
     * @throws \LogicException
     * @throws InvalidUsageException
     * @return string
     */
    public function parseVariable(Tokenizer $tokens, $options = 0, $var = null)
    {
        $stained = false;
        if (!$var) {
            if ($tokens->is(Tokenizer::MACRO_INCDEC)) {
                $stained = true;
                $var = $tokens->getAndNext() . $this->parseVar($tokens, $options);
            } else {
                $var = $this->parseVar($tokens, $options);
            }
            if ($tokens->is(T_OBJECT_OPERATOR)) { // parse
                $var .= '->' . $tokens->getNext(T_STRING);
                $tokens->next();
            }
        }

        if ($tokens->is("(") && $tokens->hasBackList(T_STRING, T_OBJECT_OPERATOR)) {
            if ($stained) {
                throw new InvalidUsageException("Can not increment or decrement of the method result");
            }
            if ($this->_options & Fenom::DENY_METHODS) {
                throw new \LogicException("Forbidden to call methods");
            }
            $var .= $this->parseArgs($tokens);
            $stained = true;
        }
        if ($tokens->is('?', '!')) {
            return $this->parseTernary($tokens, $var, $tokens->current());
        }
        if ($tokens->is(Tokenizer::MACRO_INCDEC)) {
            if ($stained) {
                throw new InvalidUsageException("Can not use two increments and/or decrements for one variable");
            }
            $var .= $tokens->getAndNext();
        }
        if ($tokens->is('|') && !($options & self::DENY_MODS)) {
            return $this->parseModifier($tokens, $var);
        }
        return $var;
    }

    /**
     * Parse ternary operator
     *
     * @param Tokenizer $tokens
     * @param $var
     * @param $type
     * @return string
     * @throws UnexpectedTokenException
     */
    public function parseTernary(Tokenizer $tokens, $var, $type)
    {
        $empty = ($type === "?");
        $tokens->next();
        if ($tokens->is(":")) {
            $tokens->next();
            if ($empty) {
                return '(empty(' . $var . ') ? (' . $this->parseExp($tokens, true) . ') : ' . $var . ')';
            } else {
                return '(isset(' . $var . ') ? ' . $var . ' : (' . $this->parseExp($tokens, true) . '))';
            }
        } elseif ($tokens->is(Tokenizer::MACRO_BINARY, Tokenizer::MACRO_BOOLEAN, Tokenizer::MACRO_MATH) || !$tokens->valid()) {
            if ($empty) {
                return '!empty(' . $var . ')';
            } else {
                return 'isset(' . $var . ')';
            }
        } else {
            $expr1 = $this->parseExp($tokens, true);
            if (!$tokens->is(":")) {
                throw new UnexpectedTokenException($tokens, null, "ternary operator");
            }
            $expr2 = $this->parseExp($tokens, true);
            if ($empty) {
                return '(empty(' . $var . ') ? ' . $expr2 . ' : ' . $expr1 . ')';
            } else {
                return '(isset(' . $var . ') ? ' . $expr1 . ' : ' . $expr2 . ')';
            }
        }
    }

    /**
     * Parse 'is' and 'is not' operators
     * @see $_checkers
     * @param Tokenizer $tokens
     * @param string $value
     * @param bool $variable
     * @throws InvalidUsageException
     * @return string
     */
    public function parseIs(Tokenizer $tokens, $value, $variable = false)
    {
        $tokens->next();
        if ($tokens->current() == 'not') {
            $invert = '!';
            $equal = '!=';
            $tokens->next();
        } else {
            $invert = '';
            $equal = '==';
        }
        if ($tokens->is(Tokenizer::MACRO_STRING)) {
            $action = $tokens->current();
            if (!$variable && ($action == "set" || $action == "empty")) {
                $action = "_$action";
                $tokens->next();
                return $invert . sprintf(self::$_checkers[$action], $value);
            } elseif (isset(self::$_checkers[$action])) {
                $tokens->next();
                return $invert . sprintf(self::$_checkers[$action], $value);
            } elseif ($tokens->isSpecialVal()) {
                $tokens->next();
                return '(' . $value . ' ' . $equal . '= ' . $action . ')';
            }
            return $invert . '(' . $value . ' instanceof \\' . $this->parseName($tokens) . ')';
        } elseif ($tokens->is(T_VARIABLE)) {
            return '(' . $value . ' ' . $equal . '= ' . $this->parseVariable($tokens) . ')';
        } elseif ($tokens->is(Tokenizer::MACRO_SCALAR)) {
            return '(' . $value . ' ' . $equal . '= ' . $this->parseScalar($tokens) . ')';
        } elseif ($tokens->is('[')) {
            return '(' . $value . ' ' . $equal . '= ' . $this->parseArray($tokens) . ')';
        } elseif ($tokens->is(T_NS_SEPARATOR)) { //
            return $invert . '(' . $value . ' instanceof \\' . $this->parseName($tokens) . ')';
        } else {
            throw new InvalidUsageException("Unknown argument");
        }
    }

    /**
     * Parse 'in' and 'not in' operators
     * @param Tokenizer $tokens
     * @param string $value
     * @throws InvalidUsageException
     * @throws UnexpectedTokenException
     * @return string
     */
    public function parseIn(Tokenizer $tokens, $value)
    {
        $checkers = array(
            "string" => 'is_int(strpos(%2$s, %1$s))',
            "list" => "in_array(%s, %s)",
            "keys" => "array_key_exists(%s, %s)",
            "auto" => '\Fenom\Modifier::in(%s, %s)'
        );
        $checker = null;
        $invert = '';
        if ($tokens->current() == 'not') {
            $invert = '!';
            $tokens->next();
        }
        if ($tokens->current() !== "in") {
            throw new UnexpectedTokenException($tokens);
        }
        $tokens->next();
        if ($tokens->is(Tokenizer::MACRO_STRING)) {
            $checker = $tokens->current();
            if (!isset($checkers[$checker])) {
                throw new UnexpectedTokenException($tokens);
            }
            $tokens->next();
        }
        if ($tokens->is('[')) {
            if ($checker == "string") {
                throw new InvalidUsageException("Can not use string operation for array");
            } elseif (!$checker) {
                $checker = "list";
            }
            return $invert . sprintf($checkers[$checker], $value, $this->parseArray($tokens));
        } elseif ($tokens->is('"', T_ENCAPSED_AND_WHITESPACE, T_CONSTANT_ENCAPSED_STRING)) {
            if (!$checker) {
                $checker = "string";
            } elseif ($checker != "string") {
                throw new InvalidUsageException("Can not use array operation for string");
            }
            return $invert . sprintf($checkers[$checker], "strval($value)", $this->parseScalar($tokens));
        } elseif ($tokens->is(T_VARIABLE, Tokenizer::MACRO_INCDEC)) {
            if (!$checker) {
                $checker = "auto";
            }
            return $invert . sprintf($checkers[$checker], $value, $this->parseVariable($tokens));
        } else {
            throw new UnexpectedTokenException($tokens);
        }
    }

    /**
     * Parse method, class or constant name
     *
     * @param Tokenizer $tokens
     * @return string
     */
    public function parseName(Tokenizer $tokens)
    {
        $tokens->skipIf(T_NS_SEPARATOR);
        $name = "";
        if ($tokens->is(T_STRING)) {
            $name .= $tokens->getAndNext();
            while ($tokens->is(T_NS_SEPARATOR)) {
                $name .= '\\' . $tokens->next()->get(T_STRING);
                $tokens->next();
            }
        }
        return $name;
    }

    /**
     * Parse scalar values
     *
     * @param Tokenizer $tokens
     * @param bool $allow_mods
     * @return string
     * @throws TokenizeException
     */
    public function parseScalar(Tokenizer $tokens, $allow_mods = true)
    {
        $_scalar = "";
        if ($token = $tokens->key()) {
            switch ($token) {
                case T_CONSTANT_ENCAPSED_STRING:
                case T_LNUMBER:
                case T_DNUMBER:
                    $_scalar .= $tokens->getAndNext();
                    break;
                case T_ENCAPSED_AND_WHITESPACE:
                case '"':
                    $_scalar .= $this->parseQuote($tokens);
                    break;
                default:
                    throw new TokenizeException("Unexpected scalar token '" . $tokens->current() . "'");
            }
            if ($allow_mods && $tokens->is("|")) {
                return $this->parseModifier($tokens, $_scalar);
            }
        }
        return $_scalar;
    }

    /**
     * Parse string with or without variable
     *
     * @param Tokenizer $tokens
     * @throws UnexpectedTokenException
     * @return string
     */
    public function parseQuote(Tokenizer $tokens)
    {
        if ($tokens->is('"', "`")) {
            $stop = $tokens->current();
            $_str = '"';
            $tokens->next();
            while ($t = $tokens->key()) {
                if ($t === T_ENCAPSED_AND_WHITESPACE) {
                    $_str .= $tokens->current();
                    $tokens->next();
                } elseif ($t === T_VARIABLE) {
                    if (strlen($_str) > 1) {
                        $_str .= '".';
                    } else {
                        $_str = "";
                    }
                    $_str .= '$tpl["' . substr($tokens->current(), 1) . '"]';
                    $tokens->next();
                    if ($tokens->is($stop)) {
                        $tokens->skip();
                        return $_str;
                    } else {
                        $_str .= '."';
                    }
                } elseif ($t === T_CURLY_OPEN) {
                    if (strlen($_str) > 1) {
                        $_str .= '".';
                    } else {
                        $_str = "";
                    }
                    $tokens->getNext(T_VARIABLE);
                    $_str .= '(' . $this->parseExp($tokens) . ')';
                    if ($tokens->is($stop)) {
                        $tokens->next();
                        return $_str;
                    } else {
                        $_str .= '."';
                    }
                } elseif ($t === "}") {
                    $tokens->next();
                } elseif ($t === $stop) {
                    $tokens->next();
                    return $_str . '"';
                } else {

                    break;
                }
            }
            throw new UnexpectedTokenException($tokens);
        } elseif ($tokens->is(T_CONSTANT_ENCAPSED_STRING)) {
            return $tokens->getAndNext();
        } elseif ($tokens->is(T_ENCAPSED_AND_WHITESPACE)) {
            throw new UnexpectedTokenException($tokens);
        } else {
            return "";
        }
    }

    /**
     * @param Tokenizer $tokens
     * @param null $first_member
     */
    public function parseConcat(Tokenizer $tokens, $first_member = null)
    {
        $concat = array();
        if ($first_member) {
        }
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
    public function parseModifier(Tokenizer $tokens, $value)
    {
        while ($tokens->is("|")) {
            $mods = $this->_fenom->getModifier($tokens->getNext(Tokenizer::MACRO_STRING), $this);
            if (!$mods) {
                throw new \Exception("Modifier " . $tokens->current() . " not found");
            }
            $tokens->next();
            $args = array();

            while ($tokens->is(":")) {
                $token = $tokens->getNext(Tokenizer::MACRO_SCALAR, T_VARIABLE, '"', Tokenizer::MACRO_STRING, "(", "[");

                if ($tokens->is(Tokenizer::MACRO_SCALAR) || $tokens->isSpecialVal()) {
                    $args[] = $token;
                    $tokens->next();
                } elseif ($tokens->is(T_VARIABLE)) {
                    $args[] = $this->parseVariable($tokens, self::DENY_MODS);
                } elseif ($tokens->is('"', '`', T_ENCAPSED_AND_WHITESPACE)) {
                    $args[] = $this->parseQuote($tokens);
                } elseif ($tokens->is('(')) {
                    $args[] = $this->parseExp($tokens, true);
                } elseif ($tokens->is('[')) {
                    $args[] = $this->parseArray($tokens);
                } elseif ($tokens->is(T_STRING) && $tokens->isNext('(')) {
                    $args[] = $tokens->getAndNext() . $this->parseArgs($tokens);
                } else {
                    break;
                }
            }

            if (!is_string($mods)) { // dynamic modifier
                $mods = 'call_user_func($tpl->getStorage()->getModifier("' . $mods . '"), ';
            } else {
                $mods .= "(";
            }
            if ($args) {
                $value = $mods . $value . ', ' . implode(", ", $args) . ')';
            } else {
                $value = $mods . $value . ')';
            }
        }
        return $value;
    }

    /**
     * Parse array
     * [1, 2.3, 5+7/$var, 'string', "str {$var+3} ing", $var2, []]
     *
     * @param Tokenizer $tokens
     * @throws UnexpectedTokenException
     * @return string
     */
    public function parseArray(Tokenizer $tokens)
    {
        if ($tokens->is("[")) {
            $_arr = "array(";
            $key = $val = false;
            $tokens->next();
            while ($tokens->valid()) {
                if ($tokens->is(',') && $val) {
                    $key = true;
                    $val = false;
                    $_arr .= $tokens->getAndNext() . ' ';
                } elseif ($tokens->is(Tokenizer::MACRO_SCALAR, T_VARIABLE, T_STRING, T_EMPTY, T_ISSET, "(", "#") && !$val) {
                    $_arr .= $this->parseExp($tokens, true);
                    $key = false;
                    $val = true;
                } elseif ($tokens->is('"') && !$val) {
                    $_arr .= $this->parseQuote($tokens);
                    $key = false;
                    $val = true;
                } elseif ($tokens->is(T_DOUBLE_ARROW) && $val) {
                    $_arr .= ' ' . $tokens->getAndNext() . ' ';
                    $key = true;
                    $val = false;
                } elseif (!$val && $tokens->is('[')) {
                    $_arr .= $this->parseArray($tokens);
                    $key = false;
                    $val = true;
                } elseif ($tokens->is(']') && !$key) {
                    $tokens->next();
                    return $_arr . ')';
                } else {
                    break;
                }
            }
        }
        throw new UnexpectedTokenException($tokens);
    }

    /**
     * Parse constant
     * #Ns\MyClass::CONST1, #CONST1, #MyClass::CONST1
     *
     * @param Tokenizer $tokens
     * @return string
     * @throws InvalidUsageException
     */
    public function parseConst(Tokenizer $tokens)
    {
        $tokens->get('#');
        $name = $tokens->getNext(T_STRING);
        $tokens->next();
        if ($tokens->is(T_NAMESPACE)) {
            $name .= '\\';
            $name .= $tokens->getNext(T_STRING);
            $tokens->next();
        }
        if ($tokens->is(T_DOUBLE_COLON)) {
            $name .= '::';
            $name .= $tokens->getNext(T_STRING);
            $tokens->next();
        }
        if (defined($name)) {
            return $name;
        } else {
            throw new InvalidUsageException("Use undefined constant $name");
        }
    }

    /**
     * @param Tokenizer $tokens
     * @param $name
     * @return string
     * @throws InvalidUsageException
     */
    public function parseMacroCall(Tokenizer $tokens, $name)
    {
        $recursive = false;
        $macro = false;
        if (isset($this->macros[$name])) {
            $macro = $this->macros[$name];
        } else {
            foreach ($this->_stack as $scope) {
                if ($scope->name == 'macro' && $scope['name'] == $name) { // invoke recursive
                    $recursive = $scope;
                    $macro = $scope['macro'];
                    break;
                }
            }
            if (!$macro) {
                throw new InvalidUsageException("Undefined macro '$name'");
            }
        }
        $tokens->next();
        $p = $this->parseParams($tokens);
        $args = array();
        foreach ($macro['args'] as $arg) {
            if (isset($p[$arg])) {
                $args[$arg] = $p[$arg];
            } elseif (isset($macro['defaults'][$arg])) {
                $args[$arg] = $macro['defaults'][$arg];
            } else {
                throw new InvalidUsageException("Macro '$name' require '$arg' argument");
            }
        }
        $args = $args ? '$tpl = ' . Compiler::toArray($args) . ';' : '';
        if ($recursive) {
            $n = $this->i++;
            $recursive['recursive'][] = $n;
            return '$stack_' . $macro['id'] . '[] = array("tpl" => $tpl, "mark" => ' . $n . '); ' . $args . ' goto macro_' . $macro['id'] . '; macro_' . $n . ':';
        } else {
            return '$_tpl = $tpl; ' . $args . ' ?>' . $macro["body"] . '<?php $tpl = $_tpl; unset($_tpl);';
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
    public function parseArgs(Tokenizer $tokens)
    {
        $_args = "(";
        $tokens->next();
        $arg = $colon = false;
        while ($tokens->valid()) {
            if (!$arg && $tokens->is(T_VARIABLE, T_STRING, "(", Tokenizer::MACRO_SCALAR, '"', Tokenizer::MACRO_UNARY, Tokenizer::MACRO_INCDEC)) {
                $_args .= $this->parseExp($tokens, true);
                $arg = true;
                $colon = false;
            } elseif (!$arg && $tokens->is('[')) {
                $_args .= $this->parseArray($tokens);
                $arg = true;
                $colon = false;
            } elseif ($arg && $tokens->is(',')) {
                $_args .= $tokens->getAndNext() . ' ';
                $arg = false;
                $colon = true;
            } elseif (!$colon && $tokens->is(')')) {
                $tokens->next();
                return $_args . ')';
            } else {
                break;
            }
        }

        throw new TokenizeException("Unexpected token '" . $tokens->current() . "' in argument list");
    }

    /**
     * Parse first unnamed argument
     *
     * @param Tokenizer $tokens
     * @param string $static
     * @return mixed|string
     */
    public function parsePlainArg(Tokenizer $tokens, &$static)
    {
        if ($tokens->is(T_CONSTANT_ENCAPSED_STRING)) {
            if ($tokens->isNext('|')) {
                return $this->parseExp($tokens, true);
            } else {
                $str = $tokens->getAndNext();
                $static = stripslashes(substr($str, 1, -1));
                return $str;
            }
        } elseif ($tokens->is(Tokenizer::MACRO_STRING)) {
            $static = $tokens->getAndNext();
            return '"' . addslashes($static) . '"';
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
     * @param array $defaults
     * @throws \Exception
     * @return array
     */
    public function parseParams(Tokenizer $tokens, array $defaults = null)
    {
        $params = array();
        while ($tokens->valid()) {
            if ($tokens->is(Tokenizer::MACRO_STRING)) {
                $key = $tokens->getAndNext();
                if ($defaults && !isset($defaults[$key])) {
                    throw new \Exception("Unknown parameter '$key'");
                }
                if ($tokens->is("=")) {
                    $tokens->next();
                    $params[$key] = $this->parseExp($tokens);
                } else {
                    $params[$key] = 'true';
                }
            } elseif ($tokens->is(Tokenizer::MACRO_SCALAR, '"', '`', T_VARIABLE, "[", '(')) {
                $params[] = $this->parseExp($tokens);
            } else {
                break;
            }
        }
        if ($defaults) {
            $params += $defaults;
        }

        return $params;
    }
}