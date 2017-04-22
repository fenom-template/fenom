<?php

namespace Fenom;


class RangeIterator implements \Iterator, \Countable
{

    public $current;
    public $index = 0;
    public $min;
    public $max;
    public $step;

    public function __construct($min, $max, $step = 1)
    {
        $this->min = $min;
        $this->max = $max;
        $this->setStep($step);
    }

    /**
     * @param int $step
     * @return $this
     */
    public function setStep($step)
    {
        if($step > 0) {
            $this->current = min($this->min, $this->max);
        } elseif($step < 0) {
            $this->current = max($this->min, $this->max);
        } else {
            $step = $this->max - $this->min;
            $this->current = $this->min;
        }
        $this->step = $step;
        return $this;
    }

    /**
     * Return the current element
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        $this->current += $this->step;
        $this->index++;
    }

    /**
     * Return the key of the current element
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid()
    {
        return $this->current >= $this->min && $this->current <= $this->max;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        if($this->step > 0) {
            $this->current = min($this->min, $this->max);
        } else {
            $this->current = max($this->min, $this->max);
        }
        $this->index = 0;
    }

    /**
     * Count elements of an object
     */
    public function count()
    {
        return intval(($this->max - $this->min + 1) / $this->step);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "[".implode(", ", range($this->min, $this->max, $this->step))."]";
    }
}