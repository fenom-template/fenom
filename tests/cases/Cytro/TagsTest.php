<?php

namespace Cytro;


class TagsTest extends TestCase {

//	public function _testSandbox() {
//		try {
//			var_dump($this->cytro->compileCode(" literal: { \$a} end")->getBody());
//		} catch(\Exception $e) {
//			echo "$e";
//		}
//		exit;
//	}

    /**
     * @dataProvider providerScalars
     */
    public function testVar($tpl_val, $val) {
        $this->assertRender("{var \$a=$tpl_val}\nVar: {\$a}", "\nVar: ".$val);
    }

    /**
     * @dataProvider providerScalars
     */
    public function testVarBlock($tpl_val, $val) {
        $this->assertRender("{var \$a}before {{$tpl_val}} after{/var}\nVar: {\$a}", "\nVar: before ".$val." after");
    }

    /**
     * @dataProvider providerScalars
     */
    public function testVarBlockModified($tpl_val, $val) {
        $this->assertRender("{var \$a|low|dots}before {{$tpl_val}} after{/var}\nVar: {\$a}", "\nVar: ".strtolower("before ".$val." after")."...");
    }

    public function testCycle() {

    }


    public function testFilter() {

    }

}