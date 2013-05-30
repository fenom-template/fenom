<?php
/*
 * This file is part of Cytro.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
use Cytro\Template,
    Cytro\ProviderInterface;

/**
 * Cytro Template Engine
 */
class Cytro {
    const VERSION = '1.0.1';

    const INLINE_COMPILER   = 1;
    const BLOCK_COMPILER    = 2;
    const INLINE_FUNCTION   = 3;
    const BLOCK_FUNCTION    = 4;
    const MODIFIER          = 5;

    const DENY_METHODS      = 0x10;
    const DENY_INLINE_FUNCS = 0x20;
    const FORCE_INCLUDE     = 0x40;

    const AUTO_RELOAD       = 0x80;
    const FORCE_COMPILE     = 0xF0;
    const DISABLE_CACHE     = 0x1F0;

    const DEFAULT_CLOSE_COMPILER = 'Cytro\Compiler::stdClose';
    const DEFAULT_FUNC_PARSER    = 'Cytro\Compiler::stdFuncParser';
    const DEFAULT_FUNC_OPEN      = 'Cytro\Compiler::stdFuncOpen';
    const DEFAULT_FUNC_CLOSE     = 'Cytro\Compiler::stdFuncClose';
    const SMART_FUNC_PARSER      = 'Cytro\Compiler::smartFuncParser';

    /**
     * @var int[] of possible options, as associative array
     * @see setOptions, addOptions, delOptions
     */
    private static $_option_list = array(
        "disable_methods" => self::DENY_METHODS,
        "disable_native_funcs" => self::DENY_INLINE_FUNCS,
        "disable_cache" => self::DISABLE_CACHE,
        "force_compile" => self::FORCE_COMPILE,
        "auto_reload" => self::AUTO_RELOAD,
        "force_include" => self::FORCE_INCLUDE,
    );

    /**
     * @var Cytro\Render[] Templates storage
     */
    protected $_storage = array();
    /**
     * @var string compile directory
     */
    protected $_compile_dir = "/tmp";

    /**
     * @var int masked options
     */
    protected $_options = 0;

    protected $_on_pre_cmp = array();
    protected $_on_cmp = array();
    protected $_on_post_cmp = array();

    /**
     * @var ProviderInterface
     */
    private $_provider;
    /**
     * @var Cytro\ProviderInterface[]
     */
    protected $_providers = array();

    /**
     * @var string[] list of modifiers [modifier_name => callable]
     */
    protected $_modifiers = array(
        "upper"       => 'strtoupper',
        "up"          => 'strtoupper',
        "lower"       => 'strtolower',
        "low"         => 'strtolower',
        "date_format" => 'Cytro\Modifier::dateFormat',
        "date"        => 'Cytro\Modifier::date',
        "truncate"    => 'Cytro\Modifier::truncate',
        "escape"      => 'Cytro\Modifier::escape',
        "e"           => 'Cytro\Modifier::escape', // alias of escape
        "unescape"    => 'Cytro\Modifier::unescape',
        "strip"       => 'Cytro\Modifier::strip',
        "length"      => 'Cytro\Modifier::length',
        "default"     => 'Cytro\Modifier::defaultValue'
    );

    /**
     * @var array of allowed PHP functions
     */
    protected $_allowed_funcs = array(
        "count" => 1, "is_string" => 1, "is_array" => 1, "is_numeric" => 1, "is_int" => 1,
        "is_object" => 1, "strtotime" => 1, "gettype" => 1, "is_double" => 1, "json_encode" => 1, "json_decode" => 1,
        "ip2long" => 1, "long2ip" => 1, "strip_tags" => 1, "nl2br" => 1, "explode" => 1, "implode" => 1
    );

