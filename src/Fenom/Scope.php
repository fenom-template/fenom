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

/**
 * Scope for blocks tags
 *
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Scope extends \ArrayObject {

    public $line = 0;
    public $name;
    public $level = 0;
    /**
     * @var Template
     */
    public $tpl;
    public $is_compiler = true;
    public $is_closed = false;
    public $escape = false;
    private $_action;
    private $_body;
    private $_offset;
    public $_global_escape = false;

    /**
     * Creating cope
     *
     * @param string   $name
     * @param Template $tpl
     * @param int      $line
     * @param array    $action
     * @param int      $level
     * @param $body
     */
    public function __construct($name, $tpl, $line, $action, $level, &$body) {
        $this->line = $line;
        $this->name = $name;
        $this->tpl = $tpl;
        $this->_action = $action;
        $this->level = $level;
        $this->_body = &$body;
        $this->_offset = strlen($body);
    }

    /**
     *
     * @param string $function
     */
    public function setFuncName($function) {
        $this["function"] = $function;
        $this->is_compiler = false;
        $this->_global_escape = $this->tpl->escape;
        $this->tpl->escape = false;
    }

    /**
     * Open callback
     *
     * @param Tokenizer $tokenizer
     * @return mixed
     */
    public function open($tokenizer) {
        return call_user_func($this->_action["open"], $tokenizer, $this);
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
        if(!$this->is_compiler) {
            $this->tpl->escape = $this->_global_escape;
        }
        return call_user_func($this->_action["close"], $tokenizer, $this);
    }

    /**
     * Return content of block
     *
     * @throws \LogicException
     * @return string
     */
    public function getContent() {
        return substr($this->_body, $this->_offset);
    }

    /**
     * Cut scope content
     *
     * @return string
     * @throws \LogicException
     */
    public function cutContent() {
        $content = substr($this->_body, $this->_offset + 1);
        $this->_body = substr($this->_body, 0, $this->_offset);
        return $content;
    }

    /**
     * Replace scope content
     *
     * @param $new_content
     */
    public function replaceContent($new_content) {
        $this->cutContent();
        $this->_body .= $new_content;
    }
}