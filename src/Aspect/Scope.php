<?php
namespace Aspect;

/**
 * Scope for blocks tags
 */
class Scope extends \ArrayObject {

	public $line = 0;
	public $name;
	/**
	 * @var Template
	 */
	public $tpl;
    public $closed = false;
    public $is_next_close = false;
	public $is_compiler = true;
	private $_action;

    /**
     * @param string   $name
     * @param Template $tpl
     * @param int      $line
     * @param array    $action
     */
    public function __construct($name, $tpl, $line, $action) {
		$this->line = $line;
		$this->name = $name;
		$this->tpl = $tpl;
		$this->_action = $action;
	}

    public function setFuncName($function) {
        $this["function"] = $function;
        $this->is_compiler = false;
    }

    public function open($tokenizer) {
        return call_user_func($this->_action["open"], $tokenizer, $this);
    }

	public function hasTag($tag, $level) {
		if(isset($this->_action["tags"][$tag])) {
            if($level) {
                return isset($this->_action["float_tags"][$tag]);
            } else {
                return true;
            }
        }
        return false;
	}

	public function tag($tag, $tokenizer) {
		return call_user_func($this->_action["tags"][$tag], $tokenizer, $this);
	}

    public function close($tokenizer) {
        return call_user_func($this->_action["close"], $tokenizer, $this);
    }

    /**
     * Count chars to close tag
     * @todo
     * @return int
     */
    public function getDistanceToClose() {
        return 1;
    }
}