<?php

namespace Fenom;


class CommentTest extends TestCase
{

    /**
     * @dataProvider providerScalars
     */
    public function testInline($tpl_val)
    {
        $this->assertRender("before {* $tpl_val *} after", "before  after");
        $this->assertRender("before {* {{$tpl_val}} {{$tpl_val}} *} after", "before  after");
        $this->assertRender("before {*{{$tpl_val}}*} after", "before  after");
    }

    public function testError()
    {
        $this->execError('{* ', 'Fenom\Error\CompileException', "Unclosed comment block in line");
    }

    /**
     * @dataProvider providerScalars
     */
    public function testMultiLine($tpl_val)
    {
        $this->assertRender(
            "before-1\nbefore-2 {* before-3\nbefore-4 $tpl_val after-1\nafter-2 *} after-3\nafter-4{* dummy *}\nafter-5",
            "before-1\nbefore-2  after-3\nafter-4\nafter-5"
        );
    }

}