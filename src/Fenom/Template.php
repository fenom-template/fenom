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

    protected static $_tests = array(
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
            $this->_body .= "<?php\n/* {$this->_name}:{$this->_line}: {$source} */\n $code ?>";
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
        "return new Fenom\\Render(\$fenom, " . $this->_getClosureSource() . ", array(\n" .
        "\t'options' => {$this->_options},\n" .
        "\t'provider' => " . var_export($this->_scm, true) . ",\n" .
        "\t'name' => " . var_export($this->_name, true) . ",\n" .
        "\t'base_name' => " . var_export($this->_base_name, true) . ",\n" .
        "\t'time' => {$this->_time},\n" .
        "\t'depends' => " . var_export($this->_depends, true) . ",\n" .
        "\t'macros' => " . $this->_getMacrosArray() . ",\n
        ));\n";
    }

    /**
     * Make array with macros code
     * @return string
     */
    private function _getMacrosArray()
    {
        if ($this->macros) {
            $macros = array();
            foreach ($this->macros as $m) {
                if ($m["recursive"]) {
                    $macros[] = "\t\t'" . $m["name"] . "' => function (\$tpl) {\n?>" . $m["body"] . "<?php\n}";
                }
            }
            return "array(\n" . implode(",\n", $macros) . ")";
        } else {
            return 'array()';
        }
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
            eval("\$this->_code = " . $this->_getClosureSource() . ";\n\$this->_macros = " . $this->_getMacrosArray() . ';');
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
            } else {
                return $this->out($this->parseExpr($tokens), $tokens);
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
            return $this->out($this->parseExpr($tokens)); // may be math and/or boolean expression
        }
        if ($tokens->is("(", T_NAMESPACE, T_DOUBLE_COLON) && !$tokens->isWhiteSpaced()) { // just invoke function or static method
            $tokens->back();
            return $this->out($this->parseExpr($tokens));
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
     * Parse expressions. The mix of operators and terms.
     *
     * @param Tokenizer $tokens
     * @throws Error\UnexpectedTokenException
     */
    public function parseExpr(Tokenizer $tokens)
    {
        $exp = array();
        $var = false; // last term was: true - variable, false - mixed
        $op = false; // last exp was operator
        $cond = false; // was comparison operator
        while ($tokens->valid()) {
            // parse term
            $term = $this->parseTerm($tokens, $var); // term of the expression
            if ($term !== false) {
                if($this->_options & Fenom::FORCE_VERIFY) {
                    $term = '(isset('.$term.') ? '.$term.' : null)';
                    $var = false;
                }
                if ($tokens->is('|')) {
                    $term = $this->parseModifier($tokens, $term);
                    $var = false;
                }
                if ($tokens->is('?', '!')) {
                    $term = $this->parseTernary($tokens, $term, $var);
                    $var = false;
                }
                $exp[] = $term;
                $op = false;
            } else {
                break;
            }
            if (!$tokens->valid()) {
                break;
            }
            // parse operator
            if ($tokens->is(Tokenizer::MACRO_BINARY)) { // binary operator: $a + $b, $a <= $b, ...
                if ($tokens->is(Tokenizer::MACRO_COND)) { // comparison operator
                    if ($cond) {
                        break;
                    }
                    $cond = true;
                } elseif ($tokens->is(Tokenizer::MACRO_BOOLEAN)) {
                    $cond = false;
                }
                $op = $tokens->getAndNext();
            } elseif ($tokens->is(Tokenizer::MACRO_EQUALS)) { // assignment operator: $a = 4, $a += 3, ...
                if (!$var) {
                    break;
                }
                $op = $tokens->getAndNext();
            } elseif ($tokens->is(T_STRING)) { // test or containment operator: $a in $b, $a is set, ...
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
            } elseif ($tokens->is('~')) { // string concatenation operator: 'asd' ~ $var
                $concat = array(array_pop($exp));
                while ($tokens->is('~')) {
                    $tokens->next();
                    if ($tokens->is(T_LNUMBER, T_DNUMBER)) {
                        $concat[] = "strval(" . $this->parseTerm($tokens) . ")";
                    } else {
                        $concat[] = $this->parseTerm($tokens);
                    }
                }
                $exp[] = "(" . implode(".", $concat) . ")";
            } else {
                break;
            }
            if ($op) {
                $exp[] = $op;
            }
        }

        if ($op || !$exp) {
            throw new UnexpectedTokenException($tokens);
        }
        return implode(' ', $exp);
    }

    /**
     * Parse any term of expression: -2, ++$var, 'adf'
     *
     * @param Tokenizer $tokens
     * @param bool $is_var is parsed term - plain variable
     * @throws Error\UnexpectedTokenException
     * @throws Error\TokenizeException
     * @throws \Exception
     * @return bool|string
     */
    public function parseTerm(Tokenizer $tokens, &$is_var = false)
    {
        $is_var = false;
        if ($tokens->is(Tokenizer::MACRO_UNARY)) {
            $unary = $tokens->getAndNext();
        } else {
            $unary = "";
        }
        if ($tokens->is(T_LNUMBER, T_DNUMBER)) {
            $code = $unary . $this->parseScalar($tokens, true);
        } elseif ($tokens->is(T_CONSTANT_ENCAPSED_STRING, '"', T_ENCAPSED_AND_WHITESPACE)) {
            if ($unary) {
                throw new UnexpectedTokenException($tokens->back());
            }
            $code = $this->parseScalar($tokens, true);
        } elseif ($tokens->is(T_VARIABLE)) {
            $code = $unary . $this->parseVariable($tokens);
            if ($tokens->is("(") && $tokens->hasBackList(T_STRING, T_OBJECT_OPERATOR)) {
                if ($this->_options & Fenom::DENY_METHODS) {
                    throw new \LogicException("Forbidden to call methods");
                }
                $code .= $this->parseArgs($tokens);
            } elseif ($tokens->is(Tokenizer::MACRO_INCDEC)) {
                $code .= $tokens->getAndNext();
            } else {
                $is_var = true;
            }
        } elseif ($tokens->is('$')) {
            $var = $this->parseAccessor($tokens, $is_var);
            $code = $unary . $var;
        } elseif ($tokens->is(Tokenizer::MACRO_INCDEC)) {
            $code = $unary . $tokens->getAndNext() . $this->parseVariable($tokens);
        } elseif ($tokens->is("(")) {
            $tokens->next();
            $code = $unary . "(" . $this->parseExpr($tokens) . ")";
            $tokens->need(")")->next();
        } elseif ($tokens->is(T_STRING)) {
            if ($tokens->isSpecialVal()) {
                $code = $unary . $tokens->getAndNext();
            } elseif ($tokens->isNext("(") && !$tokens->getWhitespace()) {
                $func = $this->_fenom->getModifier($tokens->current(), $this);
                if (!$func) {
                    throw new \Exception("Function " . $tokens->getAndNext() . " not found");
                }
                $code = $unary . $func . $this->parseArgs($tokens->next());
            } else {
                return false;
            }
        } elseif ($tokens->is(T_ISSET, T_EMPTY)) {
            $func = $tokens->getAndNext();
            if ($tokens->is("(") && $tokens->isNext(T_VARIABLE)) {
                $code = $unary . $func . "(" . $this->parseVariable($tokens->next()) . ")";
                $tokens->need(')')->next();
            } else {
                throw new TokenizeException("Unexpected token " . $tokens->getNext() . ", isset() and empty() accept only variables");
            }
        } elseif ($tokens->is('[')) {
            if ($unary) {
                throw new UnexpectedTokenException($tokens->back());
            }
            $code = $this->parseArray($tokens);
        } elseif ($unary) {
            throw new UnexpectedTokenException($tokens->back());
        } else {
            return false;
        }

        return $code;
    }

    /**
     * Parse variable name: $a, $a.b, $a.b[c]
     * @param Tokenizer $tokens
     * @param $var
     * @return string
     * @throws Error\UnexpectedTokenException
     */
    public function parseVariable(Tokenizer $tokens, $var = null)
    {
        if(!$var) {
            $var = '$tpl["' . substr( $tokens->get(T_VARIABLE), 1) . '"]';
            $tokens->next();
        }
        while ($t = $tokens->key()) {
            if ($t === ".") {
                $tokens->next();
                if ($tokens->is(T_VARIABLE)) {
                    $key = '[ $tpl["' . substr($tokens->getAndNext(), 1) . '"] ]';
                } elseif ($tokens->is(Tokenizer::MACRO_STRING)) {
                    $key = '["' . $tokens->getAndNext() . '"]';
                } elseif ($tokens->is(Tokenizer::MACRO_SCALAR)) {
                    $key = "[" . $tokens->getAndNext() . "]";
                } elseif ($tokens->is('"')) {
                    $key = "[" . $this->parseQuote($tokens) . "]";
                } else {
                    throw new UnexpectedTokenException($tokens);
                }
                $var .= $key;
            } elseif ($t === "[") {
                $tokens->next();
                if ($tokens->is(Tokenizer::MACRO_STRING)) {
                    if ($tokens->isNext("(")) {
                        $key = "[" . $this->parseExpr($tokens) . "]";
                    } else {
                        $key = '["' . $tokens->current() . '"]';
                        $tokens->next();
                    }
                } else {
                    $key = "[" . $this->parseExpr($tokens) . "]";
                }
                $tokens->get("]");
                $tokens->next();
                $var .= $key;
            } elseif ($t === T_DNUMBER) {
                $var .= '[' . substr($tokens->getAndNext(), 1) . ']';
            } elseif ($t === T_OBJECT_OPERATOR) {
                $var .= "->" . $tokens->getNext(T_STRING);
                $tokens->next();
            } else {
                break;
            }
        }
        return $var;
    }

    /**
     * Parse accessor
     */
    public function parseAccessor(Tokenizer $tokens, &$is_var)
    {
        $is_var = false;
        $vars = array(
            'get' => '$_GET',
            'post' => '$_POST',
            'session' => '$_SESSION',
            'cookie' => '$_COOKIE',
            'request' => '$_REQUEST',
            'files' => '$_FILES',
            'globals' => '$GLOBALS',
            'server' => '$_SERVER',
            'env' => '$_ENV',
            'tpl' => '$tpl->info'
        );
        if ($this->_options & Fenom::DENY_ACCESSOR) {
            throw new \LogicException("Accessor are disabled");
        }
        $key = $tokens->need('$')->next()->need('.')->next()->current();
        $tokens->next();
        if (isset($vars[$key])) {
            $is_var = true;
            return $this->parseVariable($tokens, $vars[$key]);
        }
        switch ($key) {
            case 'const':
                $tokens->need('.')->next();
                $var = $this->parseName($tokens);
                if (!defined($var)) {
                    $var = 'constant(' . var_export($var, true) . ')';
                }
                break;
            case 'version':
                $var = '\Fenom::VERSION';
                break;
            default:
                throw new UnexpectedTokenException($tokens);
        }

        return $var;
    }

    /**
     * Parse ternary operator
     *
     * @param Tokenizer $tokens
     * @param $var
     * @param $is_var
     * @return string
     * @throws UnexpectedTokenException
     */
    public function parseTernary(Tokenizer $tokens, $var, $is_var)
    {
        $empty = $tokens->is('?');
        $tokens->next();
        if ($tokens->is(":")) {
            $tokens->next();
            if ($empty) {
                if($is_var) {
                    return '(empty(' . $var . ') ? (' . $this->parseExpr($tokens) . ') : ' . $var . ')';
                } else {
                    return '(' . $var . ' ?: (' . $this->parseExpr($tokens) . ')';
                }
            } else {
                if($is_var) {
                    return '(isset(' . $var . ') ? ' . $var . ' : (' . $this->parseExpr($tokens) . '))';
                } else {
                    return '((' . $var . ' !== null) ? ' . $var . ' : (' . $this->parseExpr($tokens) . '))';
                }
            }
        } elseif ($tokens->is(Tokenizer::MACRO_BINARY, Tokenizer::MACRO_BOOLEAN, Tokenizer::MACRO_MATH) || !$tokens->valid()) {
            if ($empty) {
                if($is_var) {
                    return '!empty(' . $var . ')';
                } else {
                    return '(' . $var . ')';
                }
            } else {
                if($is_var) {
                    return 'isset(' . $var . ')';
                } else {
                    return '(' . $var . ' !== null)';
                }
            }
        } else {
            $expr1 = $this->parseExpr($tokens);
            $tokens->need(':')->skip();
            $expr2 = $this->parseExpr($tokens);
            if ($empty) {
                if($is_var) {
                    return '(empty(' . $var . ') ? ' . $expr2 . ' : ' . $expr1 . ')';
                } else {
                    return '(' . $var . ' ? ' . $expr1 . ' : ' . $expr2 . ')';
                }
            } else {
                if($is_var) {
                    return '(isset(' . $var . ') ? ' . $expr1 . ' : ' . $expr2 . ')';
                } else {
                    return '((' . $var . ' !== null) ? ' . $expr1 . ' : ' . $expr2 . ')';
                }
            }
        }
    }

    /**
     * Parse 'is' and 'is not' operators
     * @see _tests
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
                return $invert . sprintf(self::$_tests[$action], $value);
            } elseif (isset(self::$_tests[$action])) {
                $tokens->next();
                return $invert . sprintf(self::$_tests[$action], $value);
            } elseif ($tokens->isSpecialVal()) {
                $tokens->next();
                return '(' . $value . ' ' . $equal . '= ' . $action . ')';
            }
            return $invert . '(' . $value . ' instanceof \\' . $this->parseName($tokens) . ')';
        } elseif ($tokens->is(T_VARIABLE, '[', Tokenizer::MACRO_SCALAR, '"')) {
            return '(' . $value . ' ' . $equal . '= ' . $this->parseTerm($tokens) . ')';
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
            return $invert . sprintf($checkers[$checker], $value, $this->parseTerm($tokens));
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
     * @throws Error\UnexpectedTokenException
     * @return string
     */
    public function parseScalar(Tokenizer $tokens)
    {
        $token = $tokens->key();
        switch ($token) {
            case T_CONSTANT_ENCAPSED_STRING:
            case T_LNUMBER:
            case T_DNUMBER:
                return $tokens->getAndNext();
                break;
            case T_ENCAPSED_AND_WHITESPACE:
            case '"':
                return $this->parseQuote($tokens);
                break;
            case '$':
                $tokens->next()->need('.')->next()->need(T_CONST)->next();
                return 'constant('.$this->parseName($tokens).')';
            default:
                throw new UnexpectedTokenException($tokens);
        }
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
        if ($tokens->is('"')) {
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
                    $_str .= '(' . $this->parseExpr($tokens) . ')';
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
                if (!$args[] = $this->parseTerm($tokens->next())) {
                    throw new UnexpectedTokenException($tokens);
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
                    $_arr .= $this->parseExpr($tokens);
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
        $n = sprintf('%u_%d', crc32($this->_name), $this->i++);
        if ($recursive) {
            $recursive['recursive'] = true;
            $body = '$tpl->getMacro("' . $name . '")->__invoke($tpl);';
        } else {
            $body = '?>' . $macro["body"] . '<?php';
        }
        return '$_tpl' . $n . ' = $tpl->exchangeArray(' . Compiler::toArray($args) . ');' . PHP_EOL . $body . PHP_EOL . '$tpl->exchangeArray($_tpl' . $n . '); /* X */ unset($_tpl' . $n . ');';
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
                $_args .= $this->parseExpr($tokens);
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
                return $this->parseExpr($tokens);
            } else {
                $str = $tokens->getAndNext();
                $static = stripslashes(substr($str, 1, -1));
                return $str;
            }
        } elseif ($tokens->is(Tokenizer::MACRO_STRING)) {
            $static = $tokens->getAndNext();
            return '"' . addslashes($static) . '"';
        } else {
            return $this->parseExpr($tokens);
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
                    $params[$key] = $this->parseExpr($tokens);
                } else {
                    $params[$key] = 'true';
                }
            } elseif ($tokens->is(Tokenizer::MACRO_SCALAR, '"', T_VARIABLE, "[", '(')) {
                $params[] = $this->parseExpr($tokens);
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