    /**
     * @var array[] of compilers and functions
     */
    protected $_actions = array(
        'foreach' => array( // {foreach ...} {break} {continue} {foreachelse} {/foreach}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::foreachOpen',
            'close' => 'Cytro\Compiler::foreachClose',
            'tags' => array(
                'foreachelse' => 'Cytro\Compiler::foreachElse',
                'break' => 'Cytro\Compiler::tagBreak',
                'continue' => 'Cytro\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'if' => array(      // {if ...} {elseif ...} {else} {/if}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::ifOpen',
            'close' => 'Cytro\Compiler::stdClose',
            'tags' => array(
                'elseif' => 'Cytro\Compiler::tagElseIf',
                'else' => 'Cytro\Compiler::tagElse',
            )
        ),
        'switch' => array(  // {switch ...} {case ...} {break} {default} {/switch}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::switchOpen',
            'close' => 'Cytro\Compiler::stdClose',
            'tags' => array(
                'case' => 'Cytro\Compiler::tagCase',
                'default' => 'Cytro\Compiler::tagDefault',
                'break' => 'Cytro\Compiler::tagBreak',
            ),
            'float_tags' => array('break' => 1)
        ),
        'for' => array(     // {for ...} {break} {continue} {/for}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::forOpen',
            'close' => 'Cytro\Compiler::forClose',
            'tags' => array(
                'forelse' => 'Cytro\Compiler::forElse',
                'break' => 'Cytro\Compiler::tagBreak',
                'continue' => 'Cytro\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'while' => array(   // {while ...} {break} {continue} {/while}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::whileOpen',
            'close' => 'Cytro\Compiler::stdClose',
            'tags' => array(
                'break' => 'Cytro\Compiler::tagBreak',
                'continue' => 'Cytro\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'include' => array( // {include ...}
            'type' => self::INLINE_COMPILER,
            'parser' => 'Cytro\Compiler::tagInclude'
        ),
        'var' => array(     // {var ...}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::varOpen',
            'close' => 'Cytro\Compiler::varClose'
        ),
        'block' => array(   // {block ...} {parent} {/block}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::tagBlockOpen',
            'close' => 'Cytro\Compiler::tagBlockClose',
            'tags' => array(
                'parent' => 'Cytro\Compiler::tagParent'
            ),
            'float_tags' => array('parent' => 1)
        ),
        'extends' => array( // {extends ...}
            'type' => self::INLINE_COMPILER,
            'parser' => 'Cytro\Compiler::tagExtends'
        ),
        'use' => array( // {use}
            'type' => self::INLINE_COMPILER,
            'parser' => 'Cytro\Compiler::tagUse'
        ),
        'capture' => array( // {capture ...} {/capture}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::captureOpen',
            'close' => 'Cytro\Compiler::captureClose'
        ),
        'filter' => array( // {filter} ... {/filter}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::filterOpen',
            'close' => 'Cytro\Compiler::filterClose'
        ),
        'macro' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Cytro\Compiler::macroOpen',
            'close' => 'Cytro\Compiler::macroClose'
        ),
        'import' => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Cytro\Compiler::tagImport'
        )
    );

    /**
     * Just factory
     *
     * @param string|Cytro\ProviderInterface $source path to templates or custom provider
     * @param string $compile_dir path to compiled files
     * @param int $options
     * @throws InvalidArgumentException
     * @return Cytro
     */
    public static function factory($source, $compile_dir = '/tmp', $options = 0) {
        if(is_string($source)) {
            $provider = new Cytro\FSProvider($source);
        } elseif($source instanceof ProviderInterface) {
            $provider = $source;
        } else {
            throw new InvalidArgumentException("Source must be a valid path or provider object");
        }
        $cytro = new static($provider);
	    /* @var Cytro $cytro */
        $cytro->setCompileDir($compile_dir);
        if($options) {
            $cytro->setOptions($options);
        }
        return $cytro;
    }

    /**
     * @param Cytro\ProviderInterface $provider
     */
    public function __construct(Cytro\ProviderInterface $provider) {
        $this->_provider = $provider;
    }

    /**
     * Set compile directory
     *
     * @param string $dir directory to store compiled templates in
     * @return Cytro
     */
    public function setCompileDir($dir) {
        $this->_compile_dir = $dir;
        return $this;
    }

    /**
     *
     * @param callable $cb
     */
    public function addPreCompileFilter($cb) {
        $this->_on_pre_cmp[] = $cb;
    }

    /**
     *
     * @param callable $cb
     */
    public function addPostCompileFilter($cb) {
        $this->_on_post_cmp[] = $cb;
    }

    /**
     * @param callable $cb
     */
    public function addCompileFilter($cb) {
        $this->_on_cmp[] = $cb;
    }

    /**
     * Add modifier
     *
     * @param string $modifier the modifier name
     * @param string $callback the modifier callback
     * @return Cytro
     */
    public function addModifier($modifier, $callback) {
        $this->_modifiers[$modifier] = $callback;
        return $this;
    }

    /**
     * Add inline tag compiler
     *
     * @param string $compiler
     * @param callable $parser
     * @return Cytro
     */
    public function addCompiler($compiler, $parser) {
        $this->_actions[$compiler] = array(
            'type' => self::INLINE_COMPILER,
            'parser' => $parser
        );
        return $this;
    }

    /**
     * @param string $compiler
     * @param string|object $storage
     * @return $this
     */
    public function addCompilerSmart($compiler, $storage) {
        if(method_exists($storage, "tag".$compiler)) {
            $this->_actions[$compiler] = array(
                'type' => self::INLINE_COMPILER,
                'parser' => array($storage, "tag".$compiler)
            );
        }
        return $this;
    }

    /**
     * Add block compiler
     *
     * @param string $compiler
     * @param callable $open_parser
     * @param callable|string $close_parser
     * @param array $tags
     * @return Cytro
     */
    public function addBlockCompiler($compiler, $open_parser, $close_parser = self::DEFAULT_CLOSE_COMPILER, array $tags = array()) {
        $this->_actions[$compiler] = array(
            'type' => self::BLOCK_COMPILER,
            'open' => $open_parser,
            'close' => $close_parser ?: self::DEFAULT_CLOSE_COMPILER,
            'tags' => $tags,
        );
        return $this;
    }

    /**
     * @param $compiler
     * @param $storage
     * @param array $tags
     * @param array $floats
     * @throws LogicException
     * @return Cytro
     */
    public function addBlockCompilerSmart($compiler, $storage, array $tags, array $floats = array()) {
        $c = array(
            'type' => self::BLOCK_COMPILER,
            "tags" => array(),
            "float_tags" => array()
        );
        if(method_exists($storage, $compiler."Open")) {
            $c["open"] = $compiler."Open";
        } else {
            throw new \LogicException("Open compiler {$compiler}Open not found");
        }
        if(method_exists($storage, $compiler."Close")) {
            $c["close"] = $compiler."Close";
        } else {
            throw new \LogicException("Close compiler {$compiler}Close not found");
        }
        foreach($tags as $tag) {
            if(method_exists($storage, "tag".$tag)) {
                $c["tags"][ $tag ] = "tag".$tag;
                if($floats && in_array($tag, $floats)) {
                    $c['float_tags'][ $tag ] = 1;
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
     * @param callable|string $parser
     * @return Cytro
     */
    public function addFunction($function, $callback, $parser = self::DEFAULT_FUNC_PARSER) {
        $this->_actions[$function] = array(
            'type' => self::INLINE_FUNCTION,
            'parser' => $parser,
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param string $function
     * @param callable $callback
     * @return Cytro
     */
    public function addFunctionSmart($function, $callback) {
        $this->_actions[$function] = array(
            'type' => self::INLINE_FUNCTION,
            'parser' => self::SMART_FUNC_PARSER,
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param string $function
     * @param callable $callback
     * @param callable|string $parser_open
     * @param callable|string $parser_close
     * @return Cytro
     */
    public function addBlockFunction($function, $callback, $parser_open = self::DEFAULT_FUNC_OPEN, $parser_close = self::DEFAULT_FUNC_CLOSE) {
        $this->_actions[$function] = array(
            'type'      => self::BLOCK_FUNCTION,
            'open'      => $parser_open,
            'close'     => $parser_close,
            'function'  => $callback,
        );
        return $this;
    }

    /**
     * @param array $funcs
     * @return Cytro
     */
    public function addAllowedFunctions(array $funcs) {
        $this->_allowed_funcs = $this->_allowed_funcs + array_flip($funcs);
        return $this;
    }

    /**
     * Return modifier function
     *
     * @param $modifier
     * @return mixed
     * @throws \Exception
     */
    public function getModifier($modifier) {
        if(isset($this->_modifiers[$modifier])) {
            return $this->_modifiers[$modifier];
        } elseif($this->isAllowedFunction($modifier)) {
            return $modifier;
        } else {
            throw new \Exception("Modifier $modifier not found");
        }
    }

    /**
     * Return function
     *
     * @param string $function
     * @return string|bool
     */
    public function getFunction($function) {
        if(isset($this->_actions[$function])) {
            return $this->_actions[$function];
        } else {
            return false;
        }
    }

    /**
     * @param string $function
     * @return bool
     */
    public function isAllowedFunction($function) {
        if($this->_options & self::DENY_INLINE_FUNCS) {
            return isset($this->_allowed_funcs[$function]);
        } else {
            return is_callable($function);
        }
    }

    /**
     * @param string $tag
     * @return array
     */
    public function getTagOwners($tag) {
        $tags = array();
        foreach($this->_actions as $owner => $params) {
            if(isset($params["tags"][$tag])) {
                $tags[] = $owner;
            }
        }
        return $tags;
    }

    /**
     * Add source template provider by scheme
     *
     * @param string $scm scheme name
     * @param Cytro\ProviderInterface $provider provider object
     */
    public function addProvider($scm, \Cytro\ProviderInterface $provider) {
        $this->_providers[$scm] = $provider;
    }

    /**
     * Set options. May be bitwise mask of constants DENY_METHODS, DENY_INLINE_FUNCS, DENY_SET_VARS, INCLUDE_SOURCES,
     * FORCE_COMPILE, CHECK_MTIME, or associative array with boolean values:
     * disable_methods - disable all calls method in template
     * disable_native_funcs - disable all native PHP functions in template
     * force_compile - recompile template every time (very slow!)
     * compile_check - check template modifications (slow!)
     * @param int|array $options
     */
    public function setOptions($options) {
        if(is_array($options)) {
            $options = self::_makeMask($options, self::$_option_list);
        }
        $this->_storage = array();
        $this->_options = $options;
    }

    /**
     * Get options as bits
     * @return int
     */
    public function getOptions() {
        return $this->_options;
    }

    /**
     * @param bool|string $scm
     * @return Cytro\ProviderInterface
     * @throws InvalidArgumentException
     */
    public function getProvider($scm = false) {
        if($scm) {
            if(isset($this->_providers[$scm])) {
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
     * @return Cytro\Template
     */
    public function getRawTemplate() {
        return new \Cytro\Template($this, $this->_options);
    }

    /**
     * Execute template and write result into stdout
     *
     * @param string $template name of template
     * @param array $vars array of data for template
     * @return Cytro\Render
     */
    public function display($template, array $vars = array()) {
        return $this->getTemplate($template)->display($vars);
    }

    /**
     *
     * @param string $template name of template
     * @param array $vars array of data for template
     * @return mixed
     */
    public function fetch($template, array $vars = array()) {
        return $this->getTemplate($template)->fetch($vars);
    }

	/**
	 *
	 *
	 * @param string $template name of template
	 * @param array $vars
	 * @param $callback
	 * @param float $chunk
	 * @return \Cytro\Render
	 * @example $cytro->pipe("products.yml.tpl", $iterators, [new SplFileObject("/tmp/products.yml"), "fwrite"], 512*1024)
	 */
	public function pipe($template, array $vars, $callback, $chunk = 1e6) {
		ob_start($callback, $chunk, true);
		$this->getTemplate($template)->display($vars);
		ob_end_flush();

	}

    /**
     * Get template by name
     *
     * @param string $template template name with schema
     * @param int $options additional options and flags
     * @return Cytro\Template
     */
    public function getTemplate($template, $options = 0) {
        $key = dechex($this->_options | $options)."@".$template;
        if(isset($this->_storage[ $key ])) {
            /** @var Cytro\Template $tpl  */
            $tpl = $this->_storage[ $key ];
            if(($this->_options & self::AUTO_RELOAD) && !$tpl->isValid()) {
                return $this->_storage[ $key ] = $this->compile($template, true, $options);
            } else {
                return $tpl;
            }
        } elseif($this->_options & self::FORCE_COMPILE) {
            return $this->compile($template, $this->_options & self::DISABLE_CACHE & ~self::FORCE_COMPILE, $options);
        } else {
            return $this->_storage[ $key ] = $this->_load($template, $options);
        }
    }

    /**
     * Add custom template into storage
     *
     * @param Cytro\Render $template
     */
    public function addTemplate(Cytro\Render $template) {
        $this->_storage[dechex($template->getOptions()).'@'. $template->getName() ] = $template;
    }

    /**
     * Load template from cache or create cache if it doesn't exists.
     *
     * @param string $tpl
     * @param int $opts
     * @return Cytro\Render
     */
    protected function _load($tpl, $opts) {
        $file_name = $this->_getCacheName($tpl, $opts);
        if(!is_file($this->_compile_dir."/".$file_name)) {
            return $this->compile($tpl, true, $opts);
        } else {
            $cytro = $this;
            return include($this->_compile_dir."/".$file_name);
        }
    }

    /**
     * Generate unique name of compiled template
     *
     * @param string $tpl
     * @param int $options
     * @return string
     */
    private function _getCacheName($tpl, $options) {
        $hash = $tpl.":".$options;
        return sprintf("%s.%x.%x.php", str_replace(":", "_", basename($tpl)), crc32($hash), strlen($hash));
    }

    /**
     * Compile and save template
     *
     * @param string $tpl
     * @param bool $store store template on disk
     * @param int $options
     * @throws RuntimeException
     * @return \Cytro\Template
     */
    public function compile($tpl, $store = true, $options = 0) {
        $options = $this->_options | $options;
        $template = Template::factory($this, $options)->load($tpl);
        if($store) {
            $cache = $this->_getCacheName($tpl, $options);
            $tpl_tmp = tempnam($this->_compile_dir, $cache);
            $tpl_fp = fopen($tpl_tmp, "w");
            if(!$tpl_fp) {
                throw new \RuntimeException("Can't to open temporary file $tpl_tmp. Directory ".$this->_compile_dir." is writable?");
            }
            fwrite($tpl_fp, $template->getTemplateCode());
            fclose($tpl_fp);
            $file_name = $this->_compile_dir."/".$cache;
            if(!rename($tpl_tmp, $file_name)) {
                throw new \RuntimeException("Can't to move $tpl_tmp to $tpl");
            }
        }
        return $template;
    }

    /**
     * Flush internal memory template cache
     */
    public function flush() {
       $this->_storage = array();
    }

    /**
     * Remove all compiled templates
     */
    public function clearAllCompiles() {
        \Cytro\FSProvider::clean($this->_compile_dir);
    }

    /**
     * Compile code to template
     *
     * @param string $code
     * @param string $name
     * @return Cytro\Template
     */
    public function compileCode($code, $name = 'Runtime compile') {
        return Template::factory($this, $this->_options)->source($name, $code);
    }


    /**
     * Create bit-mask from associative array use fully associative array possible keys with bit values
     * @static
     * @param array $values custom assoc array, ["a" => true, "b" => false]
     * @param array $options possible values, ["a" => 0b001, "b" => 0b010, "c" => 0b100]
     * @param int $mask the initial value of the mask
     * @return int result, ( $mask | a ) & ~b
     * @throws \RuntimeException if key from custom assoc doesn't exists into possible values
     */
    private static function _makeMask(array $values, array $options, $mask = 0) {
        foreach($values as $value) {
            if(isset($options[$value])) {
                if($options[$value]) {
                    $mask |= $options[$value];
                } else {
                    $mask &= ~$options[$value];
                }
            } else {
                throw new \RuntimeException("Undefined parameter $value");
            }
        }
        return $mask;
    }
}
