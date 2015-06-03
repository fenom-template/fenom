<?php
namespace Fenom;

class MacrosTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->tpl("math.tpl",
           '{macro plus(x, y)}
              x + y = {$x + $y}
            {/macro}

            {macro minus(x, y, z=0)}
              x - y - z = {$x - $y - $z}
            {/macro}

            {macro multi(x, y)}
               x * y = {$x * $y}
            {/macro}

            {macro e()}
               2.71827
            {/macro}

            {macro pi}
               3.14159
            {/macro}

               Math: {macro.plus x=2 y=3}, {macro.minus x=10 y=4}
                   '
        );

        $this->tpl(
            "import.tpl",
            '
                   {import "math.tpl"}
                   {import "math.tpl" as math}

                   Imp: {macro.plus x=1 y=2}, {math.minus x=6 y=2 z=1}
                   '
        );

        $this->tpl(
            "import_custom.tpl",
            '
                   {macro minus($x, $y)}
                       new minus macros
                   {/macro}
                   {import [plus, minus] from "math.tpl" as math}

                   a: {math.plus x=1 y=2}, {math.minus x=6 y=2 z=1}, {macro.minus x=5 y=3}.
                   '
        );

        $this->tpl(
            "import_miss.tpl",
            '
                   {import [minus] from "math.tpl" as math}

                   a: {macro.plus x=5 y=3}.
                   '
        );

        $this->tpl(
            "macro_recursive.tpl",
            '{macro factorial(num)}
                       {if $num}
                           {$num} {macro.factorial num=$num-1} {$num}
                       {/if}
                   {/macro}

                   {macro.factorial num=10}'
        );

        $this->tpl(
            "macro_recursive_import.tpl",
            '
                   {import [factorial] from "macro_recursive.tpl" as math}

                   {math.factorial num=10}'
        );
    }

    /**
     * @throws \Exception
     * @group macros
     */
    public function testMacros()
    {
        $tpl = $this->fenom->compile('math.tpl');

        $this->assertStringStartsWith('x + y = ', trim($tpl->macros["plus"]["body"]));
        $this->assertSame('Math: x + y = 5 , x - y - z = 6', Modifier::strip($tpl->fetch(array()), true));
    }

    public function testImport()
    {
        $tpl = $this->fenom->compile('import.tpl');

        $this->assertSame('Imp: x + y = 3 , x - y - z = 3', Modifier::strip($tpl->fetch(array()), true));
    }

    /**
     * @group importCustom
     */
    public function testImportCustom()
    {
        $tpl = $this->fenom->compile('import_custom.tpl');

        $this->assertSame(
            'a: x + y = 3 , x - y - z = 3 , new minus macros .',
            Modifier::strip($tpl->fetch(array()), true)
        );
    }

    /**
     * @expectedExceptionMessage Undefined macro 'plus'
     * @expectedException \Fenom\Error\CompileException
     */
    public function testImportMiss()
    {
        $tpl = $this->fenom->compile('import_miss.tpl');

        $this->assertSame(
            'a: x + y = 3 , x - y - z = 3 , new minus macros .',
            Modifier::strip($tpl->fetch(array()), true)
        );
    }

    /**
     * @group macro-recursive
     */
    public function testRecursive()
    {
        $this->fenom->compile('macro_recursive.tpl');
        $this->fenom->flush();
        $tpl = $this->fenom->getTemplate('macro_recursive.tpl');
        $this->assertSame("10 9 8 7 6 5 4 3 2 1 1 2 3 4 5 6 7 8 9 10", Modifier::strip($tpl->fetch(array()), true));
    }


    public function testImportRecursive()
    {
        $this->fenom->compile('macro_recursive_import.tpl');
        $this->fenom->flush();
        $tpl = $this->fenom->getTemplate('macro_recursive.tpl');
        $this->assertSame("10 9 8 7 6 5 4 3 2 1 1 2 3 4 5 6 7 8 9 10", Modifier::strip($tpl->fetch(array()), true));
    }
}
