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


class Tag {

    /**
     * @var Template
     */
    public $tpl;
    public $name;
    public $options;

    public function __construct($name, Template $tpl) {
        $this->name = $name;
        $this->tpl = $tpl;
    }

    public function optLtrim() {

    }

    public function optRtrim() {

    }

    public function optTrim() {

    }

    public function optRaw() {

    }

    public function optEscape() {

    }
}