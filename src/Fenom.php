<?php
/*
 * This file is part of Fenom.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
use Fenom\Template,
    Fenom\ProviderInterface;

/**
 * Fenom Template Engine
 *
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Fenom {
    const VERSION = '1.0';

    /* Compiler types */
    const INLINE_COMPILER   = 1;
    const BLOCK_COMPILER    = 2;
    const INLINE_FUNCTION   = 3;
    const BLOCK_FUNCTION    = 4;
    const MODIFIER          = 5;

    /* Options */
    const DENY_METHODS      =  0x10;
    const DENY_INLINE_FUNCS =  0x20;
    const FORCE_INCLUDE     =  0x40;
    const AUTO_RELOAD       =  0x80;
    const FORCE_COMPILE     =  0xF0;
    const DISABLE_CACHE     = 0x1F0;
    const AUTO_ESCAPE       = 0x200;
    const FORCE_VALIDATE    = 0x400;

    /* Default parsers */
    const DEFAULT_CLOSE_COMPILER = 'Fenom\Compiler::stdClose';
    const DEFAULT_FUNC_PARSER    = 'Fenom\Compiler::stdFuncParser';
    const DEFAULT_FUNC_OPEN      = 'Fenom\Compiler::stdFuncOpen';
    const DEFAULT_FUNC_CLOSE     = 'Fenom\Compiler::stdFuncClose';
    const SMART_FUNC_PARSER      = 'Fenom\Compiler::smartFuncParser';

    /**
     * @var int[] of possible options, as associative array
     * @see setOptions
     */
    private static $_option_list = array(
        "disable_methods" => self::DENY_METHODS,
        "disable_native_funcs" => self::DENY_INLINE_FUNCS,
        "disable_cache" => self::DISABLE_CACHE,
        "force_compile" => self::FORCE_COMPILE,
        "auto_reload" => self::AUTO_RELOAD,
        "force_include" => self::FORCE_INCLUDE,
        "auto_escape" => self::AUTO_ESCAPE,
        "force_validate" => self::FORCE_VALIDATE
    );

    /**
     * @var Fenom\Render[] Templates storage
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
     * @var Fenom\ProviderInterface[]
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
        "date_format" => 'Fenom\Modifier::dateFormat',
        "date"        => 'Fenom\Modifier::date',
        "truncate"    => 'Fenom\Modifier::truncate',
        "escape"      => 'Fenom\Modifier::escape',
        "e"           => 'Fenom\Modifier::escape', // alias of escape
        "unescape"    => 'Fenom\Modifier::unescape',
        "strip"       => 'Fenom\Modifier::strip',
        "length"      => 'Fenom\Modifier::length',
        "default"     => 'Fenom\Modifier::defaultValue'
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
            'open' => 'Fenom\Compiler::foreachOpen',
            'close' => 'Fenom\Compiler::foreachClose',
            'tags' => array(
                'foreachelse' => 'Fenom\Compiler::foreachElse',
                'break' => 'Fenom\Compiler::tagBreak',
                'continue' => 'Fenom\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'if' => array(      // {if ...} {elseif ...} {else} {/if}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::ifOpen',
            'close' => 'Fenom\Compiler::stdClose',
            'tags' => array(
                'elseif' => 'Fenom\Compiler::tagElseIf',
                'else' => 'Fenom\Compiler::tagElse',
            )
        ),
        'switch' => array(  // {switch ...} {case ...} {break} {default} {/switch}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::switchOpen',
            'close' => 'Fenom\Compiler::stdClose',
            'tags' => array(
                'case' => 'Fenom\Compiler::tagCase',
                'default' => 'Fenom\Compiler::tagDefault',
                'break' => 'Fenom\Compiler::tagBreak',
            ),
            'float_tags' => array('break' => 1)
        ),
        'for' => array(     // {for ...} {break} {continue} {/for}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::forOpen',
            'close' => 'Fenom\Compiler::forClose',
            'tags' => array(
                'forelse' => 'Fenom\Compiler::forElse',
                'break' => 'Fenom\Compiler::tagBreak',
                'continue' => 'Fenom\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'while' => array(   // {while ...} {break} {continue} {/while}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::whileOpen',
            'close' => 'Fenom\Compiler::stdClose',
            'tags' => array(
                'break' => 'Fenom\Compiler::tagBreak',
                'continue' => 'Fenom\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'include' => array( // {include ...}
            'type' => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagInclude'
        ),
        'var' => array(     // {var ...}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::varOpen',
            'close' => 'Fenom\Compiler::varClose'
        ),
        'block' => array(   // {block ...} {parent} {/block}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::tagBlockOpen',
            'close' => 'Fenom\Compiler::tagBlockClose',
            'tags' => array(
                'parent' => 'Fenom\Compiler::tagParent'
            ),
            'float_tags' => array('parent' => 1)
        ),
        'extends' => array( // {extends ...}
            'type' => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagExtends'
        ),
        'use' => array( // {use}
            'type' => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagUse'
        ),
        'capture' => array( // {capture ...} {/capture}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::captureOpen',
            'close' => 'Fenom\Compiler::captureClose'
        ),
        'filter' => array( // {filter} ... {/filter}
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::filterOpen',
            'close' => 'Fenom\Compiler::filterClose'
        ),
        'macro' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::macroOpen',
            'close' => 'Fenom\Compiler::macroClose'
        ),
        'import' => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagImport'
        ),
        'cycle' => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagCycle'
        ),
        'raw'   => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Fenom\Compiler::tagRaw'
        ),
        'autoescape' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Fenom\Compiler::autoescapeOpen',
            'close' => 'Fenom\Compiler::autoescapeClose'
        )
    );

    /**
     * Just factory
     *
     * @param string|Fenom\ProviderInterface $source path to templates or custom provider
     * @param string $compile_dir path to compiled files
     * @param int $options
     * @throws InvalidArgumentException
     * @return Fenom
     */
    public static function factory($source, $compile_dir = '/tmp', $options = 0) {
        if(is_string($source)) {
            $provider = new Fenom\Provider($source);
        } elseif($source instanceof ProviderInterface) {
            $provider = $source;
        } else {
            throw new InvalidArgumentException("Source must be a valid path or provider object");
        }
        $fenom = new static($provider);
        $fenom->setCompileDir($compile_dir);
        if($options) {
            $fenom->setOptions($options);
        }
        return $fenom;
    }

    /**
     * @param Fenom\ProviderInterface $provider
     */
    public function __construct(Fenom\ProviderInterface $provider) {
        $this->_provider = $provider;
    }

    /**
     * Set compile directory
     *
     * @param string $dir directory to store compiled templates in
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @return Fenom
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
     * @param Fenom\ProviderInterface $provider provider object
     */
    public function addProvider($scm, \Fenom\ProviderInterface $provider) {
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
     * @return Fenom\ProviderInterface
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
     * @return Fenom\Template
     */
    public function getRawTemplate() {
        return new \Fenom\Template($this, $this->_options);
    }

    /**
     * Execute template and write result into stdout
     *
     * @param string $template name of template
     * @param array $vars array of data for template
     * @return Fenom\Render
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
     * @return \Fenom\Render
     * @example $fenom->pipe("products.yml.tpl", $iterators, [new SplFileObject("/tmp/products.yml"), "fwrite"], 512*1024)
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
     * @return Fenom\Template
     */
    public function getTemplate($template, $options = 0) {
		$options |= $this->_options;

		if (($options & self::FORCE_COMPILE) === self::FORCE_COMPILE) {
			return $this->compile($template, ($options & self::DISABLE_CACHE) === self::DISABLE_CACHE, $options);
		}

		$key = dechex($options)."@".$template;
		if(isset($this->_storage[ $key ])) {
            /** @var Fenom\Template $tpl  */
            $tpl = $this->_storage[ $key ];
            if(($options & self::AUTO_RELOAD) && !$tpl->isValid()) {
                return $this->_storage[ $key ] = $this->compile($template, true, $options);
            } else {
                return $tpl;
            }
        } else {
			return $this->_storage[ $key ] = $this->_load($template, $options);
        }
    }

    /**
     * Add custom template into storage
     *
     * @param Fenom\Render $template
     */
    public function addTemplate(Fenom\Render $template) {
        $this->_storage[dechex($template->getOptions()).'@'. $template->getName() ] = $template;
    }

    /**
     * Load template from cache or create cache if it doesn't exists.
     *
     * @param string $tpl
     * @param int $opts
     * @return Fenom\Render
     */
	protected function _load($tpl, $opts) {
		$cachePath = $this->_compile_dir . DIRECTORY_SEPARATOR . $this->_getCacheName($tpl, $opts);
		$useCache = false;
		$cached = null;
		if (is_file($cachePath)) {
			$fenom = $this;
			/** @var Fenom\Render $cached */
			$cached = include($cachePath);
			if (($opts & self::AUTO_RELOAD) !== self::AUTO_RELOAD || $cached->isValid()) {
				$useCache = true;
			}
		}

		return $useCache ? $cached : $this->compile($tpl, true, $opts);
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
     * @return \Fenom\Template
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
        \Fenom\Provider::clean($this->_compile_dir);
    }

    /**
     * Compile code to template
     *
     * @param string $code
     * @param string $name
     * @return Fenom\Template
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
