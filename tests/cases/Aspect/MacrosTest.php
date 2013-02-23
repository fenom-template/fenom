<?php
namespace Aspect;

class MacrosTest extends TestCase {

    public function setUp() {
        parent::setUp();
        $this->tpl("math.tpl", '
        {macro plus(x, y)}
            x + y = {$x + $y}
        {/macro}

        {macro minus(x, y, z=0)}
            x - y - z = {$x - $y - $z}
        {/macro}

        Math: {macro.plus x=2 y=3}, {macro.minus x=10 y=4}
        ');

        $this->tpl("import.tpl", '
        {import "math.tpl"}
        {import "math.tpl" as math}

        Imp: {macro.plus x=1 y=2}, {math.minus x=6 y=2 z=1}
        ');
    }

    public function testMacros() {
        $tpl = $this->aspect->compile('math.tpl');

        $this->assertStringStartsWith('x + y = ', trim($tpl->macros["plus"]["body"]));
        $this->assertSame('Math: x + y = 5 , x - y - z = 6', Modifier::strip($tpl->fetch(array()), true));
    }

    public function testImport() {
        $tpl = $this->aspect->compile('import.tpl');

        $this->assertSame('Imp: x + y = 3 , x - y - z = 3', Modifier::strip($tpl->fetch(array()), true));
    }
}
