<?php
namespace Aspect\Template;
use Aspect, Aspect\Modifier, Aspect\TestCase;

class ExtendsTest extends TestCase {
    public static function providerExtends() {
        return array(
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}', "Template extended by block1"),
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} garbage', "Template extended by block1"),
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} {block "bk3"} block3 {/block} garbage', "Template multi-extended by block1"),
            array('{extends "parent.tpl"}{var $bk = "bk3"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} {block "$bk"} block3 {/block} garbage', "Template multi-extended by block1"),
        );
    }

    /**
     * @group extends
     */
    public function testParentLevel() {
	    //echo($this->aspect->getTemplate("parent.tpl")->_body); exit;
	    $this->assertSame($this->aspect->fetch("parent.tpl", array("a" => "a char")), "Parent template\nBlock1: Block2: Block3: default");
    }

	/**
	 * @group extends
	 */
	public function testChildLevel1() {
		//echo($this->aspect->fetch("child1.tpl", array("a" => "a char"))); exit;
	}

	/**
	 * @group extends
	 */
	public function _testChildLevel3() {
        echo($this->aspect->getTemplate("child3.tpl")->getBody()); exit;
	}
}

