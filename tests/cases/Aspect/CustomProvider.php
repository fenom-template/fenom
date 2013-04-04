<?php

namespace Cytro;


class CustomProvider extends TestCase {

    public function setUp() {
        $this->setUp();
        $this->cytro->addProvider("my", new FSProvider(CYTRO_RESOURCES.'/provider'));
    }

    public function testCustom() {
        $this->render("start: {include 'my:include.tpl'}", 'start: include template');
        $this->render("start: {import 'my:macros.tpl' as ops} {ops.add a=3 b=6}");
    }
}