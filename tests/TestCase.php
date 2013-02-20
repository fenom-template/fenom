<?php
namespace Aspect;
use Aspect;

class TestCase extends \PHPUnit_Framework_TestCase {
    /**
     * @var Aspect
     */
    public $aspect;

    public function setUp() {
        if(!file_exists(ASPECT_RESOURCES.'/compile')) {
            mkdir(ASPECT_RESOURCES.'/compile', 0777, true);
        } else {
            Misc::clean(ASPECT_RESOURCES.'/compile/');
        }
        $this->aspect = Aspect::factory(ASPECT_RESOURCES.'/template', ASPECT_RESOURCES.'/compile');
    }

    public static function setUpBeforeClass() {
        if(!file_exists(ASPECT_RESOURCES.'/template')) {
            mkdir(ASPECT_RESOURCES.'/template', 0777, true);
        } else {
            Misc::clean(ASPECT_RESOURCES.'/template/');
        }
    }

    public function tpl($name, $code) {
        file_put_contents(ASPECT_RESOURCES.'/template/'.$name, $code);
    }

    /**
     * Compile and execute template
     *
     * @param string $code source of template
     * @param array $vars variables of template
     * @param string $result expected result.
     * @param bool $dump dump source and result code (for debug)
     */
    public function exec($code, $vars, $result, $dump = false) {
        $tpl = $this->aspect->compileCode($code, "runtime.tpl");
        if($dump) {
            echo "\n========= DUMP BEGIN ===========\n".$code."\n--- to ---\n".$tpl->getBody()."\n========= DUMP END =============\n";
        }
        $this->assertSame(Modifier::strip($result), Modifier::strip($tpl->fetch($vars), true), "Test $code");
    }

    public function execTpl($name, $code, $vars, $result, $dump = false) {
        $this->tpl($name, $code);
        $tpl = $this->aspect->getTemplate($name);
        if($dump) {
            echo "\n========= DUMP BEGIN ===========\n".$code."\n--- to ---\n".$tpl->getBody()."\n========= DUMP END =============\n";
        }
        $this->assertSame(Modifier::strip($result, true), Modifier::strip($tpl->fetch($vars), true), "Test tpl $name");
    }

    /**
     * Try to compile the invalid template
     * @param string $code source of the template
     * @param string $exception exception class
     * @param string $message exception message
     * @param int $options Aspect's options
     */
    public function execError($code, $exception, $message, $options = 0) {
        $opt = $this->aspect->getOptions();
        $this->aspect->setOptions($options);
        try {
            $this->aspect->compileCode($code, "inline.tpl");
        } catch(\Exception $e) {
            $this->assertSame($exception, get_class($e), "Exception $code");
            $this->assertStringStartsWith($message, $e->getMessage());
            $this->aspect->setOptions($opt);
            return;
        }
        self::$aspect->setOptions($opt);
        $this->fail("Code $code must be invalid");
    }
}
