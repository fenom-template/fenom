<?php
namespace Fenom;
use Fenom, Fenom\TestCase;

class ExtendsTest extends TestCase
{

    public $template_path = 'provider';

    public function _testSandbox()
    {
        try {
//            var_dump($this->fenom->getTemplate("extends/static/nested/child.1.tpl")->fetch([]));
//            var_dump($this->fenom->getTemplate("extends/dynamic/child.2.tpl")->getBody());
//            var_dump($this->fenom->compileCode('{block "main"}a {parent} b{/block}')->getBody());
//            $child = $this->fenom->getRawTemplate()->load('autoextends/child.1.tpl', false);
//            $child->extend('autoextends/parent.tpl');
//            $child->compile();
//            print_r($child->getBody());
        } catch (\Exception $e) {
            echo "$e";
        }
        exit;
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
    public function testAutoExtends() {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame($result, $this->fenom->fetch(array(
            'extends/auto/child.3.tpl',
            'extends/auto/child.2.tpl',
            'extends/auto/child.1.tpl',
            'extends/auto/parent.tpl',
        ), array()));
    }

    public function testStaticExtendLevel1() {
        $result = "Before header
Content of the header
Before body
Child 1 Body
Before footer
Content of the footer";
        $this->assertSame($result, $this->fenom->fetch('extends/static/child.1.tpl', array()));
    }

    public function testStaticExtendLevel3() {
        $result = "Before header
Child 2 header
Before body
Child 3 content
Before footer
Footer from use";
        $this->assertSame($result, $this->fenom->fetch('extends/static/child.3.tpl', array()));
    }

    public function testStaticExtendNested() {
        $result = "Before body

    Before header
    Child 1: Content of the header
    Before footer
    Content of the footer
";
        $this->assertSame($result, $this->fenom->fetch('extends/static/nested/child.1.tpl', array()));
    }

    public function _testDynamicExtendLevel2() {
        $result = "Before header
Content of the header
Before body
Child 1 Body
Before footer
Content of the footer";
        $this->assertSame($result, $this->fenom->fetch('extends/dynamic/child.2.tpl', array()));
    }

}

