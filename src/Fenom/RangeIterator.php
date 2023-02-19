<?php

namespace Fenom;

use Countable;
use Iterator;

class RangeIterator implements Iterator, Countable
{

    public int $current;
    public int $index = 0;
    public int $min;
    public int $max;
    public int $step;

    public function __construct(int $min, int $max, int $step = 1)
    {
        $this->min = $min;
        $this->max = $max;
        $this->setStep($step);
    }

    /**
     * @param int $step
     * @return $this
     */
    public function setStep(int $step): static
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
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * Move forward to next element
     */
    public function next(): void
    {
        $this->current += $this->step;
        $this->index++;
    }

    /**
     * Return the key of the current element
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->index;
    }

    /**
     * Checks if current position is valid
     * @return bool
     */
    public function valid(): bool
    {
        return $this->current >= $this->min && $this->current <= $this->max;
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind(): void
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
    public function count(): int
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