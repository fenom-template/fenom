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


class Tag extends \ArrayObject
{
    const COMPILER = 1;
    const FUNC = 2;
    const BLOCK = 4;

    /**
     * @var Template
     */
    public $tpl;
    public $name;
    public $options;
    public $line = 0;
    public $level = 0;
    public $callback;

    private $_offset = 0;
    private $_closed = true;
    private $_body;
    private $_type = 0;
    private $_open;
    private $_close;
    private $_tags = array();
    private $_floats = array();

    /**
     * Create tag entity
     * @param string $name the tag name
     * @param Template $tpl current template
     * @param string $info tag's information
     * @param string $body template's code
     */
    public function __construct($name, Template $tpl, $info, &$body)
    {
        $this->tpl = $tpl;
        $this->name = $name;
        $this->line = $tpl->getLine();
        $this->level = $tpl->getStackSize();
        $this->_body = & $body;
        $this->_offset = strlen($body);
        $this->_type = $info["type"];

        if ($this->_type & self::BLOCK) {
            $this->_open = $info["open"];
            $this->_close = $info["close"];
            $this->_tags = isset($info["tags"]) ? $info["tags"] : array();
            $this->_floats = isset($info["float_tags"]) ? $info["float_tags"] : array();
            $this->_closed = false;
        } else {
            $this->_open = $info["parser"];
        }

        if ($this->_type & self::FUNC) {
            $this->callback = $info["function"];
        }
    }

    /**
     * Set tag option
     * @param string $option
     */
    public function setOption($option)
    {

    }

    /**
     * Check, if the tag closed
     * @return bool
     */
    public function isClosed()
    {
        return $this->_closed;
    }

    /**
     * Open callback
     *
     * @param Tokenizer $tokenizer
     * @return mixed
     */
    public function start($tokenizer)
    {
        return call_user_func($this->_open, $tokenizer, $this);
    }

    /**
     * Check, has the block this tag
     *
     * @param string $tag
     * @param int $level
     * @return bool
     */
    public function hasTag($tag, $level)
    {
        if (isset($this->_tags[$tag])) {
            if ($level) {
                return isset($this->_floats[$tag]);
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
     * @throws \LogicException
     * @return string
     */
    public function tag($tag, $tokenizer)
    {
        if (isset($this->_tags[$tag])) {
            return call_user_func($this->_tags[$tag], $tokenizer, $this);
        } else {
            throw new \LogicException("The block tag {$this->name} no have tag {$tag}");
        }
    }

    /**
     * Close callback
     *
     * @param Tokenizer $tokenizer
     * @throws \LogicException
     * @return string
     */
    public function end($tokenizer)
    {
        if ($this->_closed) {
            throw new \LogicException("Tag {$this->name} already closed");
        }
        if ($this->_close) {
            return call_user_func($this->_close, $tokenizer, $this);
        } else {
            throw new \LogicException("Can not use a inline tag {$this->name} as a block");
        }
    }

    /**
     * Forcefully close the tag
     */
    public function close()
    {
        $this->_closed = true;
    }

    /**
     * Return content of block
     *
     * @throws \LogicException
     * @return string
     */
    public function getContent()
    {
        return substr($this->_body, $this->_offset);
    }

    /**
     * Cut scope content
     *
     * @return string
     * @throws \LogicException
     */
    public function cutContent()
    {
        $content = substr($this->_body, $this->_offset + 1);
        $this->_body = substr($this->_body, 0, $this->_offset);
        return $content;
    }

    /**
     * Replace scope content
     *
     * @param $new_content
     */
    public function replaceContent($new_content)
    {
        $this->cutContent();
        $this->_body .= $new_content;
    }

    public function escape($code)
    {
        return $this->tpl->out($code);
    }

    public function optLtrim()
    {

    }

    public function optRtrim()
    {

    }

    public function optTrim()
    {

    }

    public function optRaw()
    {

    }

    public function optEscape()
    {

    }
}