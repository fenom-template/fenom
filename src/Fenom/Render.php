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

/**
 * Primitive template
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Render extends \ArrayObject
{
    private static $_props = array(
        "name"      => "runtime",
        "base_name" => "",
        "scm"       => false,
        "time"      => 0,
        "depends"   => array(),
        "macros"    => array()
    );
    /**
     * @var \Closure
     */
    protected $_code;
    /**
     * Template name
     * @var string
     */
    protected $_name = 'runtime';
    /**
     * Provider's schema
     * @var bool
     */
    protected $_scm = false;
    /**
     * Basic template name
     * @var string
     */
    protected $_base_name = 'runtime';
    /**
     * @var Fenom
     */
    protected $_fenom;
    /**
     * Timestamp of compilation
     * @var float
     */
    protected $_time = 0.0;

    /**
     * @var array depends list
     */
    protected $_depends = array();

    /**
     * @var int template options (see Fenom options)
     */
    protected $_options = 0;

    /**
     * Template provider
     * @var ProviderInterface
     */
    protected $_provider;

    /**
     * @var \Closure[]
     */
    protected $_macros;

    /**
     * @param Fenom $fenom
     * @param callable $code template body
     * @param array $props
     */
    public function __construct(Fenom $fenom, \Closure $code, array $props = array())
    {
        $this->_fenom = $fenom;
        $props += self::$_props;
        $this->_name      = $props["name"];
        $this->_base_name = $props["base_name"];
        $this->_scm       = $props["scm"];
        $this->_time      = $props["time"];
        $this->_depends   = $props["depends"];
        $this->_macros    = $props["macros"];
        $this->_code      = $code;
    }

    /**
     * Get template storage
     * @return \Fenom
     */
    public function getStorage()
    {
        return $this->_fenom;
    }

    /**
     * Get depends list
     * @return array
     */
    public function getDepends()
    {
        return $this->_depends;
    }

    /**
     * Get schema name
     * @return string
     */
    public function getScm()
    {
        return $this->_scm;
    }

    /**
     * Get provider of template source
     * @return ProviderInterface
     */
    public function getProvider()
    {
        return $this->_fenom->getProvider($this->_scm);
    }

    /**
     * Get name without schema
     * @return string
     */
    public function getBaseName()
    {
        return $this->_base_name;
    }

    /**
     * Get parse options
     * @return int
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_name;
    }

    /**
     * Get template name
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    public function getTime()
    {
        return $this->_time;
    }


    /**
     * Validate template
     * @return bool
     */
    public function isValid()
    {
        foreach ($this->_depends as $scm => $templates) {
            $provider = $this->_fenom->getProvider($scm);
            if(count($templates) === 1) {
                if ($provider->getLastModified(key($templates)) !== $this->_time) {
                    return false;
                }
            } else {
                if (!$provider->verify($templates)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get internal macro
     * @param $name
     * @throws \RuntimeException
     * @return mixed
     */
    public function getMacro($name)
    {
        if (empty($this->_macros[$name])) {
            throw new \RuntimeException('macro ' . $name . ' not found');
        }
        return $this->_macros[$name];
    }

    /**
     * Execute template and write into output
     * @param array $values for template
     * @return Render
     */
    public function display(array $values)
    {
        $this->_code->__invoke($values, $this);
        return $values;
    }

    /**
     * Execute template and return result as string
     * @param array $values for template
     * @return string
     * @throws \Exception
     */
    public function fetch(array $values)
    {
        ob_start();
        try {
            $this->display($values);
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Stub
     * @param $method
     * @param $args
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        throw new \BadMethodCallException("Unknown method " . $method);
    }

    public function __get($name)
    {
        return $this->$name = null;
    }
}
