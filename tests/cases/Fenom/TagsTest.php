<?php

namespace Fenom;


class TagsTest extends TestCase {

	public function _testSandbox() {
		try {
			var_dump($this->fenom->compileCode('{var $a=Fenom\TestCase::dots("asd")}')->getBody());
		} catch(\Exception $e) {
			echo "$e";
		}
		exit;
	}

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
		$this->assertRender('{for $i=0 to=4}{cycle ["one", "two"]}, {/for}', "one, two, one, two, one, ");
    }

	/**
	 *
	 */
	public function testCycleIndex() {
		$this->assertRender('{var $a=["one", "two"]}{for $i=1 to=5}{cycle $a index=$i}, {/for}', "two, one, two, one, two, ");
	}

	/**
	 * @dataProvider providerScalars
	 */
    public function testFilter($tpl_val, $val) {
	    $this->assertRender("{filter|up} before {{$tpl_val}} after {/filter}", strtoupper(" before {$val} after "));
    }

}