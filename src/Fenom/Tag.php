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
    const FUNC     = 2;
    const BLOCK    = 4;


    const LTRIM = 1;
    const RTRIM = 2;

    /**
     * @var Template
     */
    public Template $tpl;
    public string $name;
    public array $options = [];
    public int $line = 0;
    public int $level = 0;
    public mixed $callback;
    public bool $escape;

    private int $_offset = 0;
    private bool $_closed = true;
    private string $_body;
    private int $_type = 0;
    private mixed $_open;
    private mixed $_close;
    private array $_tags = [];
    private array $_floats = [];
    private array $_changed = [];

    /**
     * Create tag entity
     * @param string $name the tag name
     * @param Template $tpl current template
     * @param array $info tag's information
     * @param string $body template's code
     */
    public function __construct(string $name, Template $tpl, array $info, string &$body)
    {
        parent::__construct();
        $this->tpl     = $tpl;
        $this->name    = $name;
        $this->line    = $tpl->getLine();
        $this->level   = $tpl->getStackSize();
        $this->_body   = & $body;
        $this->_offset = strlen($body);
        $this->_type   = $info["type"];
        $this->escape  = $tpl->getOptions() & \Fenom::AUTO_ESCAPE;

        if ($this->_type & self::BLOCK) {
            $this->_open   = $info["open"];
            $this->_close  = $info["close"];
            $this->_tags   = $info["tags"] ?? [];
            $this->_floats = $info["float_tags"] ?? [];
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
     * @throws \RuntimeException
     */
    public function tagOption(string $option)
    {
        if (method_exists($this, 'opt' . $option)) {
            $this->options[] = $option;
        } else {
            throw new \RuntimeException("Unknown tag option $option");
        }
    }

    /**
     * Rewrite template option for tag. When tag will be closed option will be reverted.
     * @param int $option option constant
     * @param bool $value true — add option, false — remove option
     */
    public function setOption(int $option, bool $value)
    {
        $actual = (bool)($this->tpl->getOptions() & $option);
        if ($actual != $value) {
            $this->_changed[$option] = $actual;
            $this->tpl->setOption(\Fenom::AUTO_ESCAPE, $value);
        }
    }

    /**
     * Restore the option
     * @param int $option
     */
    public function restore(int $option)
    {
        if (isset($this->_changed[$option])) {
            $this->tpl->setOption($option, $this->_changed[$option]);
            unset($this->_changed[$option]);
        }
    }

    public function restoreAll()
    {
        foreach ($this->_changed as $option => $value) {
            $this->tpl->setOption($option, $this->_changed[$option]);
            unset($this->_changed[$option]);
        }
    }

    /**
     * Check, if the tag closed
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->_closed;
    }

    /**
     * Open callback
     *
     * @param Tokenizer $tokenizer
     * @return mixed
     */
    public function start(Tokenizer $tokenizer): mixed
    {
        foreach ($this->options as $option) {
            $option = 'opt' . $option;
            $this->$option();
        }
        return call_user_func($this->_open, $tokenizer, $this);
    }

    /**
     * Check, has the block this tag
     *
     * @param string $tag
     * @param int $level
     * @return bool
     */
    public function hasTag(string $tag, int $level): bool
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
     * @return string
     * @throws \LogicException
     */
    public function tag(string $tag, Tokenizer $tokenizer): string
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
     * @return string
     * @throws \LogicException
     */
    public function end(Tokenizer $tokenizer): string
    {
        if ($this->_closed) {
            throw new \LogicException("Tag {$this->name} already closed");
        }
        if ($this->_close) {
            foreach ($this->options as $option) {
                $option = 'opt' . $option . 'end';
                if (method_exists($this, $option)) {
                    $this->$option();
                }
            }
            $code = call_user_func($this->_close, $tokenizer, $this);
            $this->restoreAll();
            return (string)$code;
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
     * Returns tag's content
     *
     * @throws \LogicException
     * @return string
     */
    public function getContent(): string
    {
        return substr($this->_body, $this->_offset);
    }

    /**
     * Cut tag's content
     *
     * @return string
     * @throws \LogicException
     */
    public function cutContent(): string
    {
        $content     = substr($this->_body, $this->_offset);
        $this->_body = substr($this->_body, 0, $this->_offset);
        return $content;
    }

    /**
     * Replace tag's content
     *
     * @param $new_content
     */
    public function replaceContent($new_content)
    {
        $this->cutContent();
        $this->_body .= $new_content;
    }

    /**
     * Generate output code
     * @param string $code
     * @return string
     */
    public function out(string $code): string
    {
        return $this->tpl->out($code, $this->escape);
    }

    /**
     * Enable escape option for the tag
     */
    public function optEscape()
    {
        $this->escape = true;
    }

    /**
     * Disable escape option for the tag
     */
    public function optRaw()
    {
        $this->escape = false;
    }

    /**
     * Enable strip spaces option for the tag
     */
    public function optStrip()
    {
        $this->setOption(\Fenom::AUTO_STRIP, true);
    }

    /**
     * Enable ignore for body of the tag
     */
    public function optIgnore()
    {
        if(!$this->isClosed()) {
            $this->tpl->ignore($this->name);
        }
    }
}