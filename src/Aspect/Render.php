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
     * Signature of the template
     * @var mixed
     */
    protected $_fingerprint;

    /**
     * @param string $name template name
     * @param callable $code template body
     * @param mixed $fingerprint signature
     */
    public function __construct($name, \Closure $code, $fingerprint = null) {
        $this->_name = $name;
		$this->_code = $code;
		$this->_fingerprint = $fingerprint;
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

    /**
     * Validate template version
     * @param mixed $fingerprint of the template
     * @return bool
     */
    public function isValid($fingerprint) {
        if($this->_fingerprint) {
            return $fingerprint === $this->_fingerprint;
        } else {
            return true;
        }
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
