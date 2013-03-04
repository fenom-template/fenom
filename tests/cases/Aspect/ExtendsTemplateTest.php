<?php
namespace Aspect;
use Aspect, Aspect\Modifier, Aspect\TestCase;

class ExtendsTemplateTest extends TestCase {
    public static function providerExtends() {
        $a = array("one" => 1, "two" => 2, "three" => 3);
        return array(
            array("parent.tpl", "Parent. B1: {block b1}{/block}\nB2: {block 'b2'}empty {\$iteration}{/block}", $a,
                                "Parent. B1: \nB2: empty 0"),
            array("child1.tpl", '{extends "parent.tpl"} {block b1}from child1 {$iteration}{/block} some trash', $a,
                                "Parent. B1: from child1 1\nB2: empty 1"),
            array("child2.tpl", '{extends "child1.tpl"} {block "b2"}from child2 {$iteration}{/block} some {block b4}what is it?{/block} trash', $a,
                                "Parent. B1: from child1 2\nB2: from child2 2"),
            array("child3.tpl", '{extends "child2.tpl"} {block \'b1\'}from child3 {$iteration}{/block} {block "b2"}from child3 {$iteration}{/block} some {block b4}what is it?{/block} trash', $a,
                                "Parent. B1: from child3 3\nB2: from child3 3")
        );
    }

    public static function providerDynamicExtends() {
        $data = self::providerExtends();
        $data[2][1] = str_replace('"b2"', '"b{$two}"', $data[2][1]);
        return $data;
    }

    /**
     * @dataProvider providerExtends
     * @param $name
     * @param $code
     * @param $vars
     * @param $result
     */
    public function testStaticExtends($name, $code, $vars, $result) {
        static $i = 0;
        $vars["iteration"] = $i++;
        $this->execTpl($name, $code, $vars, $result);
    }

    /**
     * @dataProvider providerDynamicExtends
     * @param $name
     * @param $code
     * @param $vars
     * @param $result
     */
    public function testDynamicExtends($name, $code, $vars, $result) {
        static $i = 0;
        $vars["iteration"] = $i++;
        $this->execTpl($name, $code, $vars, $result, 0);
    }

    /**
     * @group extends
     */
    public function _testParentLevel() {
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

