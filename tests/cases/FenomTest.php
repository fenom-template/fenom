<?php

class FenomTest extends \Fenom\TestCase
{

    public static function providerOptions()
    {
        return array(
            array("disable_methods", Fenom::DENY_METHODS),
            array("disable_native_funcs", Fenom::DENY_NATIVE_FUNCS),
            array("disable_cache", Fenom::DISABLE_CACHE),
            array("force_compile", Fenom::FORCE_COMPILE),
            array("auto_reload", Fenom::AUTO_RELOAD),
            array("force_include", Fenom::FORCE_INCLUDE),
            array("auto_escape", Fenom::AUTO_ESCAPE),
            array("force_verify", Fenom::FORCE_VERIFY),
            array("strip", Fenom::AUTO_STRIP),
        );
    }


    public function testCreating()
    {
        $time  = $this->tpl('temp.tpl', 'Template 1 a');
        $fenom = new Fenom($provider = new \Fenom\Provider(FENOM_RESOURCES . '/template'));
        $fenom->setCompileDir(FENOM_RESOURCES . '/compile');
        $this->assertInstanceOf('Fenom\Template', $tpl = $fenom->getTemplate('temp.tpl'));
        $this->assertSame($provider, $tpl->getProvider());
        $this->assertSame('temp.tpl', $tpl->getBaseName());
        $this->assertSame('temp.tpl', $tpl->getName());
        $this->assertSame($time, $tpl->getTime());
        $fenom->clearAllCompiles();
    }

