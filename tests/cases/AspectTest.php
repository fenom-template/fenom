<?php

use Aspect\Render,
    Aspect\Misc;

class AspectTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Aspect
     */
    public $aspect;

    public static function tearDownAfterClass() {
        Misc::clean(ASPECT_RESOURCES.'/compile');
        Misc::rm(ASPECT_RESOURCES.'/template/custom.tpl');
    }

    public function setUp() {
        if(!file_exists(ASPECT_RESOURCES.'/compile')) {
            mkdir(ASPECT_RESOURCES.'/compile', 0777, true);
        }
        self::tearDownAfterClass();
        $this->aspect = $aspect = Aspect::factory(ASPECT_RESOURCES.'/template', ASPECT_RESOURCES.'/compile');
        $aspect->setCompileDir(ASPECT_RESOURCES.'/compile');
        $aspect->setForceCompile(false);
        $aspect->setCompileCheck(false);
    }

    public function tpl($code) {
        Misc::put(ASPECT_RESOURCES.'/template/custom.tpl', $code);
    }

    public function rmTpl() {
        Misc::rm(ASPECT_RESOURCES.'/template/custom.tpl');
    }

    public function testAddRender() {
        $test = $this;
        $this->aspect->addTemplate(new Render($this->aspect, function($tpl) use ($test) {
            /** @var \PHPUnit_Framework_TestCase $test  */
            $test->assertInstanceOf('Aspect\Render', $tpl);
            echo "Inline render";
        }, array(
            "name" => 'render.tpl'
        )));

        $this->assertSame("Inline render", $this->aspect->fetch('render.tpl', array()));
    }

    public function testCompileFile() {
        $a = array(
            "a" => "a",
            "b" => "b"
        );

        $this->assertSame("Template 1 a", $this->aspect->fetch('template1.tpl', $a));
        $this->assertSame("Template 2 b", $this->aspect->fetch('template2.tpl', $a));
        $this->assertInstanceOf('Aspect\Render', $this->aspect->getTemplate('template1.tpl'));
        $this->assertInstanceOf('Aspect\Render', $this->aspect->getTemplate('template2.tpl'));
        $this->assertSame(2, iterator_count(new FilesystemIterator(ASPECT_RESOURCES.'/compile')));
    }

    public function testStorage() {
        $this->tpl('Custom template');
        $this->assertSame("Custom template", $this->aspect->fetch('custom.tpl', array()));
        $this->rmTpl();

        $this->assertSame("Custom template", $this->aspect->fetch('custom.tpl', array()));

        $this->tpl('Custom template 2');
        $this->assertSame("Custom template", $this->aspect->fetch('custom.tpl', array()));
    }

    public function testCheckMTime() {
        $this->aspect->setCompileCheck(true);
        $this->tpl('Custom template');
        $this->assertSame("Custom template", $this->aspect->fetch('custom.tpl', array()));

        sleep(1);
        $this->tpl('Custom template (new)');
        $this->assertSame("Custom template (new)", $this->aspect->fetch('custom.tpl', array()));
    }

    public function testForceCompile() {
        $this->aspect->setForceCompile(true);
        $this->tpl('Custom template');
        $this->assertSame("Custom template", $this->aspect->fetch('custom.tpl', array()));
        $this->tpl('Custom template (new)');
        $this->assertSame("Custom template (new)", $this->aspect->fetch('custom.tpl', array()));
    }

    public function testSetModifier() {
        $this->aspect->addModifier("mymod", "myMod");
        $this->tpl('Custom modifier {$a|mymod}');
        $this->assertSame("Custom modifier (myMod)Custom(/myMod)", $this->aspect->fetch('custom.tpl', array("a" => "Custom")));
    }

    public function testSetFunctions() {
        $this->aspect->setForceCompile(true);
        $this->aspect->addFunction("myfunc", "myFunc");
        $this->aspect->addBlockFunction("myblockfunc", "myBlockFunc");
        $this->tpl('Custom function {myfunc name="foo"}');
        $this->assertSame("Custom function MyFunc:foo", $this->aspect->fetch('custom.tpl', array()));
        $this->tpl('Custom function {myblockfunc name="foo"} this block1 {/myblockfunc}');
        $this->assertSame("Custom function Block:foo:this block1:Block", $this->aspect->fetch('custom.tpl', array()));
    }

    public function testSetCompilers() {
        $this->aspect->setForceCompile(true);
        $this->aspect->addCompiler("mycompiler", 'myCompiler');
        $this->aspect->addBlockCompiler("myblockcompiler", 'myBlockCompilerOpen', 'myBlockCompilerClose', array(
            'tag' => 'myBlockCompilerTag'
        ));
        $this->tpl('Custom compiler {mycompiler name="bar"}');
        $this->assertSame("Custom compiler PHP_VERSION: ".PHP_VERSION." (for bar)", $this->aspect->fetch('custom.tpl', array()));
        $this->tpl('Custom compiler {myblockcompiler name="bar"} block1 {tag name="baz"} block2 {/myblockcompiler}');
        $this->assertSame("Custom compiler PHP_VERSION: ".PHP_VERSION." (for bar) block1 Tag baz of compiler block2 End of compiler", $this->aspect->fetch('custom.tpl', array()));
    }
}

?>
