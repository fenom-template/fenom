<?php
namespace Aspect;

/**
 * Scope for blocks tags
 */
class Scope extends \ArrayObject {

    public $id = 0;
    public $line = 0;
    public $name;
    public $level = 0;
    /**
     * @var Template
     */
    public $tpl;
    public $closed = false;
    public $is_next_close = false;
    public $is_compiler = true;
    private $_action;
    private static $count = 0;

    /**
     * @param string   $name
     * @param Template $tpl
     * @param int      $line
     * @param array    $action
     * @param int      $level
     */
    public function __construct($name, $tpl, $line, $action, $level) {
        $this->id = ++self::$count;
        $this->line = $line;
        $this->name = $name;
        $this->tpl = $tpl;
        $this->_action = $action;
        $this->level = $level;
    }

    /**
     *
     * @param string $function
     */
    public function setFuncName($function) {
        $this["function"] = $function;
        $this->is_compiler = false;
    }

    /**
     * Open callback
     *
     * @param Tokenizer $tokenizer
     * @return mixed
     */
    public function open($tokenizer) {
        return call_user_func($this->_action["open"], $tokenizer, $this)." /*#{$this->id}#*/";
    }

    /**
     * Check, has the block this tag
     *
     * @param string $tag
     * @param int $level
     * @return bool
     */
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

    /**
     * Call tag callback
     *
     * @param string $tag
     * @param Tokenizer $tokenizer
     * @return string
     */
    public function tag($tag, $tokenizer) {
        return call_user_func($this->_action["tags"][$tag], $tokenizer, $this);
    }

    /**
     * Close callback
     *
     * @param Tokenizer $tokenizer
     * @return string
     */
    public function close($tokenizer) {
        return call_user_func($this->_action["close"], $tokenizer, $this);
    }

    /**
     * Return content of block
     *
     * @throws \LogicException
     * @return string
     */
    public function getContent() {
        if($pos = strpos($this->tpl->_body, "/*#{$this->id}#*/")) {
            $begin = strpos($this->tpl->_body, "?>", $pos);
            return substr($this->tpl->_body, $begin + 2);
        } else {
            throw new \LogicException("Trying get content of non-block scope");
        }
    }
}