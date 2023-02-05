<?php
/*
 * This file is part of Fenom.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
use Fenom\Error\CompileException;
use Fenom\Provider;
use Fenom\ProviderInterface;
use Fenom\Render;
use Fenom\Template;

/**
 * Fenom Template Engine
 *
 *
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Fenom
{
    const VERSION = '2.12';
    const REV = 1;
    /* Actions */
    const INLINE_COMPILER = 1;
    const BLOCK_COMPILER  = 5;
    const INLINE_FUNCTION = 2;
    const BLOCK_FUNCTION  = 7;

    /* Options */
    const DENY_ACCESSOR     = 0x8;
    const DENY_METHODS      = 0x10;
    const DENY_NATIVE_FUNCS = 0x20;
    const FORCE_INCLUDE     = 0x40;
    const AUTO_RELOAD       = 0x80;
    const FORCE_COMPILE     = 0x100;
    const AUTO_ESCAPE       = 0x200;
    const DISABLE_CACHE     = 0x400;
    const FORCE_VERIFY      = 0x800;
    const AUTO_TRIM         = 0x1000; // reserved
    const DENY_PHP_CALLS    = 0x2000;
    const AUTO_STRIP        = 0x4000;
    /**
     * Use DENY_PHP_CALLS
     * @deprecated
     */
    const DENY_STATICS      = 0x2000;

    /* Default parsers */
    const DEFAULT_CLOSE_COMPILER = 'Fenom\Compiler::stdClose';
    const DEFAULT_FUNC_PARSER    = 'Fenom\Compiler::stdFuncParser';
    const DEFAULT_FUNC_OPEN      = 'Fenom\Compiler::stdFuncOpen';
    const DEFAULT_FUNC_CLOSE     = 'Fenom\Compiler::stdFuncClose';
    const SMART_FUNC_PARSER      = 'Fenom\Compiler::smartFuncParser';

    const MAX_MACRO_RECURSIVE = 32;

    const ACCESSOR_CUSTOM   = null;
    const ACCESSOR_VAR      = 'Fenom\Accessor::parserVar';
    const ACCESSOR_CALL     = 'Fenom\Accessor::parserCall';
    const ACCESSOR_PROPERTY = 'Fenom\Accessor::parserProperty';
    const ACCESSOR_METHOD   = 'Fenom\Accessor::parserMethod';
    const ACCESSOR_CHAIN    = 'Fenom\Accessor::parserChain';

    public static $charset = "UTF-8";

    /**
     * @var int maximum length of compiled filename (use sha1 of name if bigger)
     */
    public static $filename_length = 200;

    /**
     * @var int[] of possible options, as associative array
     * @see setOptions
     */
    private static $_options_list = [
        "disable_accessor"     => self::DENY_ACCESSOR,
        "disable_methods"      => self::DENY_METHODS,
        "disable_native_funcs" => self::DENY_NATIVE_FUNCS,
        "disable_cache"        => self::DISABLE_CACHE,
        "force_compile"        => self::FORCE_COMPILE,
        "auto_reload"          => self::AUTO_RELOAD,
        "force_include"        => self::FORCE_INCLUDE,
        "auto_escape"          => self::AUTO_ESCAPE,
        "force_verify"         => self::FORCE_VERIFY,
        "auto_trim"            => self::AUTO_TRIM,
        "disable_php_calls"    => self::DENY_PHP_CALLS,
        "disable_statics"      => self::DENY_STATICS,
        "strip"                => self::AUTO_STRIP,
    ];

    /**
     * @var callable[]
     */
    public array $pre_filters = [];

    /**
     * @var callable[]
     */
    public array $filters = [];

    /**
     * @var callable[]
     */
    public array $tag_filters = [];

    /**
     * @var string[]
     */
    public array $call_filters = [];

    /**
     * @var callable[]
     */
    public array $post_filters = [];

    /**
     * @var Fenom\Render[] Templates storage
     */
    protected array $_storage = [];

    /**
     * @var string compile directory
     */
    protected string $_compile_dir = "/tmp";

    /**
     * @var string compile prefix ID template
     */
    protected string $_compile_id;

    /**
     * @var string[] compile directory for custom provider
     */
    protected array $_compiles = [];

    /**
     * @var int masked options
     */
    protected int $_options = 0;

    /**
     * @var ProviderInterface
     */
    private ProviderInterface $_provider;
    /**
     * @var Fenom\ProviderInterface[]
     */
    protected array $_providers = [];

    /**
     * @var string[] list of modifiers [modifier_name => callable]
     */
    protected array $_modifiers = [
        "upper"       => 'strtoupper',
        "up"          => 'strtoupper',
        "lower"       => 'strtolower',
        "low"         => 'strtolower',
        "date_format" => 'Fenom\Modifier::dateFormat',
        "date"        => 'Fenom\Modifier::date',
        "truncate"    => 'Fenom\Modifier::truncate',
        "escape"      => 'Fenom\Modifier::escape',
        "e"           => 'Fenom\Modifier::escape', // alias of escape
        "unescape"    => 'Fenom\Modifier::unescape',
        "strip"       => 'Fenom\Modifier::strip',
        "length"      => 'Fenom\Modifier::length',
        "iterable"    => 'Fenom\Modifier::isIterable',
        "replace"     => 'Fenom\Modifier::replace',
        "ereplace"    => 'Fenom\Modifier::ereplace',
        "match"       => 'Fenom\Modifier::match',
        "ematch"      => 'Fenom\Modifier::ematch',
        "split"       => 'Fenom\Modifier::split',
        "esplit"      => 'Fenom\Modifier::esplit',
        "join"        => 'Fenom\Modifier::join',
        "in"          => 'Fenom\Modifier::in',
        "range"       => 'Fenom\Modifier::range',
    ];

    /**
     * @var array of allowed PHP functions
     */
    protected array $_allowed_funcs = [
        "count"       => 1,
        "is_string"   => 1,
        "is_array"    => 1,
        "is_numeric"  => 1,
        "is_int"      => 1,
        'constant'    => 1,
        "is_object"   => 1,
        "strtotime"   => 1,
        "gettype"     => 1,
        "is_double"   => 1,
        "json_encode" => 1,
        "json_decode" => 1,
        "ip2long"     => 1,
        "long2ip"     => 1,
        "strip_tags"  => 1,
        "nl2br"       => 1,
        "explode"     => 1,
        "implode"     => 1
    ];

    /**
     * @var string[] the disabled functions by `disable_functions` PHP's option
     */
    protected array $_disabled_funcs = [];

    /**
     * @var array[] of compilers and functions
     */
    protected array $_actions = [
        'foreach'    => [ // {foreach ...} {break} {continue} {foreachelse} {/foreach}
            'type'       => self::BLOCK_COMPILER,
            'open'       => 'Fenom\Compiler::foreachOpen',
            'close'      => 'Fenom\Compiler::foreachClose',
            'tags'       => [
                'foreachelse' => 'Fenom\Compiler::foreachElse',
                'break'       => 'Fenom\Compiler::tagBreak',
                'continue'    => 'Fenom\Compiler::tagContinue',
            ],
            'float_tags' => ['break' => 1, 'continue' => 1]
        ],
        'if'         => [ // {if ...} {elseif ...} {else} {/if}
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::ifOpen',
            'close' => 'Fenom\Compiler::stdClose',
            'tags'  => [
                'elseif' => 'Fenom\Compiler::tagElseIf',
                'else'   => 'Fenom\Compiler::tagElse'
            ]
        ],
        'switch'     => [ // {switch ...} {case ..., ...}  {default} {/switch}
            'type'       => self::BLOCK_COMPILER,
            'open'       => 'Fenom\Compiler::switchOpen',
            'close'      => 'Fenom\Compiler::switchClose',
            'tags'       => [
                'case'    => 'Fenom\Compiler::tagCase',
                'default' => 'Fenom\Compiler::tagDefault'
            ],
            'float_tags' => ['break' => 1]
        ],
        'for'        => [ // {for ...} {break} {continue} {/for}
            'type'       => self::BLOCK_COMPILER,
            'open'       => 'Fenom\Compiler::forOpen',
            'close'      => 'Fenom\Compiler::forClose',
            'tags'       => [
                'forelse'  => 'Fenom\Compiler::forElse',
                'break'    => 'Fenom\Compiler::tagBreak',
                'continue' => 'Fenom\Compiler::tagContinue',
            ],
            'float_tags' => ['break' => 1, 'continue' => 1]
        ],
        'while'      => [ // {while ...} {break} {continue} {/while}
            'type'       => self::BLOCK_COMPILER,
            'open'       => 'Fenom\Compiler::whileOpen',
            'close'      => 'Fenom\Compiler::stdClose',
            'tags'       => [
                'break'    => 'Fenom\Compiler::tagBreak',
                'continue' => 'Fenom\Compiler::tagContinue',
            ],
            'float_tags' => ['break' => 1, 'continue' => 1]
        ],
        'include'    => [ // {include ...}
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagInclude'
        ],
        'insert'     => [ // {include ...}
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagInsert'
        ],
        'var'       => [ // {var ...}
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::setOpen',
            'close' => 'Fenom\Compiler::setClose'
        ],
        'set'       => [ // {set ...}
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::setOpen',
            'close' => 'Fenom\Compiler::setClose'
        ],
        'add'       => [ // {add ...}
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::setOpen',
            'close' => 'Fenom\Compiler::setClose'
        ],
        'do'     => [ // {do ...}
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagDo'
        ],
        'block'      => [ // {block ...} {parent} {/block}
            'type'       => self::BLOCK_COMPILER,
            'open'       => 'Fenom\Compiler::tagBlockOpen',
            'close'      => 'Fenom\Compiler::tagBlockClose',
            'tags'       => ['parent' => 'Fenom\Compiler::tagParent'],
            'float_tags' => ['parent' => 1]
        ],
        'extends'    => [ // {extends ...}
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagExtends'
        ],
        'use'        => [ // {use}
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagUse'
        ],
        'filter'     => [ // {filter} ... {/filter}
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::filterOpen',
            'close' => 'Fenom\Compiler::filterClose'
        ],
        'macro'      => [
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::macroOpen',
            'close' => 'Fenom\Compiler::macroClose'
        ],
        'import'     => [
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagImport'
        ],
        'cycle'      => [
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagCycle'
        ],
        'raw'        => [
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagRaw'
        ],
        'autoescape' => [ // deprecated
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::escapeOpen',
            'close' => 'Fenom\Compiler::nope'
        ],
        'escape' => [
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::escapeOpen',
            'close' => 'Fenom\Compiler::nope'
        ],
        'strip' => [
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::stripOpen',
            'close' => 'Fenom\Compiler::nope'
        ],
        'ignore' => [
            'type'  => self::BLOCK_COMPILER,
            'open'  => 'Fenom\Compiler::ignoreOpen',
            'close' => 'Fenom\Compiler::nope'
        ],
        'unset'  => [
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagUnset'
        ],
        'paste'  => [ // {include ...}
            'type'   => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagPaste'
        ],
    ];

    /**
     * List of tests
     * @see https://github.com/bzick/fenom/blob/develop/docs/operators.md#test-operator
     * @var array
     */
    protected array $_tests = [
        'integer'  => 'is_int(%s)',
        'int'      => 'is_int(%s)',
        'float'    => 'is_float(%s)',
        'double'   => 'is_float(%s)',
        'decimal'  => 'is_float(%s)',
        'string'   => 'is_string(%s)',
        'bool'     => 'is_bool(%s)',
        'boolean'  => 'is_bool(%s)',
        'number'   => 'is_numeric(%s)',
        'numeric'  => 'is_numeric(%s)',
        'scalar'   => 'is_scalar(%s)',
        'object'   => 'is_object(%s)',
        'callable' => 'is_callable(%s)',
        'callback' => 'is_callable(%s)',
        'array'    => 'is_array(%s)',
        'iterable' => '\Fenom\Modifier::isIterable(%s)',
        'const'    => 'defined(%s)',
        'template' => '$tpl->getStorage()->templateExists(%s)',
        'empty'    => 'empty(%s)',
        'set'      => 'isset(%s)',
        '_empty'   => '!%s', // for none variable
        '_set'     => '(%s !== null)', // for none variable
        'odd'      => '(%s & 1)',
        'even'     => '!(%s %% 2)',
        'third'    => '!(%s %% 3)'
    ];

    protected array $_accessors = [
        'get'     => 'Fenom\Accessor::getVar',
        'env'     => 'Fenom\Accessor::getVar',
        'post'    => 'Fenom\Accessor::getVar',
        'request' => 'Fenom\Accessor::getVar',
        'cookie'  => 'Fenom\Accessor::getVar',
        'globals' => 'Fenom\Accessor::getVar',
        'server'  => 'Fenom\Accessor::getVar',
        'session' => 'Fenom\Accessor::getVar',
        'files'   => 'Fenom\Accessor::getVar',
        'tpl'     => 'Fenom\Accessor::tpl',
        'version' => 'Fenom\Accessor::version',
        'const'   => 'Fenom\Accessor::constant',
        'php'     => 'Fenom\Accessor::call',
        'call'    => 'Fenom\Accessor::call',
        'tag'     => 'Fenom\Accessor::Tag',
        'fetch'   => 'Fenom\Accessor::fetch',
        'block'   => 'Fenom\Accessor::block',
    ];

    /**
     * Just factory
     *
     * @param string|Fenom\ProviderInterface $source path to templates or custom provider
     * @param string $compile_dir path to compiled files
     * @param int|array $options
     * @throws InvalidArgumentException
     * @return Fenom
     */
    public static function factory(
        string|Fenom\ProviderInterface $source,
        string $compile_dir = '/tmp',
        int|array $options = 0
    ): Fenom
    {
        if (is_string($source)) {
            $provider = new Fenom\Provider($source);
        } elseif ($source instanceof ProviderInterface) {
            $provider = $source;
        } else {
            throw new InvalidArgumentException("Source must be a valid path or provider object");
        }
        $fenom = new static($provider);
        $fenom->setCompileDir($compile_dir);
        if ($options) {
            $fenom->setOptions($options);
        }
        return $fenom;
    }

    /**
     * @param Fenom\ProviderInterface $provider
     */
    public function __construct(Fenom\ProviderInterface $provider)
    {
        $this->_provider = $provider;
    }

    /**
     * Set compile directory
     *
     * @param string $dir directory to store compiled templates in
     * @throws LogicException
     * @return $this
     */
    public function setCompileDir(string $dir): static
    {
        if (!is_writable($dir)) {
            throw new LogicException("Cache directory $dir is not writable");
        }
        $this->_compile_dir = $dir;
        return $this;
    }

    /**
     * Set compile prefix ID template
     *
     * @param string $id prefix ID to store compiled templates
     * @return $this
     */
    public function setCompileId(string $id): static
    {
        $this->_compile_id = $id;
        return $this;
    }

    /**
     *
     * @param callable $cb
     * @return $this
     */
    public function addPreFilter(callable $cb): static
    {
        $this->pre_filters[] = $cb;
        return $this;
    }

    /**
     * @return callable[]
     */
    public function getPreFilters(): array
    {
        return $this->pre_filters;
    }

    /**
     *
     * @param callable $cb
     * @return $this
     */
    public function addPostFilter(callable $cb): static
    {
        $this->post_filters[] = $cb;
        return $this;
    }

    /**
     * @return callable[]
     */
    public function getPostFilters(): array
    {
        return $this->post_filters;
    }

    /**
     * @param callable $cb
     * @return self
     */
    public function addFilter(callable $cb): static
    {
        $this->filters[] = $cb;
        return $this;
    }


    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param callable $cb
     * @return self
     */
    public function addTagFilter(callable $cb): static
    {
        $this->tag_filters[] = $cb;
        return $this;
    }


    public function getTagFilters(): array
    {
        return $this->tag_filters;
    }

    /**
     * Add modifier
     *
     * @param string $modifier the modifier name
     * @param callable $callback the modifier callback
     * @return Fenom
     */
    public function addModifier(string $modifier, callable $callback): static
    {
        $this->_modifiers[$modifier] = $callback;
        return $this;
    }

    /**
     * Add inline tag compiler
     *
     * @param string $compiler
     * @param callable $parser
     * @return Fenom
     */
    public function addCompiler(string $compiler, callable $parser): static
    {
        $this->_actions[$compiler] = array(
            'type'   => self::INLINE_COMPILER,
            'parser' => $parser
        );
        return $this;
    }

    /**
     * @param string $compiler
     * @param string|object $storage
     * @return $this
     */
    public function addCompilerSmart(string $compiler, string|object $storage): static
    {
        if (method_exists($storage, "tag" . $compiler)) {
            $this->_actions[$compiler] = array(
                'type'   => self::INLINE_COMPILER,
                'parser' => array($storage, "tag" . $compiler)
            );
        }
        return $this;
    }

    /**
     * Add block compiler
     *
     * @param string $compiler
     * @param callable $open_parser
     * @param callable $close_parser
     * @param array $tags
     * @return Fenom
     */
    public function addBlockCompiler(
        string $compiler,
        callable $open_parser,
        callable $close_parser = self::DEFAULT_CLOSE_COMPILER,
        array $tags = []
    ): static
    {
        $this->_actions[$compiler] = array(
            'type'  => self::BLOCK_COMPILER,
            'open'  => $open_parser,
            'close' => $close_parser ? : self::DEFAULT_CLOSE_COMPILER,
            'tags'  => $tags,
        );
        return $this;
    }

    /**
     * @param string $compiler
     * @param string|object $storage
     * @param array $tags
     * @param array $floats
     * @throws LogicException
     * @return Fenom
     */
    public function addBlockCompilerSmart(
        string $compiler,
        string|object $storage,
        array $tags,
        array $floats = []
    ): static
    {
        $c = array(
            'type'       => self::BLOCK_COMPILER,
            "tags"       => array(),
            "float_tags" => array()
        );
        if (method_exists($storage, $compiler . "Open")) {
            $c["open"] = array($storage, $compiler . "Open");
        } else {
            throw new \LogicException("Open compiler {$compiler}Open not found");
        }
        if (method_exists($storage, $compiler . "Close")) {
            $c["close"] = array($storage, $compiler . "Close");
        } else {
            throw new \LogicException("Close compiler {$compiler}Close not found");
        }
        foreach ($tags as $tag) {
            if (method_exists($storage, "tag" . $tag)) {
                $c["tags"][$tag] = array($storage, "tag" . $tag);
                if ($floats && in_array($tag, $floats)) {
                    $c['float_tags'][$tag] = 1;
                }
            } else {
                throw new \LogicException("Tag compiler $tag (tag{$compiler}) not found");
            }
        }
        $this->_actions[$compiler] = $c;
        return $this;
    }

    /**
     * @param string $function
     * @param callable $callback
     * @param callable $parser
     * @return Fenom
     */
    public function addFunction(string $function, callable $callback, callable $parser = self::DEFAULT_FUNC_PARSER): static
    {
        $this->_actions[$function] = array(
            'type'     => self::INLINE_FUNCTION,
            'parser'   => $parser,
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param string $function
     * @param callable $callback
     * @return Fenom
     */
    public function addFunctionSmart(string $function, callable $callback): static
    {
        $this->_actions[$function] = array(
            'type'     => self::INLINE_FUNCTION,
            'parser'   => self::SMART_FUNC_PARSER,
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param string $function
     * @param callable $callback
     * @param callable $parser_open
     * @param callable $parser_close
     * @return Fenom
     */
    public function addBlockFunction(
        string $function,
        callable $callback,
        callable $parser_open = self::DEFAULT_FUNC_OPEN,
        callable $parser_close = self::DEFAULT_FUNC_CLOSE
    ): static
    {
        $this->_actions[$function] = array(
            'type'     => self::BLOCK_FUNCTION,
            'open'     => $parser_open,
            'close'    => $parser_close,
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param array $funcs
     * @return Fenom
     */
    public function addAllowedFunctions(array $funcs): static
    {
        $this->_allowed_funcs = $this->_allowed_funcs + array_flip($funcs);
        return $this;
    }

    /**
     * Add custom test
     * @param string $name test name
     * @param string $code test PHP code. Code may contain placeholder %s, which will be replaced by test-value. For example: is_callable(%s)
     */
    public function addTest(string $name, string $code): static
    {
        $this->_tests[$name] = $code;
        return $this;
    }

    /**
     * Get test code by name
     * @param string $name
     * @return string
     */
    public function getTest(string $name): string
    {
        return $this->_tests[$name] ?? "";
    }

    /**
     * Return modifier function
     *
     * @param string $modifier
     * @param Template|null $template
     * @return mixed
     */
    public function getModifier(string $modifier, Fenom\Template $template = null): static
    {
        if (isset($this->_modifiers[$modifier])) {
            return $this->_modifiers[$modifier];
        } elseif ($this->isAllowedFunction($modifier)) {
            return $modifier;
        } else {
            return $this->_loadModifier($modifier, $template);
        }
    }

    /**
     * Modifier autoloader
     * @param string $modifier
     * @param Fenom\Template $template
     * @return bool
     */
    protected function _loadModifier(string $modifier, Fenom\Template $template): bool
    {
        return false;
    }

    /**
     * Returns tag info
     *
     * @param string $tag
     * @param Template|null $template
     * @return array|null
     */
    public function getTag(string $tag, Template $template = null): ?array
    {
        if (isset($this->_actions[$tag])) {
            return $this->_actions[$tag];
        } else {
            return $this->_loadTag($tag, $template);
        }
    }

    /**
     * Tags autoloader
     * @param string $tag
     * @param Fenom\Template $template
     * @return array|null
     */
    protected function _loadTag(string $tag, Template $template): ?array
    {
        return null;
    }

    /**
     * Checks if is allowed PHP function for using in templates.
     *
     * @param string $function the function name
     * @return bool
     */
    public function isAllowedFunction(string $function): bool
    {
        $allow = ($this->_options & self::DENY_NATIVE_FUNCS)
            ? isset($this->_allowed_funcs[$function])
            : function_exists($function);
        return $allow && !in_array($function, $this->_getDisabledFuncs(), true);
    }

    /**
     * Returns the disabled PHP functions.
     *
     * @return string[]
     */
    protected function _getDisabledFuncs(): array
    {
        return $this->_disabled_funcs;
    }

    /**
     * @param string $tag
     * @return array
     */
    public function getTagOwners(string $tag): array
    {
        $tags = [];
        foreach ($this->_actions as $owner => $params) {
            if (isset($params["tags"][$tag])) {
                $tags[] = $owner;
            }
        }
        return $tags;
    }

    /**
     * Add source template provider by scheme
     *
     * @param string $scm scheme name
     * @param Fenom\ProviderInterface $provider provider object
     * @param string|null $compile_path
     * @return $this
     */
    public function addProvider(string $scm, ProviderInterface $provider, string $compile_path = null): static
    {
        $this->_providers[$scm] = $provider;
        if ($compile_path) {
            $this->_compiles[$scm] = $compile_path;
        }
        return $this;
    }

    /**
     * Set options
     * @param int|array $options
     * @return $this
     */
    public function setOptions(int|array $options): static
    {
        if (is_array($options)) {
            $options = self::_makeMask($options, self::$_options_list, $this->_options);
        }
        $this->_storage = array();
        $this->_options = $options;
        return $this;
    }

    /**
     * Get options as bits
     * @return int
     */
    public function getOptions(): int
    {
        return $this->_options;
    }

    /**
     * Add global accessor ($.)
     * @param string $name
     * @param callable $parser
     * @return Fenom
     */
    public function addAccessor(string $name, callable $parser): static
    {
        $this->_accessors[$name] = $parser;
        return $this;
    }

    /**
     * Add global accessor as PHP code ($.)
     * @param string $name
     * @param mixed $accessor
     * @param string $parser
     * @return Fenom
     */
    public function addAccessorSmart(string $name, mixed $accessor, string $parser = self::ACCESSOR_VAR): static
    {
        $this->_accessors[$name] = array(
            "accessor" => $accessor,
            "parser" => $parser,
        );
        return $this;
    }

    /**
     * Add global accessor handler as callback ($.X)
     * @param string $name
     * @param callable $callback
     * @return Fenom
     */
    public function addAccessorCallback(string $name, callable $callback): static
    {
        $this->_accessors[$name] = array(
            "callback" => $callback
        );
        return $this;
    }

    /**
     * Remove accessor
     * @param string $name
     * @return Fenom
     */
    public function removeAccessor(string $name): static
    {
        unset($this->_accessors[$name]);
        return $this;
    }

    /**
     * Get an accessor
     * @param string $name
     * @param string|null $key
     * @return callable|null
     */
    public function getAccessor(string $name,string $key = null): ?callable
    {
        if(isset($this->_accessors[$name])) {
            if($key) {
                return $this->_accessors[$name][$key];
            } else {
                return $this->_accessors[$name];
            }
        } else {
            return null;
        }
    }

    /**
     * Add filter for $.php accessor.
     * Uses glob syntax.
     * @param string $pattern
     * @return $this
     */
    public function addCallFilter(string $pattern): static
    {
        $this->call_filters[] = $pattern;
        return $this;
    }

    /**
     * @param string|null $scm
     * @return ProviderInterface
     * @throws InvalidArgumentException
     */
    public function getProvider(string $scm = null): Fenom\ProviderInterface
    {
        if ($scm) {
            if (isset($this->_providers[$scm])) {
                return $this->_providers[$scm];
            } else {
                throw new InvalidArgumentException("Provider for '$scm' not found");
            }
        } else {
            return $this->_provider;
        }
    }

    /**
     * Return empty template
     *
     * @return Fenom\Template
     */
    public function getRawTemplate(Template $parent = null): Template
    {
        return new Template($this, $this->_options, $parent);
    }

    /**
     * Execute template and write result into stdout
     *
     * @param string|array $template name of template.
     * If it is array of names of templates they will be extended from left to right.
     * @param array $vars array of data for template
     * @return Fenom\Render
     * @throws CompileException
     */
    public function display(string|array $template, array $vars = array()): Fenom\Render
    {
        return $this->getTemplate($template)->display($vars);
    }

    /**
     *
     * @param array|string $template name of template.
     * If it is array of names of templates they will be extended from left to right.
     * @param array $vars array of data for template
     * @return mixed
     * @throws Exception
     */
    public function fetch(array|string $template, array $vars = array()): mixed
    {
        return $this->getTemplate($template)->fetch($vars);
    }

    /**
     * Creates pipe-line of template's data to callback
     * @note Method not works correctly in old PHP 5.3.*
     * @param array|string $template name of the template.
     * If it is array of names of templates they will be extended from left to right.
     * @param callable $callback template's data handler
     * @param array $vars
     * @param int $chunk amount of bytes of chunk
     * @return array
     * @throws CompileException
     */
    public function pipe(array|string $template, callable $callback, array $vars = array(), int $chunk = 1_000_000): array
    {
        ob_start($callback, $chunk, PHP_OUTPUT_HANDLER_STDFLAGS);
        $data = $this->getTemplate($template)->display($vars);
        ob_end_flush();
        return $data;
    }

    /**
     * Get template by name
     *
     * @param string|array $template template name with schema
     * @param int $options additional options and flags
     * @return Fenom\Render
     * @throws CompileException
     */
    public function getTemplate(string|array $template, int $options = 0): Render
    {
        $options |= $this->_options;
        if (is_array($template)) {
            $key = $options . "@" . implode(",", $template);
        } else {
            $key = $options . "@" . $template;
        }
        if (isset($this->_storage[$key])) {
            /** @var Fenom\Template $tpl */
            $tpl = $this->_storage[$key];
            if (($this->_options & self::AUTO_RELOAD) && !$tpl->isValid()) {
                return $this->_storage[$key] = $this->compile($template, true, $options);
            } else {
                return $tpl;
            }
        } elseif ($this->_options & (self::FORCE_COMPILE |  self::DISABLE_CACHE)) {
            return $this->compile($template, !($this->_options & self::DISABLE_CACHE), $options);
        } else {
            return $this->_storage[$key] = $this->_load($template, $options);
        }
    }

    /**
     * Check if template exists
     * @param string $template
     * @return bool
     */
    public function templateExists(string $template): bool
    {
        $key = $this->_options . "@" . $template;
        if (isset($this->_storage[$key])) { // already loaded
            return true;
        }
        if ($provider = strstr($template, ":", true)) {
            if (isset($this->_providers[$provider])) {
                return $this->_providers[$provider]->templateExists(substr($template, strlen($provider) + 1));
            }
        } else {
            return $this->_provider->templateExists($template);
        }
        return false;
    }

    /**
     * Load template from cache or create cache if it doesn't exists.
     *
     * @param string $template
     * @param int $opts
     * @return Fenom\Render
     * @throws CompileException
     */
    protected function _load(string $template, int $opts): Render
    {
        $file_name = $this->getCompileName($template, $opts);
        if (is_file($this->_compile_dir . "/" . $file_name)) {
            $fenom = $this; // used in template
            $_tpl  = include($this->_compile_dir . "/" . $file_name);
            /* @var Fenom\Render $_tpl */

            if (!($this->_options & self::AUTO_RELOAD) || ($this->_options & self::AUTO_RELOAD)
                && $_tpl instanceof Fenom\Render
                && $_tpl->isValid()) {
                return $_tpl;
            }
        }
        return $this->compile($template, true, $opts);
    }

    /**
     * Generate unique name of compiled template
     *
     * @param string|string[] $tpl
     * @param int $options additional options
     * @return string
     */
    public function getCompileName(array|string $tpl, int $options = 0): string
    {
        $options = $this->_options | $options;
        if (is_array($tpl)) {
            $hash = implode(".", $tpl) . ":" . $options;
            foreach ($tpl as &$t) {
                $t = urlencode(str_replace(":", "_", basename($t)));
            }
            $tpl = implode("~", $tpl);
        } else {
            $hash = $tpl . ":" . $options;
            $tpl = urlencode(str_replace(":", "_", basename($tpl)));
        }
        if($tpl > self::$filename_length) {
            $tpl = sha1($tpl);
        }
        return $this->_compile_id . $tpl . "." . sprintf("%x.%x.php", crc32($hash), strlen($hash));
    }

    /**
     * Compile and save template
     *
     * @param array|string $tpl
     * @param bool $store store template on disk
     * @param int $options
     * @return Template
     * @throws CompileException
     */
    public function compile(array|string $tpl, bool $store = true, int $options = 0): Template
    {
        if (is_string($tpl)) {
            $template = $this->getRawTemplate()->load($tpl);
        } else {
            $template = $this->getRawTemplate()->load($tpl[0], false);
            for($i = 1; $i < count($tpl); $i++) {
                $template->extend($tpl[ $i ]);
            }
        }
        if ($store) {
            $cache_name   = $this->getCompileName($tpl, $options);
            $compile_path = $this->_compile_dir . "/" . $cache_name . "." . mt_rand(0, 100000) . ".tmp";
            if(!file_put_contents($compile_path, $template->getTemplateCode())) {
                throw new CompileException("Can't to write to the file $compile_path. Directory " . $this->_compile_dir . " is writable?");
            }
            $cache_path = $this->_compile_dir . "/" . $cache_name;
            if (!rename($compile_path, $cache_path)) {
                unlink($compile_path);
                throw new CompileException("Can't to move the file $compile_path -> $cache_path");
            }
        }
        return $template;
    }

    /**
     * Flush internal template in-memory-cache
     */
    public function flush(): void
    {
        $this->_storage = [];
    }

    /**
     * Remove all compiled templates
     */
    public function clearAllCompiles(): void
    {
        Provider::clean($this->_compile_dir);
        $this->flush();
    }

    /**
     * Compile code to template
     *
     * @param string $code
     * @param string $name
     * @return Fenom\Template
     */
    public function compileCode(string $code, string $name = 'Runtime compile'): Template
    {
        return $this->getRawTemplate()->source($name, $code);
    }


    /**
     * Create bit-mask from associative array use fully associative array possible keys with bit values
     * @static
     * @param array $values custom assoc array, ["a" => true, "b" => false]
     * @param array $options possible values, ["a" => 0b001, "b" => 0b010, "c" => 0b100]
     * @param int $mask the initial value of the mask
     * @return int result, ( $mask | a ) & ~b
     * @throws \RuntimeException if key from custom assoc doesn't exist into possible values
     */
    private static function _makeMask(array $values, array $options, int $mask = 0): int
    {
        foreach ($values as $key => $value) {
            if (isset($options[$key])) {
                if ($value) {
                    $mask |= $options[$key];
                } else {
                    $mask &= ~$options[$key];
                }
            } else {
                throw new \RuntimeException("Undefined parameter $value");
            }
        }
        return $mask;
    }
}
