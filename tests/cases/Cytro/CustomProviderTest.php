<?php

namespace Cytro;


class CustomProviderTest extends TestCase {

    public function setUp() {
        parent::setUp();
        $this->cytro->addProvider("my", new FSProvider(CYTRO_RESOURCES.'/provider'));
    }

    public function testCustom() {
        $this->assertRender("start: {include 'my:include.tpl'}", 'start: include template');
        //$this->assertRender("start: {import 'my:macros.tpl' as ops} {ops.add a=3 b=6}");
    }
}