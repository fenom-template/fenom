<?php
namespace Aspect;
use Aspect;

/**
 * Primitive template
 */
class Render extends \ArrayObject {
	/**
	 * @var \Closure
	 */
	protected $_code;
    /**
     * Template name
     * @var string
     */
    protected $_name = 'runtime template';
    /**
     * @var Aspect
     */
    protected $_aspect;
    /**
     * Timestamp of compilation
     * @var float
     */
    protected $_time = 0.0;

    protected $_depends = array();

    /**
     * @param string $name template name
     * @param callable $code template body
     * @param mixed $props signature
     */
    public function __construct($name, \Closure $code, $props = array()) {
        $this->_name = $name;
		$this->_code = $code;
        $this->_time = isset($props["time"]) ? $props["time"] : microtime(true);
        $this->_depends = isset($props["depends"]) ? $props["depends"] : array();
	}

    /**
     * Set template storage
     * @param Aspect $aspect
     */
    public function setStorage(Aspect $aspect) {
        $this->_aspect = $aspect;
    }

    /**
     * Get template storage
     * @return Aspect
     */
    public function getStorage() {
        return $this->_aspect;
    }

    /**
     * @return string
     */
    public function __toString() {
		return "Template({$this->_name})";
	}

    /**
     * Get template name
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

	public function getCompileTime() {
		return $this->_time;
	}


    /**
     * Validate template
     * @return bool
     */
    public function isValid() {
	    $provider = $this->_aspect->getProvider(strstr($this->_name, ":"), true);
	    if($provider->getLastModified($this->_name) >= $this->_time) {
		    return false;
	    }
	    foreach($this->_depends as $tpl => $time) {
			if($this->_aspect->getTemplate($tpl)->getCompileTime() !== $time) {
				return false;
			}
	    }
        return true;
    }

    /**
     * Execute template and write into output
     * @param array $values for template
     * @return Render
     */
    public function display(array $values) {
		$this->exchangeArray($values);
		$this->_code->__invoke($this);
        return $this;
	}

    /**
     * Execute template and return result as string
     * @param array $values for template
     * @return string
     * @throws \Exception
     */
    public function fetch(array $values) {
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
    public function __call($method, $args) {
		throw new \BadMethodCallException("Unknown method ".$method);
	}
}
