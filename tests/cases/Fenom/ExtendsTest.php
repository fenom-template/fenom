<?php
namespace Fenom;

use Fenom, Fenom\TestCase;

class ExtendsTest extends TestCase
{

    public $template_path = 'provider';

    public function _testSandbox()
    {
        try {
            var_dump($this->fenom->getTemplate('extends/dynamic/child.3.tpl')->getBody());
        } catch (\Exception $e) {
            echo "$e";
        }
        exit;
    }

    public static function providerExtendsInvalid()
    {
        return array(
            array(
                '{extends "extends/dynamic/child.3.tpl"} {extends "extends/dynamic/child.3.tpl"}',
                'Fenom\Error\CompileException',
                "Only one {extends} allowed"
            ),
            array(
                '{if true}{extends "extends/dynamic/child.3.tpl"}{/if}',
                'Fenom\Error\CompileException',
                "Tag {extends} can not be nested"
            ),
            array(
                '{if true}{use "extends/dynamic/use.tpl"}{/if}',
                'Fenom\Error\CompileException',
                "Tag {use} can not be nested"
            ),
            array('{use $use_this}', 'Fenom\Error\CompileException', "Invalid template name for tag {use}"),
            array('{block $use_this}{/block}', 'Fenom\Error\CompileException', "Invalid block name"),
        );
    }

    public function testAutoExtendsManual()
    {
        $child = $this->fenom->getRawTemplate()->load('extends/auto/child.1.tpl', false);
        $child->extend('extends/auto/parent.tpl');
        $child->compile();
        $result = "Before header
Content of the header
Before body
Child 1 Body
Before footer
Content of the footer";
        $this->assertSame($result, $child->fetch(array()));
    }

    /**
     * @group testAutoExtends
     */
    public function testAutoExtends()
    {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame(
            $result,
            $this->fenom->fetch(
                array(
                    'extends/auto/child.3.tpl',
                    'extends/auto/child.2.tpl',
                    'extends/auto/child.1.tpl',
                    'extends/auto/parent.tpl',
                ),
                array()
            )
        );
    }

    public function testStaticExtendLevel1()
    {
        $result = "Before header
Content of the header
Before body
Child 1 Body
Before footer
Content of the footer";
        $this->assertSame($result, $this->fenom->fetch('extends/static/child.1.tpl', array()));
    }

    public function testStaticExtendLevel3()
    {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame($result, $this->fenom->fetch('extends/static/child.3.tpl', array()));
    }

    public function testAutoAndStaticExtend()
    {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame(
            $result,
            $this->fenom->fetch(
                array(
                    'extends/auto/child.3.tpl',
                    'extends/auto/child.2.tpl',
                    'extends/auto/static/child.1.tpl'
                ),
                array()
            )
        );
    }

    public function testStaticExtendNested()
    {
        $result = "Before body

    Before header
    Child 1: Content of the header
    Before footer
    Content of the footer
";
        $this->assertSame($result, $this->fenom->fetch('extends/static/nested/child.1.tpl', array()));
    }

    public function testDynamicExtendLevel2()
    {
        $result = "Before header
Child 2 header
Before body
Child 1 Body
Before footer
Footer from use";
        $this->assertSame($result, $this->fenom->fetch('extends/dynamic/child.2.tpl', array()));
    }

    public function testDynamicExtendLevel3()
    {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame($result, $this->fenom->fetch('extends/dynamic/child.3.tpl', array()));
    }

    public function testDynamicExtendLevel4()
    {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame($result, $this->fenom->fetch('extends/dynamic/child.4.tpl', array()));
    }

    /**
     * @group static-invalid
     * @dataProvider providerExtendsInvalid
     */
    public function testExtendsInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }
}