    public function testFactory()
    {
        $time  = $this->tpl('temp.tpl', 'Template 1 a');
        $fenom = Fenom::factory(
            $provider = new \Fenom\Provider(FENOM_RESOURCES . '/template'),
            FENOM_RESOURCES . '/compile',
            Fenom::AUTO_ESCAPE
        );
        $this->assertInstanceOf('Fenom\Template', $tpl = $fenom->getTemplate('temp.tpl'));
        $this->assertSame($provider, $tpl->getProvider());
        $this->assertSame('temp.tpl', $tpl->getBaseName());
        $this->assertSame('temp.tpl', $tpl->getName());
        $this->assertSame($time, $tpl->getTime());
        $fenom->clearAllCompiles();
    }


    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Cache directory /invalid/path is not writable
     */
    public function testFactoryInvalid()
    {
        Fenom::factory(FENOM_RESOURCES . '/template', '/invalid/path');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Source must be a valid path or provider object
     */
    public function testFactoryInvalid2()
    {
        Fenom::factory(new StdClass);
    }

    public function testCompileFile()
    {
        $a = array(
            "a" => "a",
            "b" => "b"
        );
        $this->tpl('template1.tpl', 'Template 1 a');
        $this->tpl('template2.tpl', 'Template 2 b');
        $this->assertSame("Template 1 a", $this->fenom->fetch('template1.tpl', $a));
        $this->assertSame("Template 2 b", $this->fenom->fetch('template2.tpl', $a));
        $this->assertInstanceOf('Fenom\Render', $this->fenom->getTemplate('template1.tpl'));
        $this->assertInstanceOf('Fenom\Render', $this->fenom->getTemplate('template2.tpl'));
        $this->assertSame(3, iterator_count(new FilesystemIterator(FENOM_RESOURCES . '/compile')));
    }

    public function testStorage()
    {
        $this->tpl('custom.tpl', 'Custom template');
        $this->assertSame("Custom template", $this->fenom->fetch('custom.tpl', array()));
        $this->tpl('custom.tpl', 'Custom template 2');
        $this->assertSame("Custom template", $this->fenom->fetch('custom.tpl', array()));
    }

    /**
     * @group testCheckMTime
     */
    public function testCheckMTime()
    {
        $this->fenom->setOptions(Fenom::FORCE_COMPILE);
        $this->fenom->getProvider()->setClearCachedStats();
        $this->tpl('custom.tpl', 'Custom template');
        $this->assertSame("Custom template", $this->fenom->fetch('custom.tpl', array()));
        $tpl = $this->fenom->getTemplate('custom.tpl');
        $this->assertTrue($tpl->isValid());
        usleep(1.5e6);
        $this->tpl('custom.tpl', 'Custom template (new)');
        $this->assertFalse($tpl->isValid());
        $this->assertSame("Custom template (new)", $this->fenom->fetch('custom.tpl', array()));
    }

    public function testForceCompile()
    {
        $this->fenom->setOptions(Fenom::FORCE_COMPILE);
        $this->tpl('custom.tpl', 'Custom template');
        $this->assertSame("Custom template", $this->fenom->fetch('custom.tpl', array()));
        $this->tpl('custom.tpl', 'Custom template (new)');
        $this->assertSame("Custom template (new)", $this->fenom->fetch('custom.tpl', array()));
    }

    /**
     * @group dev
     */
    public function testCompileIdAndName()
    {
        $this->fenom->setCompileId("iddqd.");
        $this->tpl('custom.tpl', 'Custom template');
        $this->assertSame("Custom template", $this->fenom->fetch('custom.tpl', array()));
        $compile_name = $this->fenom->getCompileName('custom.tpl');
        $this->assertFileExists($this->getCompilePath().'/'.$compile_name);
        $this->assertStringStartsWith('iddqd.', $compile_name);
    }

    public function testSetModifier()
    {
        $this->fenom->addModifier("mymod", "myMod");
        $this->assertRender(
            'Custom modifier {$a|mymod}',
            "Custom modifier (myMod)Custom(/myMod)",
            array("a" => "Custom")
        );
        $this->assertRender(
            'Custom modifier {mymod($a)}',
            "Custom modifier (myMod)Custom(/myMod)",
            array("a" => "Custom")
        );
    }

    public function testSetModifierClosure()
    {
        $this->fenom->addModifier("mymod", function ($value) {
            return "(myMod)$value(/myMod)";
        });
        $this->assertRender(
            'Custom modifier {$a|mymod}',
            "Custom modifier (myMod)Custom(/myMod)",
            array("a" => "Custom")
        );
        $this->assertRender(
            'Custom modifier {mymod($a)}',
            "Custom modifier (myMod)Custom(/myMod)",
            array("a" => "Custom")
        );
    }

    /**
     * @group add_functions
     */
    public function testSetFunctions()
    {
        $test = $this;
        $this->fenom->setOptions(Fenom::FORCE_COMPILE);
        $this->fenom->addFunction("myfunc", "myFunc");
        $this->fenom->addFunction("myfunc2", function ($args, $tpl) use ($test) {
            $test->assertInstanceOf('Fenom\Render', $tpl);
            $test->assertSame(array(
                "name" => "foo"
            ), $args);
            return "MyFunc2:".$args['name'];
        });
        $this->fenom->addBlockFunction("myblockfunc", "myBlockFunc");
        $this->fenom->addBlockFunction("myblockfunc2", function ($args, $content, $tpl) use ($test) {
            $test->assertInstanceOf('Fenom\Render', $tpl);
            $test->assertSame(array(
                    "name" => "foo"
                ), $args);
            $test->assertSame(' this block1 ', $content);
            return "Block2:" . $args["name"] . ':' . trim($content) . ':Block';
        });
        $this->tpl('custom.tpl', 'Custom function {myfunc name="foo"}');
        $this->assertSame("Custom function MyFunc:foo", $this->fenom->fetch('custom.tpl', array()));
        $this->tpl('custom.tpl', 'Custom function {myfunc2 name="foo"}');
        $this->assertSame("Custom function MyFunc2:foo", $this->fenom->fetch('custom.tpl', array()));
        $this->tpl('custom.tpl', 'Custom function {myblockfunc name="foo"} this block1 {/myblockfunc}');
        $this->assertSame("Custom function Block:foo:this block1:Block", $this->fenom->fetch('custom.tpl', array()));
        $this->tpl('custom.tpl', 'Custom function {myblockfunc2 name="foo"} this block1 {/myblockfunc2}');
        $this->assertSame("Custom function Block2:foo:this block1:Block", $this->fenom->fetch('custom.tpl', array()));
    }

    public function testSetCompilers()
    {
        $this->fenom->setOptions(Fenom::FORCE_COMPILE);
        $this->fenom->addCompiler("mycompiler", 'myCompiler');
        $this->fenom->addBlockCompiler(
            "myblockcompiler",
            'myBlockCompilerOpen',
            'myBlockCompilerClose',
            array(
                'tag' => 'myBlockCompilerTag'
            )
        );
        $this->tpl('custom.tpl', 'Custom compiler {mycompiler name="bar"}');
        $this->assertSame(
            "Custom compiler PHP_VERSION: " . PHP_VERSION . " (for bar)",
            $this->fenom->fetch('custom.tpl', array())
        );
        $this->tpl(
            'custom.tpl',
            'Custom compiler {myblockcompiler name="bar"} block1 {tag name="baz"} block2 {/myblockcompiler}'
        );
        $this->assertSame(
            "Custom compiler PHP_VERSION: " . PHP_VERSION . " (for bar) block1 Tag baz of compiler block2 End of compiler",
            $this->fenom->fetch('custom.tpl', array())
        );
    }

    /**
     * @dataProvider providerOptions
     */
    public function testOptions($code, $option)
    {
        static $options = array();
        static $flags = 0;
        $options[$code] = true;
        $flags |= $option;

        $this->fenom->setOptions($options);
        $this->assertSame($this->fenom->getOptions(), $flags);
        $this->fenom->setOptions(array($code => false));
        $this->assertSame($this->fenom->getOptions(), $flags & ~$option);
    }

    public function testFilter()
    {
        $punit = $this;
        $this->fenom->addPreFilter(
            function ($tpl, $src) use ($punit) {
                $punit->assertInstanceOf('Fenom\Template', $tpl);
                return "== $src ==";
            }
        );

        $this->fenom->addPostFilter(
            function ($tpl, $code) use ($punit) {
                $punit->assertInstanceOf('Fenom\Template', $tpl);
                return "+++ $code +++";
            }
        );

        $this->fenom->addFilter(
            function ($tpl, $text) use ($punit) {
                $punit->assertInstanceOf('Fenom\Template', $tpl);
                return "|--- $text ---|";
            }
        );

        $this->assertSame(
            '+++ |--- == hello  ---||---  world == ---| +++',
            $this->fenom->compileCode('hello {var $user} misterio {/var} world')->fetch(array())
        );
        $this->assertSame(
            '+++ |--- == hello  ---||---  world == ---| +++',
            $this->fenom->compileCode('hello {var $user} <?php  misterio ?> {/var} world')->fetch(array())
        );
    }

    /**
     * @group tag-filter
     */
    public function testTagFilter()
    {
        $tags  = array();
        $punit = $this;
        $this->fenom->addTagFilter(
            function ($text, $tpl) use (&$tags, $punit) {
                $punit->assertInstanceOf('Fenom\Template', $tpl);
                $tags[] = $text;
                return $text;
            }
        );

        $this->fenom->compileCode('hello {var $a} misterio {/var} world, {$b}!');

        $this->assertSame(array('var $a', '/var', '$b'), $tags);
    }

    public function testAddInlineCompilerSmart()
    {
        $this->fenom->addCompilerSmart('SayA', 'TestTags');
        $this->tpl('inline_compiler.tpl', 'I just {SayA}.');
        $this->assertSame('I just Say A.', $this->fenom->fetch('inline_compiler.tpl', array()));
    }

    public function testAddBlockCompilerSmart()
    {
        $this->fenom->addBlockCompilerSmart('SayBlock', 'TestTags', array('SaySomething'), array('SaySomething'));
        $this->tpl('block_compiler.tpl', '{SayBlock} and {SaySomething}. It is all, {/SayBlock}');
        $this->assertSame(
            'Start saying and say blah-blah-blah. It is all, Stop saying',
            $this->fenom->fetch('block_compiler.tpl', array())
        );
    }

    public function testAddAllowedFunctions()
    {
        $this->fenom->setOptions(Fenom::DENY_NATIVE_FUNCS);
        $this->assertFalse($this->fenom->isAllowedFunction('substr'));
        $this->fenom->addAllowedFunctions(array('substr'));
        $this->assertTrue($this->fenom->isAllowedFunction('substr'));
    }




    /**
     * @requires PHP 5.4
     * @group pipe
     */
    public function testPipe()
    {
        $iteration = 0;
        $test      = $this; // for PHP 5.3
        $this->fenom->pipe(
            "persist:pipe.tpl",
            function ($chunk) use (&$iteration, $test) {
                if (!$chunk) {
                    return;
                }
                $iteration++;
//                error_log(var_export($chunk, 1));
                $test->assertSame("key$iteration:value$iteration", $chunk);
            },
            array(
                "items" => array(
                    "key1" => "value1",
                    "key2" => "value2",
                    "key3" => "value3",
                )
            ),
            11 // strlen(key1) + strlen(:) +  strlen(value1)
        );
        $this->assertSame(3, $iteration);
    }

    /**
     * @group strip
     */
    public function testStrip() {
        $this->fenom->setOptions(Fenom::AUTO_STRIP);
        $tpl = <<<TPL
<div class="item   item-one">
    <a href="/item/{\$one}">number  {\$num.1}</a>
</div>
TPL;
        $this->assertRender($tpl, '<div class="item item-one"><a href="/item/1">number one</a></div>');
    }

    /**
     * Bug #195
     * @group strip-xml
     */
    public function testStripXML() {
        $this->fenom->setOptions(Fenom::AUTO_STRIP);
        $tpl = <<<TPL
<?xml version="1.0"?>
TPL;
        $this->assertRender($tpl, '<?xml version="1.0"?>');
    }
}


class TestTags
{

    public static function tagSayA()
    {
        return 'echo "Say A"';
    }

    public static function SayBlockOpen()
    {
        return 'echo "Start saying"';
    }

    public static function tagSaySomething()
    {
        return 'echo "say blah-blah-blah"';
    }

    public static function SayBlockClose()
    {
        return 'echo "Stop saying"';
    }
}
