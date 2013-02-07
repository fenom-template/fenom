<?php
namespace Aspect;
use Aspect,
    Aspect\Render;

class RenderTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Render
     */
    public static $render;

    public static function setUpBeforeClass() {
        self::$render = new Render("render.tpl", function ($tpl) {
            echo "It is render function ".$tpl["render"];
        }, array());
    }

    public function testCreate() {
        $r = new Render("test.render.tpl", function () {
            echo "Test render";
        }, array());
        $this->assertSame("Test render", $r->fetch(array()));
    }

    public function testDisplay() {
        ob_start();
        self::$render->display(array("render" => "display"));
        $out = ob_get_clean();
        $this->assertSame("It is render function display", $out);
    }

    public function testFetch() {
        $this->assertSame("It is render function fetch", self::$render->fetch(array("render" => "fetch")));
    }

    /**
     * @expectedException     RuntimeException
     * @expectedExceptionMessage template error
     */
    public function testFetchException() {
        $render = new Render("render.tpl", function ($tpl) {
            echo "error";
            throw new \RuntimeException("template error");
        });
        $render->fetch(array());
    }

}

