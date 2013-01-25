<?php
use Aspect\Template;

/**
 * Templater
 */
class Aspect {

    const INLINE_COMPILER = 1;
    const BLOCK_COMPILER = 2;
    const INLINE_FUNCTION = 3;
    const BLOCK_FUNCTION = 4;
    const MODIFIER = 5;

    const DENY_METHODS = 128;
    const DENY_INLINE_FUNCS = 256;
    const DENY_SET_VARS = 512;

    const INCLUDE_SOURCES = 1024;

    const CHECK_MTIME = 2048;
    const FORCE_COMPILE = 4096;

    /**
     * @var array list of possible options, as associative array
     * @see setOptions, addOptions, delOptions
     */
    private static $_option_list = array(
        "disable_methods" => self::DENY_METHODS,
        "disable_native_funcs" => self::DENY_INLINE_FUNCS,
        "disable_set_vars" => self::DENY_SET_VARS,
        "include_sources" => self::INCLUDE_SOURCES,
        "force_compile" => self::FORCE_COMPILE,
        "compile_check" => self::CHECK_MTIME,
    );

    private static $_actions_defaults = array(
        self::BLOCK_FUNCTION => array(
            'type' => self::BLOCK_FUNCTION,
            'open' => 'MF\Aspect\Compiler::stdFuncOpen',
            'close' => 'MF\Aspect\Compiler::stdFuncClose',
            'function' => null,
        ),
        self::INLINE_FUNCTION => array(
            'type' => self::INLINE_FUNCTION,
            'parser' => 'MF\Aspect\Compiler::stdFuncParser',
            'function' => null,
        ),
        self::INLINE_FUNCTION => array(
            'type' => self::INLINE_COMPILER,
            'open' => null,
            'close' => 'MF\Aspect\Compiler::stdClose',
            'tags' => array(),
            'float_tags' => array()
        ),
        self::BLOCK_FUNCTION => array(
            'type' => self::BLOCK_COMPILER,
            'open' => null,
            'close' => null,
            'tags' => array(),
            'float_tags' => array()
        )
    );

    public $blocks = array();
	/**
	 * @var array Templates storage
	 */
	protected $_storage = array();
    /**
     * @var array template directory
     */
    protected $_tpl_path = array();
    /**
     * @var string compile directory
     */
    protected $_compile_dir = "/tmp";

    /**
     * @var int masked options
     */
    protected $_options = 0;

    /**
     * Modifiers loader
     * @var callable
     */
    protected $_loader_mod;
    /**
     * Functions loader
     * @var callable
     */
    protected $_loader_func;

    /**
     * @var array list of modifiers
     */
    protected $_modifiers = array(
        "upper" => 'strtoupper',
        "lower" => 'strtolower',
        "nl2br" => 'nl2br',
        "date_format" => 'Aspect\Modifier::dateFormat',
        "date" => 'Aspect\Modifier::date',
        "truncate" => 'Aspect\Modifier::truncate',
        "escape" => 'Aspect\Modifier::escape',
        "e" => 'Aspect\Modifier::escape', // alias of escape
        "unescape" => 'Aspect\Modifier::unescape',
        "strip_tags" => 'strip_tags',
        "strip" => 'Aspect\Modifier::strip',
        "default" => 'Aspect\Modifier::defaultValue',
        "isset" => 'isset',
        "empty" => 'empty'
    );

    /**
     * @var array list of allowed PHP functions
     */
    protected $_allowed_funcs = array(
        "empty" => 1, "isset" => 1, "count" => 1, "is_string" => 1, "is_array" => 1, "is_numeric" => 1, "is_int" => 1, "is_object" => 1
    );

