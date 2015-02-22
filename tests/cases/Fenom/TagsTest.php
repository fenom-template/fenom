<?php

namespace Fenom;


class TagsTest extends TestCase
{
    /**
     * @group test-for
     */
//    public function testFor()
//    {
//        $this->assertRender('{for $i=0 to=3}{$i},{/for}', "0,1,2,3,");
//    }

    /**
     * @dataProvider providerScalars
     */
    public function testVar($tpl_val, $val)
    {
        $this->assertRender("{set \$a=$tpl_val}\nVar: {\$a}", "Var: " . $val);
    }

    /**
     * @dataProvider providerScalars
     */
    public function testVarBlock($tpl_val, $val)
    {
        $this->assertRender("{set \$a}before {{$tpl_val}} after{/set}\nVar: {\$a}", "Var: before " . $val . " after");
    }

    /**
     * @dataProvider providerScalars
     */
    public function testVarBlockModified($tpl_val, $val)
    {
        $this->assertRender(
            "{set \$a|low|dots}before {{$tpl_val}} after{/set}\nVar: {\$a}",
            "Var: " . strtolower("before " . $val . " after") . "..."
        );
    }

    public function testCycle()
    {
        $this->assertRender('{foreach 0..4 as $i}{cycle ["one", "two"]}, {/foreach}', "one, two, one, two, one, ");
    }

    /**
     *
     */
    public function testCycleIndex()
    {
        $this->assertRender(
            '{set $a=["one", "two"]}{foreach 1..5 as $i}{cycle $a index=$i}, {/foreach}',
            "two, one, two, one, two, "
        );
    }

    /**
     * @dataProvider providerScalars
     */
    public function testFilter($tpl_val, $val)
    {
        $this->assertRender("{filter|up} before {{$tpl_val}} after {/filter}", strtoupper(" before {$val} after "));
    }

}