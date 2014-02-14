<?php
namespace Fenom;
use Fenom, Fenom\TestCase;

class ExtendsTest extends TestCase
{

    public $template_path = 'provider';

    public function _testSandbox()
    {
        try {
            var_dump($this->fenom->getTemplate([
                'autoextends/child.3.tpl',
                'autoextends/child.2.tpl',
                'autoextends/child.1.tpl',
                'autoextends/parent.tpl',
            ])->getBody());
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

    public function testManualExtends()
    {
        $child = $this->fenom->getRawTemplate()->load('autoextends/child.1.tpl', false);
        $child->extend('autoextends/parent.tpl');
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
        $this->assertSame($result, $this->fenom->fetch([
            'autoextends/child.3.tpl',
            'autoextends/child.2.tpl',
            'autoextends/child.1.tpl',
            'autoextends/parent.tpl',
        ], array()));
    }

}

