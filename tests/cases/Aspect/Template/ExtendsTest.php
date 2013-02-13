<?php
namespace Aspect\Template;
use Aspect, Aspect\Modifier;

class ExtendsTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Aspect
     */
    public static $aspect;

    public function setUp() {
        if(!file_exists(ASPECT_RESOURCES.'/compile')) {
            mkdir(ASPECT_RESOURCES.'/compile', 0777, true);
        } else {
            exec("rm -f ".ASPECT_RESOURCES.'/compile/*');
        }
        self::$aspect = Aspect::factory(ASPECT_RESOURCES.'/template', ASPECT_RESOURCES.'/compile');
    }

    public static function providerExtends() {
        return array(
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}', "Template extended by block1"),
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} garbage', "Template extended by block1"),
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} {block "bk3"} block3 {/block} garbage', "Template multi-extended by block1"),
            array('{extends "parent.tpl"}{var $bk = "bk3"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} {block "$bk"} block3 {/block} garbage', "Template multi-extended by block1"),
        );
    }

    public function exec($code, $vars, $result, $dump = false) {
        $tpl = self::$aspect->compileCode($code, "inline.tpl");
        if($dump) {
            echo "\n===========================\n".$code.": ".$tpl->getBody();
        }
        $this->assertSame(Modifier::strip($result), Modifier::strip($tpl->fetch($vars), true), "Test $code");
    }

    public function execError($code, $exception, $message, $options) {
        self::$aspect->setOptions($options);
        try {
            self::$aspect->compileCode($code, "inline.tpl");
        } catch(\Exception $e) {
            $this->assertSame($exception, get_class($e), "Exception $code");
            $this->assertStringStartsWith($message, $e->getMessage());
            self::$aspect->setOptions(0);
            return;
        }
        self::$aspect->setOptions(0);
        $this->fail("Code $code must be invalid");
    }

    /**
     * @group extends
     */
    public function testParent() {
	    //echo(self::$aspect->getTemplate("parent.tpl")->getBody()); exit;
    }

	/**
	 * @group extends
	 */
	public function ___testChildLevel1() {
		echo(self::$aspect->getTemplate("child1.tpl")->getBody()); exit;
	}

	/**
	 * @group extends
	 */
	public function __testExtends() {
		echo(self::$aspect->fetch("child1.tpl", array("a" => "value", "z" => ""))."\n"); exit;
	}
}

