<?php
namespace Aspect;

class MacrosTest extends TestCase {

    public function testMacros() {
        $tpl = $this->aspect->compileCode('
        {macro plus(x, y)}
            x + y = {$x + $y}
        {/macro}

        Math: {macro.plus x=2 y=3}
        ');

        $this->assertStringStartsWith('x + y = ', trim($tpl->macros["plus"]["body"]));
        $this->assertSame('Math: x + y = 5', Modifier::strip($tpl->fetch(array()), true));
    }
}
