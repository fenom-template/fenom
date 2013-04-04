<?php
namespace Cytro;
use Cytro, Cytro\FSProvider as FS;

class TestCase extends \PHPUnit_Framework_TestCase {
    /**
     * @var Cytro
     */
    public $cytro;

    public $values = array(
        "one" => 1,
        "two" => 2,
        "three" => 3,
        1 => "one value",
        2 => "two value",
        3 => "three value",
    );

    public function setUp() {
        if(!file_exists(CYTRO_RESOURCES.'/compile')) {
            mkdir(CYTRO_RESOURCES.'/compile', 0777, true);
        } else {
            FS::clean(CYTRO_RESOURCES.'/compile/');
        }
        $this->cytro = Cytro::factory(CYTRO_RESOURCES.'/template', CYTRO_RESOURCES.'/compile');
    }

    public static function setUpBeforeClass() {
        if(!file_exists(CYTRO_RESOURCES.'/template')) {
            mkdir(CYTRO_RESOURCES.'/template', 0777, true);
        } else {
            FS::clean(CYTRO_RESOURCES.'/template/');
        }
    }

    public function tpl($name, $code) {
        file_put_contents(CYTRO_RESOURCES.'/template/'.$name, $code);
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
        $tpl = $this->cytro->compileCode($code, "runtime.tpl");
        if($dump) {
            echo "\n========= DUMP BEGIN ===========\n".$code."\n--- to ---\n".$tpl->getBody()."\n========= DUMP END =============\n";
        }
        $this->assertSame(Modifier::strip($result), Modifier::strip($tpl->fetch($vars), true), "Test $code");
    }

    public function execTpl($name, $code, $vars, $result, $dump = false) {
        $this->tpl($name, $code);
        $tpl = $this->cytro->getTemplate($name);
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
     * @param int $options Cytro's options
     */
    public function execError($code, $exception, $message, $options = 0) {
        $opt = $this->cytro->getOptions();
        $this->cytro->setOptions($options);
        try {
            $this->cytro->compileCode($code, "inline.tpl");
        } catch(\Exception $e) {
            $this->assertSame($exception, get_class($e), "Exception $code");
            $this->assertStringStartsWith($message, $e->getMessage());
            $this->cytro->setOptions($opt);
            return;
        }
        $this->cytro->setOptions($opt);
        $this->fail("Code $code must be invalid");
    }

    public function assertRender($tpl, $result) {
        $template = $this->cytro->compileCode($tpl);
        $this->assertSame($result, $template->fetch($this->values));
    }
}

class Fake implements \ArrayAccess {
    public $vars;

    public function offsetExists($offset) {
        return true;
    }

    public function offsetGet($offset) {
        if($offset == "object") {
            return new self();
        } else {
            return new self($offset);
        }
    }

    public function offsetSet($offset, $value) {
        $this->vars[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->vars[$offset]);
    }
}
