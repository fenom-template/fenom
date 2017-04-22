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
use Fenom\Error\CompileException;
use Fenom\Error\InvalidUsageException;
use Fenom\Error\SecurityException;
use Fenom\Error\TokenizeException;
use Fenom\Error\UnexpectedTokenException;

/**
 * Template compiler
 *
 * @package    Fenom
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Template extends Render
{
    const VAR_NAME = '$var';
    const TPL_NAME = '$tpl';

    const COMPILE_STAGE_LOADED        = 1;
    const COMPILE_STAGE_PRE_FILTERED  = 2;
    const COMPILE_STAGE_PARSED        = 3;
    const COMPILE_STAGE_PROCESSED     = 4;
    const COMPILE_STAGE_POST_FILTERED = 5;

    /**
     * Disable array parser.
     */
    const DENY_ARRAY = 1;
    /**
     * Disable modifier parser.
     */
    const DENY_MODS = 2;
    /**
     * Allow parse modifiers with term
     */
    const TERM_MODS = 1;
    /**
     * Allow parse range with term
     */
    const TERM_RANGE = 1;
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

    /**
     * @var string|null
     */
    public $extends;

    /**
     * @var string|null
     */
    public $extended;

    /**
     * Stack of extended templates
     * @var array
     */
    public $ext_stack = array();

    public $extend_body = false;

    /**
     * Parent template
     * @var Template
     */
    public $parent;

    /**
     * Template PHP code
     * @var string
     */
    private $_body;
    private $_compile_stage = 0;

    /**
     * Call stack
     * @var Tag[]
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
     * @var bool|string
     */
    private $_ignore = false;

    private $_before = array();

    private $_filters = array();

    /**
     * @var int crc32 of the template name
     */
    private $_crc = 0;

    /**
     * @param Fenom $fenom Template storage
     * @param int $options
     * @param Template $parent
     */
    public function __construct(Fenom $fenom, $options, Template $parent = null)
    {
        $this->parent       = $parent;
        $this->_fenom       = $fenom;
        $this->_options     = $options;
        $this->_filters     = $this->_fenom->getFilters();
        $this->_tag_filters = $this->_fenom->getTagFilters();
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
     * @param string $tag
     * @return bool|\Fenom\Tag
     */
    public function getParentScope($tag)
    {
        for ($i = count($this->_stack) - 1; $i >= 0; $i--) {
            if ($this->_stack[$i]->name == $tag) {
                return $this->_stack[$i];
            }
        }

        return false;
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
        $this->_crc  = crc32($this->_name);
        if ($provider = strstr($name, ':', true)) {
            $this->_scm       = $provider;
            $this->_base_name = substr($name, strlen($provider) + 1);
        } else {
            $this->_base_name = $name;
        }
        $this->_provider = $this->_fenom->getProvider($provider);
        $this->_src      = $this->_provider->getSource($this->_base_name, $this->_time);
        $this->_compile_stage = self::COMPILE_STAGE_LOADED;
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
        $this->_src  = $src;
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
        foreach ($this->_fenom->getPreFilters() as $filter) {
            $this->_src = call_user_func($filter, $this, $this->_src);
        }
        $this->_compile_stage = self::COMPILE_STAGE_PRE_FILTERED;

        while (($start = strpos($this->_src, '{', $pos)) !== false) { // search open-symbol of tags
            switch (substr($this->_src, $start + 1, 1)) { // check next character
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
                        $end       = strpos($this->_src, '}', $end + 1); // search close-symbol of the tag
                        if ($end === false) { // if unexpected end of template
                            throw new CompileException("Unclosed tag in line {$this->_line}", 0, 1, $this->_name, $this->_line);
                        }
                        $tag = substr(
                            $this->_src,
                            $start + 1, // skip '{'
                            $end - $start - 1 // skip '}'
                        );

                        if ($this->_ignore) { // check ignore
                            if ($tag === '/' . $this->_ignore) { // turn off ignore
                                $this->_ignore = false;
                            } else { // still ignore
                                $this->_appendText('{' . $tag . '}');
                                continue;
                            }
                        }

                        if ($this->_tag_filters) {
                            foreach ($this->_tag_filters as $filter) {
                                $tag = call_user_func($filter, $tag, $this);
                            }
                        }
                        $tokens = new Tokenizer($tag); // tokenize the tag
                        if ($tokens->isIncomplete()) { // all strings finished?
                            $need_more = true;
                        } else {
                            $this->_appendCode($this->parseTag($tokens), '{' . $tag . '}'); // start the tag lexer
                            if ($tokens->key()) { // if tokenizer have tokens - throws exceptions
                                throw new CompileException("Unexpected token '" . $tokens->current() . "' in {$this} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line);
                            }
                        }
                    } while ($need_more);
                    unset($tag); // cleanup
                    break;
            }
            $pos = $end + 1; // move search-pointer to end of the tag
        }
        $this->_compile_stage = self::COMPILE_STAGE_PARSED;

        gc_collect_cycles();
        $this->_appendText(substr($this->_src, $end ? $end + 1 : 0)); // append tail of the template
        if ($this->_stack) {
            $_names = array();
            foreach ($this->_stack as $scope) {
                $_names[] = '{' . $scope->name . '} opened on line ' . $scope->line;
            }
            /* @var Tag $scope */
            $message = "Unclosed tag" . (count($_names) > 1 ? "s" : "") . ": " . implode(", ", $_names);
            throw new CompileException($message, 0, 1, $this->_name, $scope->line);
        }
        $this->_src = ""; // cleanup
        if ($this->_post) {
            foreach ($this->_post as $cb) {
                call_user_func_array($cb, array($this, &$this->_body));
            }
        }
        $this->_compile_stage = self::COMPILE_STAGE_PROCESSED;
        $this->addDepend($this); // for 'verify' performance
        foreach ($this->_fenom->getPostFilters() as $filter) {
            $this->_body = call_user_func($filter, $this, $this->_body);
        }
        $this->_compile_stage = self::COMPILE_STAGE_POST_FILTERED;
    }

    public function isStageDone($stage_no) {
        return $this->_compile_stage >= $stage_no;
    }

    /**
     * Set or unset the option
     * @param int $option
     * @param bool $value
     */
    public function setOption($option, $value)
    {
        if ($value) {
            $this->_options |= $option;
        } else {
            $this->_options &= ~$option;
        }
    }

    /**
     * Execute some code at loading cache
     * @param $code
     * @return void
     */
    public function before($code)
    {
        $this->_before[] = $code;
    }

    /**
     * Generate name of temporary internal template variable (may be random)
     * @return string
     */
    public function tmpVar()
    {
        return sprintf('$t%x_%x', $this->_crc ? $this->_crc : mt_rand(0, 0x7FFFFFFF), $this->i++);
    }

    /**
     * Append plain text to template body
     *
     * @param string $text
     */
    private function _appendText($text)
    {
        $this->_line += substr_count($text, "\n");
        $strip = $this->_options & Fenom::AUTO_STRIP;
        if ($this->_filters) {
            if (strpos($text, "<?") === false) {
                foreach ($this->_filters as $filter) {
                    $text = call_user_func($filter, $this, $text);
                }
            } else {
                $fragments = explode("<?", $text);
                foreach ($fragments as &$fragment) {
                    if ($fragment) {
                        foreach ($this->_filters as $filter) {
                            $fragment = call_user_func($filter, $this, $fragment);
                        }
                    }
                }
                $text = implode('<?php echo "<?"; ?>' . ($strip ? '' : PHP_EOL), $fragments);
            }
        } else {
            $text = str_replace("<?", '<?php echo "<?"; ?>' . ($strip ? '' : PHP_EOL), $text);
        }
        if ($strip) {
            $text = preg_replace('/\s+/uS', ' ', str_replace(array("\r", "\n"), " ", $text));
            $text = str_replace("> <", "><", $text);
        }
        $this->_body .= $text;
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
     * @param $tag_name
     */
    public function ignore($tag_name)
    {
        $this->_ignore = $tag_name;
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
        $before = $this->_before ? implode("\n", $this->_before) . "\n" : "";
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
            foreach ($this->macros as $name => $m) {
                if ($m["recursive"]) {
                    $macros[] = "\t\t'" . $name . "' => function (\$var, \$tpl) {\n?>" . $m["body"] . "<?php\n}";
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
        return "function (\$var, \$tpl) {\n?>{$this->_body}<?php\n}";
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
     * Add depends
     * @param Render $tpl
     */
    public function addDepend(Render $tpl)
    {
        $this->_depends[$tpl->getScm()][$tpl->getBaseName()] = $tpl->getTime();
    }

    /**
     * Output the value
     *
     * @param string $data
     * @param null|bool $escape
     * @return string
     */
    public function out($data, $escape = null)
    {
        if ($escape === null) {
            $escape = $this->_options & Fenom::AUTO_ESCAPE;
        }
        if ($escape) {
            return "echo htmlspecialchars($data, ENT_COMPAT, " . var_export(Fenom::$charset, true) . ");";
        } else {
            return "echo $data;";
        }
    }

    /**
     * Import block from another template
     * @param string $tpl
     */
    public function importBlocks($tpl)
    {
        $donor = $this->_fenom->compile($tpl, false);
        foreach ($donor->blocks as $name => $block) {
            if (!isset($this->blocks[$name])) {
                $block['import']     = $this->getName();
                $this->blocks[$name] = $block;
            }
        }
        $this->addDepend($donor);
    }

    /**
     * Extends the template
     * @param string $tpl
     * @return \Fenom\Template parent
     */
    public function extend($tpl)
    {
        if (!$this->isStageDone(self::COMPILE_STAGE_PARSED)) {
            $this->compile();
        }
        $parent           = $this->_fenom->getRawTemplate()->load($tpl, false);
        $parent->blocks   = &$this->blocks;
        $parent->macros   = &$this->macros;
        $parent->_before  = &$this->_before;
        $parent->extended = $this->getName();
        if (!$this->ext_stack) {
            $this->ext_stack[] = $this->getName();
        }
        $this->ext_stack[] = $parent->getName();
        $parent->_options  = $this->_options;
        $parent->ext_stack = $this->ext_stack;
        $parent->compile();
        $this->_body = $parent->_body;
        $this->_src  = $parent->_src;
        $this->addDepend($parent);
        return $parent;
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
                return $this->parseAct($tokens);
            } elseif ($tokens->is('/')) {
                return $this->parseEndTag($tokens);
            } else {
                return $this->out($this->parseExpr($tokens));
            }
        } catch (InvalidUsageException $e) {
            throw new CompileException($e->getMessage() . " in {$this->_name} line {$this->_line}", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\LogicException $e) {
            throw new SecurityException($e->getMessage() . " in {$this->_name} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\Exception $e) {
            throw new CompileException($e->getMessage() . " in {$this->_name} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
        } catch (\Throwable $e) {
            throw new CompileException($e->getMessage() . " in {$this->_name} line {$this->_line}, near '{" . $tokens->getSnippetAsString(0, 0) . "' <- there", 0, E_ERROR, $this->_name, $this->_line, $e);
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
        /** @var Tag $tag */
        $tag = array_pop($this->_stack);
        if ($tag->name !== $name) {
            throw new TokenizeException("Unexpected closing of the tag '$name' (expecting closing of the tag {$tag->name}, opened in line {$tag->line})");
        }
        return $tag->end($tokens);
    }

    /**
     * Parse action {action ...} or {action(...) ...}
     *
     * @static
     * @param Tokenizer $tokens
     * @throws \LogicException
     * @throws \RuntimeException
     * @throws Error\TokenizeException
     * @return string
     */
    public function parseAct(Tokenizer $tokens)
    {
        $action = $tokens->get(Tokenizer::MACRO_STRING);
        $tokens->next();
        if ($tokens->is("(", T_DOUBLE_COLON, T_NS_SEPARATOR) && !$tokens->isWhiteSpaced()
        ) { // just invoke function or static method
            $tokens->back();
            return $this->out($this->parseExpr($tokens));
        } elseif ($tokens->is('.')) {
            $name = $tokens->skip()->get(Tokenizer::MACRO_STRING);
            if ($action !== "macro") {
                $name = $action . "." . $name;
            }
            return $this->parseMacroCall($tokens, $name);
        }
        if ($info = $this->_fenom->getTag($action, $this)) {
            $tag = new Tag($action, $this, $info, $this->_body);
            if ($tokens->is(':')) { // parse tag options
                do {
                    $tag->tagOption($tokens->next()->need(T_STRING)->getAndNext());
                } while ($tokens->is(':'));
            }
            $code = $tag->start($tokens);
            if ($tag->isClosed()) {
                $tag->restoreAll();
            } else {
                array_push($this->_stack, $tag);
            }
            return $code;
        }

        for ($j = $i = count($this->_stack) - 1; $i >= 0; $i--) { // call function's internal tag
            if ($this->_stack[$i]->hasTag($action, $j - $i)) {
                return $this->_stack[$i]->tag($action, $tokens);
            }
        }
        if ($tags = $this->_fenom->getTagOwners($action)) { // unknown template tag
            throw new TokenizeException(
                "Unexpected tag '$action' (this tag can be used with '" . implode(
                    "', '",
                    $tags
                ) . "')"
            );
        } else {
            throw new TokenizeException("Unexpected tag '$action'");
        }
    }

    /**
     * Get current template line
     * @return int
     */
    public function getLine()
    {
        return $this->_line;
    }

    /**
     * Parse expressions. The mix of operators and terms.
     *
     * @param Tokenizer $tokens
     * @param bool $is_var
     * @throws \Exception
     * @return string
     */
    public function parseExpr(Tokenizer $tokens, &$is_var = false)
    {
        $exp  = array();
        $var  = false; // last term was: true - variable, false - mixed
        $op   = false; // last exp was operator
        $cond = false; // was comparison operator
        while ($tokens->valid()) {
            // parse term
            $term = $this->parseTerm($tokens, $var, -1); // term of the expression
            if ($term !== false) {
                if ($tokens->is('?', '!')) {
                    if ($cond) {
                        $term = array_pop($exp) . ' ' . $term;
                        $term = '(' . array_pop($exp) . ' ' . $term . ')';
                        $var  = false;
                    }
                    $term = $this->parseTernary($tokens, $term, $var);
                    $var  = false;
                }
                $exp[] = $term;
                $op    = false;
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
            } elseif ($tokens->is(Tokenizer::MACRO_EQUALS, '[')) { // assignment operator: $a = 4, $a += 3, ...
                if (!$var) {
                    break;
                }
                $op = $tokens->getAndNext();
                if ($op == '[') {
                    $tokens->need(']')->next()->need('=')->next();
                    $op = '[]=';
                }
            } elseif ($tokens->is(T_STRING)) { // test or containment operator: $a in $b, $a is set, ...
                if (!$exp) {
                    break;
                }
                $operator = $tokens->current();
                if ($operator == "is") {
                    $item  = array_pop($exp);
                    $exp[] = $this->parseIs($tokens, $item, $var);
                } elseif ($operator == "in" || ($operator == "not" && $tokens->isNextToken("in"))) {
                    $item  = array_pop($exp);
                    $exp[] = $this->parseIn($tokens, $item);
                } else {
                    break;
                }
            } elseif ($tokens->is('~')) { // string concatenation operator: 'asd' ~ $var
                if ($tokens->isNext('=')) { // ~=
                    $exp[] = ".=";
                    $tokens->next()->next();
                } else {
                    $concat = array(array_pop($exp));

                    while ($tokens->is('~')) {
                        $tokens->next();
                        if ($tokens->is(T_LNUMBER, T_DNUMBER)) {
                            $concat[] = "strval(" . $this->parseTerm($tokens) . ")";
                        } else {

                            if ($tokens->is('~')) {
                                $tokens->next();
                                $concat[] = "' '";
                            }
                            if (!$term2 = "strval(" . $this->parseTerm($tokens) . ")") {
                                throw new UnexpectedTokenException($tokens);
                            }
                            $concat[] = $term2;
                        }
                    }
                    $exp[] = "(" . implode(".", $concat) . ")";
                }
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

        if (count($exp) == 1 && $var) {
            $is_var = true;
        }
        return implode(' ', $exp);
    }

    /**
     * Parse any term of expression: -2, ++$var, 'adf'
     *
     * @param Tokenizer $tokens
     * @param bool $is_var is parsed term - plain variable
     * @param int $allows
     * @throws \Exception
     * @return bool|string
     */
    public function parseTerm(Tokenizer $tokens, &$is_var = false, $allows = -1)
    {
        $is_var = false;
        if ($tokens->is(Tokenizer::MACRO_UNARY)) {
            $unary = $tokens->getAndNext();
        } else {
            $unary = "";
        }
        switch ($tokens->key()) {
            case T_LNUMBER:
            case T_DNUMBER:
                $code = $unary . $this->parseScalar($tokens);
                break;
            case T_CONSTANT_ENCAPSED_STRING:
            case '"':
            case T_ENCAPSED_AND_WHITESPACE:
                if ($unary) {
                    throw new UnexpectedTokenException($tokens->back());
                }
                $code = $this->parseScalar($tokens);
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case '$':
                $code = $this->parseAccessor($tokens, $is_var);
                if (!$is_var) {
                    $code = $unary . $code;
                    break;
                }
            /* no break */
            case T_VARIABLE:
                if (!isset($code)) {
                    $code = $this->parseVariable($tokens);
                }
                if ($tokens->is("(") && $tokens->hasBackList(T_STRING, T_OBJECT_OPERATOR)) {
                    if ($this->_options & Fenom::DENY_METHODS) {
                        throw new \LogicException("Forbidden to call methods");
                    }
                    $code = $unary . $this->parseChain($tokens, $code);
                } elseif ($tokens->is(Tokenizer::MACRO_INCDEC)) {
                    if ($this->_options & Fenom::FORCE_VERIFY) {
                        $code = $unary . '(isset(' . $code . ') ? ' . $code . $tokens->getAndNext() . ' : null)';
                    } else {
                        $code = $unary . $code . $tokens->getAndNext();
                    }
                } else {
                    if ($this->_options & Fenom::FORCE_VERIFY) {
                        $code = $unary . '(isset(' . $code . ') ? ' . $code . ' : null)';
                    } else {
                        $is_var = true;
                        $code   = $unary . $code;
                    }
                }
                break;
            case T_DEC:
            case T_INC:
                if ($this->_options & Fenom::FORCE_VERIFY) {
                    $var  = $this->parseVariable($tokens);
                    $code = $unary . '(isset(' . $var . ') ? ' . $tokens->getAndNext() . $this->parseVariable($tokens) . ' : null)';
                } else {
                    $code = $unary . $tokens->getAndNext() . $this->parseVariable($tokens);
                }
                break;
            case '(':
                $tokens->next();
                $code = $unary . "(" . $this->parseExpr($tokens) . ")";
                $tokens->need(")")->next();
                break;
            case T_STRING:
                if ($tokens->isSpecialVal()) {
                    $code = $unary . $tokens->getAndNext();
                } elseif ($tokens->isNext("(") && !$tokens->getWhitespace()) {
                    $func = $this->_fenom->getModifier($modifier = $tokens->current(), $this);
                    if (!$func) {
                        throw new \Exception("Function " . $tokens->getAndNext() . " not found");
                    }
                    if (!is_string($func)) { // dynamic modifier
                        $call = 'call_user_func_array($tpl->getStorage()->getModifier("' . $modifier . '"), array' . $this->parseArgs($tokens->next()) . ')'; // @todo optimize
                    } else {
                        $call = $func . $this->parseArgs($tokens->next());
                    }
                    $code = $unary . $this->parseChain($tokens, $call);
                } elseif ($tokens->isNext(T_NS_SEPARATOR, T_DOUBLE_COLON)) {
                    $method = $this->parseStatic($tokens);
                    $args   = $this->parseArgs($tokens);
                    $code   = $unary . $this->parseChain($tokens, $method . $args);
                } else {
                    return false;
                }
                break;
            case T_ISSET:
            case T_EMPTY:
                $func = $tokens->getAndNext();
                if ($tokens->is("(") && $tokens->isNext(T_VARIABLE)) {
                    $code = $unary . $func . "(" . $this->parseVariable($tokens->next()) . ")";
                    $tokens->need(')')->next();
                } else {
                    throw new TokenizeException("Unexpected token " . $tokens->getNext() . ", isset() and empty() accept only variables");
                }
                break;
            case '[':
                if ($unary) {
                    throw new UnexpectedTokenException($tokens->back());
                }
                $code = $this->parseArray($tokens);
                break;
            default:
                if ($unary) {
                    throw new UnexpectedTokenException($tokens->back());
                } else {
                    return false;
                }
        }
        if (($allows & self::TERM_MODS) && $tokens->is('|')) {
            $code   = $this->parseModifier($tokens, $code);
            $is_var = false;
        }
        if (($allows & self::TERM_RANGE) && $tokens->is('.') && $tokens->isNext('.')) {
            $tokens->next()->next();
            $code   = '(new \Fenom\RangeIterator(' . $code . ', ' . $this->parseTerm($tokens, $var, self::TERM_MODS) . '))';
            $is_var = false;
        }
        return $code;
    }

    /**
     * Parse call-chunks: $var->func()->func()->prop->func()->...
     * @param Tokenizer $tokens
     * @param string $code start point (it is $var)
     * @return string
     */
    public function parseChain(Tokenizer $tokens, $code)
    {
        do {
            if ($tokens->is('(')) {
                $code .= $this->parseArgs($tokens);
            }
            if ($tokens->is(T_OBJECT_OPERATOR) && $tokens->isNext(T_STRING)) {
                $code .= '->' . $tokens->next()->getAndNext();
            }
        } while ($tokens->is('(', T_OBJECT_OPERATOR));

        return $code;
    }

    /**
     * Parse variable name: $a, $a.b, $a.b['c'], $a:index
     * @param Tokenizer $tokens
     * @param $var
     * @return string
     * @throws Error\UnexpectedTokenException
     */
    public function parseVariable(Tokenizer $tokens, $var = null)
    {
        if (!$var) {
            if ($tokens->isNext('@')) {
//                $v = $tokens->get(T_VARIABLE);
                $prop = $tokens->next()->next()->get(T_STRING);
                if ($tag = $this->getParentScope("foreach")) {
                    $tokens->next();
                    return Compiler::foreachProp($tag, $prop);
                } else {
                    throw new UnexpectedTokenException($tokens);
                }
            } else {
                $var = '$var["' . substr($tokens->get(T_VARIABLE), 1) . '"]';
                $tokens->next();
            }
        }
        while ($t = $tokens->key()) {
            if ($t === ".") {
                $tokens->next();
                if ($tokens->is(T_VARIABLE)) {
                    $key = '[ $var["' . substr($tokens->getAndNext(), 1) . '"] ]';
                } elseif ($tokens->is(Tokenizer::MACRO_STRING)) {
                    $key = '["' . $tokens->getAndNext() . '"]';
                } elseif ($tokens->is(Tokenizer::MACRO_SCALAR)) {
                    $key = "[" . $tokens->getAndNext() . "]";
                } elseif ($tokens->is('"')) {
                    $key = "[" . $this->parseQuote($tokens) . "]";
                } elseif ($tokens->is('.')) {
                    $tokens->back();
                    break;
                } else {
                    throw new UnexpectedTokenException($tokens);
                }
                $var .= $key;
            } elseif ($t === "[") {
                if ($tokens->isNext(']')) {
                    break;
                }
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
     * @param Tokenizer $tokens
     * @param bool $is_var
     * @return string
     */
    public function parseAccessor(Tokenizer $tokens, &$is_var = false)
    {
        $accessor = $tokens->need('$')->next()->need('.')->next()->current();
        $parser   = $this->getStorage()->getAccessor($accessor);
        $is_var   = false;
        if ($parser) {
            if (is_array($parser)) {
                if (isset($parser['callback'])) {
                    $tokens->next();
                    return 'call_user_func($tpl->getStorage()->getAccessor(' . var_export($accessor, true) .
                    ', "callback"), ' . var_export($accessor, true) . ', $tpl, $var)';
                } else {
                    return call_user_func_array(
                        $parser['parser'], array(
                        $parser['accessor'],
                        $tokens->next(),
                        $this,
                        &$is_var
                    )
                    );
                }
            } else {
                return call_user_func_array($parser, array($tokens->next(), $this, &$is_var));
            }
        } else {
            throw new \RuntimeException("Unknown accessor '\$.$accessor'");
        }
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
        if ($tokens->is(":", "?")) {
            $tokens->next();
            if ($empty) {
                if ($is_var) {
                    return '(empty(' . $var . ') ? (' . $this->parseExpr($tokens) . ') : ' . $var . ')';
                } else {
                    return '(' . $var . ' ?: (' . $this->parseExpr($tokens) . '))';
                }
            } else {
                if ($is_var) {
                    return '(isset(' . $var . ') ? ' . $var . ' : (' . $this->parseExpr($tokens) . '))';
                } else {
                    return '((' . $var . ' !== null) ? ' . $var . ' : (' . $this->parseExpr($tokens) . '))';
                }
            }
        } elseif ($tokens->is(
                Tokenizer::MACRO_BINARY,
                Tokenizer::MACRO_BOOLEAN,
                Tokenizer::MACRO_MATH
            ) || !$tokens->valid()
        ) {
            if ($empty) {
                if ($is_var) {
                    return '!empty(' . $var . ')';
                } else {
                    return '(' . $var . ')';
                }
            } else {
                if ($is_var) {
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
                if ($is_var) {
                    return '(empty(' . $var . ') ? ' . $expr2 . ' : ' . $expr1 . ')';
                } else {
                    return '(' . $var . ' ? ' . $expr1 . ' : ' . $expr2 . ')';
                }
            } else {
                if ($is_var) {
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
            $equal  = '!=';
            $tokens->next();
        } else {
            $invert = '';
            $equal  = '==';
        }
        if ($tokens->is(Tokenizer::MACRO_STRING)) {
            $action = $tokens->current();
            if (!$variable && ($action == "set" || $action == "empty")) {
                $action = "_$action";
                $tokens->next();
                return $invert . sprintf($this->_fenom->getTest($action), $value);
            } elseif ($test = $this->_fenom->getTest($action)) {
                $tokens->next();
                return $invert . sprintf($test, $value);
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
            "list"   => "in_array(%s, %s)",
            "keys"   => "array_key_exists(%s, %s)",
            "auto"   => '\Fenom\Modifier::in(%s, %s)'
        );
        $checker  = null;
        $invert   = '';
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
            case T_ENCAPSED_AND_WHITESPACE:
            case '"':
                return $this->parseQuote($tokens);
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
                    $_str .= '$var["' . substr($tokens->current(), 1) . '"]';
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
            $modifier = $tokens->getNext(Tokenizer::MACRO_STRING);
            if ($tokens->isNext(T_DOUBLE_COLON, T_NS_SEPARATOR)) {
                $mods = $this->parseStatic($tokens);
            } else {
                $mods = $this->_fenom->getModifier($modifier, $this);
                if (!$mods) {
                    throw new \Exception("Modifier " . $tokens->current() . " not found");
                }
                $tokens->next();
            }

            $args = array();
            while ($tokens->is(":")) {
                if (($args[] = $this->parseTerm($tokens->next(), $is_var, 0)) === false) {
                    throw new UnexpectedTokenException($tokens);
                }
            }

            if (!is_string($mods)) { // dynamic modifier
                $mods = 'call_user_func($tpl->getStorage()->getModifier("' . $modifier . '"), ';
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
     * @param int $count amount of elements
     * @throws Error\UnexpectedTokenException
     * @return string
     */
    public function parseArray(Tokenizer $tokens, &$count = 0)
    {
        if ($tokens->is("[")) {
            $arr = array();
            $tokens->next();
            while ($tokens->valid()) {
                if ($tokens->is(']')) {
                    $tokens->next();
                    return 'array(' . implode(', ', $arr) . ')';
                }
                if ($tokens->is('[')) {
                    $arr[] = $this->parseArray($tokens);
                    $count++;
                } else {
                    $expr = $this->parseExpr($tokens);
                    if ($tokens->is(T_DOUBLE_ARROW)) {
                        $tokens->next();
                        $arr[] = $expr . ' => ' . $this->parseExpr($tokens);
                    } else {
                        $arr[] = $expr;
                    }
                    $count++;
                }
                if ($tokens->is(',')) {
                    $tokens->next();
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
        $macro     = false;

        if (isset($this->macros[$name])) {
            $macro     = $this->macros[$name];
            $recursive = $macro['recursive'];
        } else {
            foreach ($this->_stack as $scope) {
                if ($scope->name == 'macro' && $scope['name'] == $name) { // invoke recursive
                    $recursive = $scope;
                    $macro     = $scope['macro'];
                    break;
                }
            }
            if (!$macro) {
                throw new InvalidUsageException("Undefined macro '$name'");
            }
        }
        $tokens->next();
        $p    = $this->parseParams($tokens);
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
        if ($recursive) {
            if ($recursive instanceof Tag) {
                $recursive['recursive'] = true;
            }
            return '$tpl->getMacro("' . $name . '")->__invoke(' . Compiler::toArray($args) . ', $tpl);';
        } else {
            $vars = $this->tmpVar();
            return $vars . ' = $var; $var = ' . Compiler::toArray($args) . ';' . PHP_EOL . '?>' .
            $macro["body"] . '<?php' . PHP_EOL . '$var = ' . $vars . '; unset(' . $vars . ');';
        }
    }

    /**
     * @param Tokenizer $tokens
     * @throws \LogicException
     * @throws \RuntimeException
     * @return string
     */
    public function parseStatic(Tokenizer $tokens)
    {
        if ($this->_options & Fenom::DENY_STATICS) {
            throw new \LogicException("Static methods are disabled");
        }
        $tokens->skipIf(T_NS_SEPARATOR);
        $name = "";
        if ($tokens->is(T_STRING)) {
            $name .= $tokens->getAndNext();
            while ($tokens->is(T_NS_SEPARATOR)) {
                $name .= '\\' . $tokens->next()->get(T_STRING);
                $tokens->next();
            }
        }
        $tokens->need(T_DOUBLE_COLON)->next()->need(T_STRING);
        $static = $name . "::" . $tokens->getAndNext();
        if (!is_callable($static)) {
            throw new \RuntimeException("Method $static doesn't exist");
        }
        return $static;
    }

    /**
     * Parse argument list
     * (1 + 2.3, 'string', $var, [2,4])
     *
     * @param Tokenizer $tokens
     * @return string
     */
    public function parseArgs(Tokenizer $tokens)
    {
        $_args = "(";
        $tokens->next();
        $arg = $colon = false;
        while ($tokens->valid()) {
            if (!$arg && $tokens->is(
                    T_VARIABLE,
                    T_STRING,
                    "$",
                    "(",
                    Tokenizer::MACRO_SCALAR,
                    '"',
                    Tokenizer::MACRO_UNARY,
                    Tokenizer::MACRO_INCDEC
                )
            ) {
                $_args .= $this->parseExpr($tokens);
                $arg   = true;
                $colon = false;
            } elseif (!$arg && $tokens->is('[')) {
                $_args .= $this->parseArray($tokens);
                $arg   = true;
                $colon = false;
            } elseif ($arg && $tokens->is(',')) {
                $_args .= $tokens->getAndNext() . ' ';
                $arg   = false;
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
                $str    = $tokens->getAndNext();
                $static = stripslashes(substr($str, 1, -1));
                return $str;
            }
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
                    throw new InvalidUsageException("Unknown parameter '$key'");
                }
                if ($tokens->is("=")) {
                    $tokens->next();
                    $params[$key] = $this->parseExpr($tokens);
                } else {
                    throw new InvalidUsageException("Invalid value for parameter '$key'");
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