    /**
     * @var array list of compilers and functions
     */
    protected $_actions = array(
        'foreach' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Aspect\Compiler::foreachOpen',
            'close' => 'Aspect\Compiler::foreachClose',
            'tags' => array(
                'foreachelse' => 'Aspect\Compiler::foreachElse',
                'break' => 'Aspect\Compiler::tagBreak',
                'continue' => 'Aspect\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'if' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Aspect\Compiler::ifOpen',
            'close' => 'Aspect\Compiler::stdClose',
            'tags' => array(
                'elseif' => 'Aspect\Compiler::tagElseIf',
                'else' => 'Aspect\Compiler::tagElse',
            )
        ),
        'switch' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Aspect\Compiler::switchOpen',
            'close' => 'Aspect\Compiler::stdClose',
            'tags' => array(
                'case' => 'Aspect\Compiler::tagCase',
                'default' => 'Aspect\Compiler::tagDefault',
                'break' => 'Aspect\Compiler::tagBreak',
            ),
            'float_tags' => array('break' => 1)
        ),
        'for' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Aspect\Compiler::forOpen',
            'close' => 'Aspect\Compiler::forClose',
            'tags' => array(
                'forelse' => 'Aspect\Compiler::forElse',
                'break' => 'Aspect\Compiler::tagBreak',
                'continue' => 'Aspect\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'while' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Aspect\Compiler::whileOpen',
            'close' => 'Aspect\Compiler::stdClose',
            'tags' => array(
                'break' => 'Aspect\Compiler::tagBreak',
                'continue' => 'Aspect\Compiler::tagContinue',
            ),
            'float_tags' => array('break' => 1, 'continue' => 1)
        ),
        'include' => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Aspect\Compiler::tagInclude'
        ),
        'var' => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Aspect\Compiler::assign'
        ),
        'block' => array(
            'type' => self::BLOCK_COMPILER,
            'open' => 'Aspect\Compiler::tagBlockOpen',
            'close' => 'Aspect\Compiler::tagBlockClose',
        ),
        'extends' => array(
            'type' => self::INLINE_COMPILER,
            'parser' => 'Aspect\Compiler::tagExtends'
        ),
        'capture' => array(
            'type' => self::BLOCK_FUNCTION,
            'open' => 'Aspect\Compiler::stdFuncOpen',
            'close' => 'Aspect\Compiler::stdFuncClose',
            'function' => 'Aspect\Func::capture',
        ),
        'mailto' => array(
            'type' => self::INLINE_FUNCTION,
            'parser' => 'Aspect\Compiler::stdFuncParser',
            'function' => 'Aspect\Func::mailto',
        )

    );

    public static function factory($template_dir, $compile_dir, $options = 0) {
        $aspect = new static();
        $aspect->setCompileDir($compile_dir);
        $aspect->setTemplateDirs($template_dir);
        if($options) {
            $aspect->setOptions($options);
        }
        return $aspect;
    }

    public function setCompileCheck($state) {
        $state && ($this->_options |= self::CHECK_MTIME);
        return $this;
    }

    public function setForceCompile($state) {
        $state && ($this->_options |= self::FORCE_COMPILE);
        $this->_storage = $state ? new Aspect\BlackHole() : array();
        return $this;
    }

    public function setCompileDir($dir) {
        $this->_compile_dir = $dir;
        return $this;
    }

    public function setTemplateDirs($dirs) {
        $this->_tpl_path = (array)$dirs;
        return $this;
    }

    /*public function addPostCompileFilter($cb) {
        $this->_post_cmp[] = $cb;
    }

    public function addCompileFilter($cb) {
        $this->_cmp[] = $cb;
    }*/

    /**
     * Add modifier
     *
     * @param string $modifier
     * @param string $callback
     * @return Aspect
     */
    public function setModifier($modifier, $callback) {
        $this->_modifiers[$modifier] = $callback;
        return $this;
    }

    /**
     * @param $compiler
     * @param $parser
     * @return Aspect
     */
    public function setCompiler($compiler, $parser) {
        $this->_actions[$compiler] = array(
            'type' => self::INLINE_COMPILER,
            'parser' => $parser
        );
        return $this;
    }

    /**
     * @param $compiler
     * @param array $parsers
     * @param array $tags
     * @return Aspect
     */
    public function setBlockCompiler($compiler, array $parsers, array $tags = array()) {
        $this->_actions[$compiler] = array(
            'type' => self::BLOCK_COMPILER,
            'open' => $parsers["open"],
            'close' => isset($parsers["close"]) ? $parsers["close"] : 'Aspect\Compiler::stdClose',
            'tags' => $tags,
        );
        return $this;
    }

    /**
     * @param $function
     * @param $callback
     * @param null $parser
     * @return Aspect
     */
    public function setFunction($function, $callback, $parser = null) {
        $this->_actions[$function] = array(
            'type' => self::INLINE_FUNCTION,
            'parser' => $parser ?: 'Aspect\Compiler::stdFuncParser',
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param $function
     * @param $callback
     * @param null $parser_open
     * @param null $parser_close
     * @return Aspect
     */
    public function setBlockFunction($function, $callback, $parser_open = null, $parser_close = null) {
        $this->_actions[$function] = array(
            'type' => self::BLOCK_FUNCTION,
            'open' => $parser_open ?: 'Aspect\Compiler::stdFuncOpen',
            'close' => $parser_close ?: 'Aspect\Compiler::stdFuncClose',
            'function' => $callback,
        );
        return $this;
    }

    /**
     * @param array $funcs
     * @return Aspect
     */
    public function setAllowedFunctions(array $funcs) {
        $this->_allowed_funcs = $this->_allowed_funcs + array_flip($funcs);
        return $this;
    }

    /**
     * @param callable $callback
     * @return Aspect
     */
    public function setFunctionsLoader($callback) {
        $this->_loader_func = $callback;
        return $this;
    }

    /**
     * @param callable $callback
     * @return Aspect
     */
    public function setModifiersLoader($callback) {
        $this->_loader_mod = $callback;
        return $this;
    }

    /**
     * @param $modifier
     * @return mixed
     * @throws \Exception
     */
    public function getModifier($modifier) {
        if(isset($this->_modifiers[$modifier])) {
            return $this->_modifiers[$modifier];
        } elseif($this->isAllowedFunction($modifier)) {
            return $modifier;
        } elseif($this->_loader_mod && $this->_loadModifier($modifier)) {
            return $this->_modifiers[$modifier];
        } else {
            throw new \Exception("Modifier $modifier not found");
        }
    }

    /**
     * @param string $function
     * @return string|bool
     */
    public function getFunction($function) {
        if(isset($this->_actions[$function])) {
            return $this->_actions[$function];
        } elseif($this->_loader_func && $this->_loadFunction($function)) {
            return $this->_actions[$function];
        } else {
            return false;
        }
    }

    private function _loadModifier($modifier) {
        $mod = call_user_func($this->_loader_mod, $modifier);
        if($mod) {
            $this->_modifiers[$modifier] = $mod;
            return true;
        } else {
            return false;
        }
    }

    private function _loadFunction($function) {
        $func = call_user_func($this->_loader_func, $function);
        if($func && isset(self::$_actions_defaults[ $func["type"] ])) {

            $this->_actions[$function] = $func + self::$_actions_defaults[ $func["type"] ];
            return true;
        } else {
            return false;
        }
    }

    public function isAllowedFunction($function) {
        if($this->_options & self::DENY_INLINE_FUNCS) {
            return isset($this->_allowed_funcs[$function]);
        } else {
            return is_callable($function);
        }
    }

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
     * Add template directory
     * @static
     * @param string $dir
     * @throws \InvalidArgumentException
     */
    public function addTemplateDir($dir) {
        $_dir = realpath($dir);
        if(!$_dir) {
            throw new \InvalidArgumentException("Invalid template dir: $dir");
        }
		$this->_tpl_path[] = $_dir;
	}

    /**
     * Set options. May be bitwise mask of constants DENY_METHODS, DENY_INLINE_FUNCS, DENY_SET_VARS, INCLUDE_SOURCES,
     * FORCE_COMPILE, CHECK_MTIME, or associative array with boolean values:
     * disable_methods - disable all call method in template
     * disable_native_funcs - disable all native PHP functions in template
     * disable_set_vars - forbidden rewrite variables
     * include_sources - insert comments with source code into compiled template
     * force_compile - recompile template every time (very slow!)
     * compile_check - check template modifications (slow!)
     * @param int|array $options
     */
    public function setOptions($options) {
        if(is_array($options)) {
            $options = Aspect\Misc::makeMask($options, self::$_option_list);
        }
        $this->_storage = ($options & self::FORCE_COMPILE) ? new Aspect\BlackHole() : array();
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
     * Execute template and write result into stdout
     *
     *
     * @param string $template
     * @param array $vars
     * @return Aspect\Render
     */
	public function display($template, array $vars = array()) {
        return $this->getTemplate($template)->display($vars);
	}

    /**
     *
     * @param string $template
     * @param array $vars
     * @internal param int $options
     * @return mixed
     */
	public function fetch($template, array $vars = array()) {
		return $this->getTemplate($template)->fetch($vars);
	}

    /**
     * Return template by name
     *
     * @param string $template
     * @return Aspect\Template
     */
	public function getTemplate($template) {
        if(isset($this->_storage[ $template ])) {
            if(($this->_options & self::CHECK_MTIME) && !$this->_check($template)) {
                return $this->_storage[ $template ] = $this->compile($template);
            } else {
			    return $this->_storage[ $template ];
            }
		} elseif($this->_options & self::FORCE_COMPILE) {
            return $this->compile($template);
        } else {
            return $this->_storage[ $template ] = $this->_load($template);
        }
	}

    /**
     * Add custom template into storage
     * @param Aspect\Render $template
     */
    public function storeTemplate(Aspect\Render $template) {
        $this->_storage[ $template->getName() ] = $template;
        $template->setStorage($this);
    }

    /**
     * Return template from storage or create if template doesn't exists.
     *
     * @param string $tpl
     * @throws \RuntimeException
     * @return Aspect\Template|mixed
     */
    protected function _load($tpl) {
		$file_name = $this->_getHash($tpl);
		if(!is_file($this->_compile_dir."/".$file_name) || ($this->_options & self::CHECK_MTIME) && !$this->_check($tpl)) {
            return $this->compile($tpl);
		} else {
            /** @var Aspect\Render $tpl */
            $tpl = include($this->_compile_dir."/".$file_name);
            $tpl->setStorage($this);
            return $tpl;
		}
	}

    /**
     * @param string $template
     * @return bool
     */
    private function _check($template) {
        return $this->_isActual($template, filemtime($this->_compile_dir."/".$this->_getHash($template)));
    }

    /**
     * Check, if template is actual
     * @param $template
     * @param $compiled_time
     * @return bool
     */
    protected function _isActual($template, $compiled_time) {
        clearstatcache(false, $template = $this->_getTemplatePath($template));
        return filemtime($template) < $compiled_time;
    }

    /**
     * Generate unique name of compiled template
     *
     * @param string $tpl
     * @return string
     */
    private function _getHash($tpl) {
        $hash = $tpl.":".$this->_options;
        return basename($tpl).".".crc32($hash).".".strlen($hash).".php";
    }

    /**
     * Compile and save template
     *
     *
     * @param string $tpl
     * @throws \RuntimeException
     * @return \Aspect\Template
     */
    public function compile($tpl) {
        $file_name = $this->_compile_dir."/".$this->_getHash($tpl);
        $template = new Template($this, $this->_loadCode($tpl), $tpl);
        $tpl_tmp = tempnam($this->_compile_dir, basename($tpl));
        $tpl_fp = fopen($tpl_tmp, "w");
        if(!$tpl_fp) {
            throw new \RuntimeException("Can not open temporary file $tpl_tmp. Directory ".$this->_compile_dir." is writable?");
        }
        fwrite($tpl_fp, $template->getTemplateCode());
        fclose($tpl_fp);
        if(!rename($tpl_tmp, $file_name)) {
            throw new \RuntimeException("Can not to move $tpl_tmp to $tpl");
        }
        return $template;
    }

    /**
     * Remove all compiled templates. Warning! Do cleanup the compiled directory.
     * @return int
     * @api
     */
    public function compileAll() {
        //return FS::rm($this->_compile_dir.'/*');
    }

    /**
     * @param string $tpl
     * @return bool
     * @api
     */
    public function clearCompileTemplate($tpl) {
        $file_name = $this->_compile_dir."/".$this->_getHash($tpl);
        if(file_exists($file_name)) {
            return unlink($file_name);
        } else {
            return true;
        }
    }

    /**
     * @return int
     * @api
     */
    public function clearAllCompiles() {

    }

    /**
     * Get template path
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    private function _getTemplatePath($tpl) {
        foreach($this->_tpl_path as $tpl_path) {
            if(($path = stream_resolve_include_path($tpl_path."/".$tpl)) && strpos($path, $tpl_path) === 0) {
                return $path;
            }
        }
        throw new \RuntimeException("Template $tpl not found");
    }

    /**
     * Code loader
     *
     * @param string $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _loadCode(&$tpl) {
        return file_get_contents($tpl = $this->_getTemplatePath($tpl));
    }

    /**
     * Compile code to template
     *
     * @param string $code
     * @param string $name
     * @return Aspect\Template
     */
    public function compileCode($code, $name = 'Runtime compile') {
        return new Template($this, $code, $name);
    }

}
