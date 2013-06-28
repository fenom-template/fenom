<?php

namespace Fenom;


class CustomProviderTest extends TestCase {

    public function setUp() {
        parent::setUp();
        $this->fenom->addProvider("my", new Provider(FENOM_RESOURCES.'/provider'));
    }

    public function testCustom() {
        $this->assertRender("start: {include 'my:include.tpl'}", 'start: include template');
        //$this->assertRender("start: {import 'my:macros.tpl' as ops} {ops.add a=3 b=6}");
    }
}