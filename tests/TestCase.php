<?php
namespace Fenom;
use Fenom, Fenom\Provider as FS;

class TestCase extends \PHPUnit_Framework_TestCase {
    /**
     * @var Fenom
     */
    public $fenom;

    public $values = array(
        "one" => 1,
        "two" => 2,
        "three" => 3,
        1 => "one value",
        2 => "two value",
        3 => "three value",
    );

    public function setUp() {
        if(!file_exists(FENOM_RESOURCES.'/compile')) {
            mkdir(FENOM_RESOURCES.'/compile', 0777, true);
        } else {
            FS::clean(FENOM_RESOURCES.'/compile/');
        }
        $this->fenom = Fenom::factory(FENOM_RESOURCES.'/template', FENOM_RESOURCES.'/compile');
        $this->fenom->addModifier('dots', __CLASS__.'::dots');
        $this->fenom->addModifier('concat', __CLASS__.'::concat');
        $this->fenom->addFunction('test_function', __CLASS__.'::inlineFunction');
        $this->fenom->addBlockFunction('test_block_function', __CLASS__.'::blockFunction');
    }

    public static function dots($value) {
        return $value."...";
    }

	public static function concat() {
		return call_user_func_array('var_export', func_get_args());
	}

    public static function inlineFunction($params) {
        return isset($params["text"]) ? $params["text"] : "";
    }

    public static function blockFunction($params, $text) {
        return $text;
    }

    public static function setUpBeforeClass() {
        if(!file_exists(FENOM_RESOURCES.'/template')) {
            mkdir(FENOM_RESOURCES.'/template', 0777, true);
        } else {
            FS::clean(FENOM_RESOURCES.'/template/');
        }
    }

    public function tpl($name, $code) {
        file_put_contents(FENOM_RESOURCES.'/template/'.$name, $code);
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
        $tpl = $this->fenom->compileCode($code, "runtime.tpl");
        if($dump) {
            echo "\n========= DUMP BEGIN ===========\n".$code."\n--- to ---\n".$tpl->getBody()."\n========= DUMP END =============\n";
        }
        $this->assertSame(Modifier::strip($result), Modifier::strip($tpl->fetch($vars), true), "Test $code");
    }

    public function execTpl($name, $code, $vars, $result, $dump = false) {
        $this->tpl($name, $code);
        $tpl = $this->fenom->getTemplate($name);
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
     * @param int $options Fenom's options
     */
    public function execError($code, $exception, $message, $options = 0) {
        $opt = $this->fenom->getOptions();
        $this->fenom->setOptions($options);
        try {
            $this->fenom->compileCode($code, "inline.tpl");
        } catch(\Exception $e) {
            $this->assertSame($exception, get_class($e), "Exception $code");
            $this->assertStringStartsWith($message, $e->getMessage());
            $this->fenom->setOptions($opt);
            return;
        }
        $this->fenom->setOptions($opt);
        $this->fail("Code $code must be invalid");
    }

    public function assertRender($tpl, $result, $debug = false) {
        $template = $this->fenom->compileCode($tpl);
        if($debug) {
            print_r("$tpl:\n".$template->getBody());
        }
        $this->assertSame($result, $template->fetch($this->values));
    }


	public static function providerNumbers() {
		return array(
			array('0', 0),
			array('77', 77),
			array('-33', -33),
			array('0.2', 0.2),
			array('-0.3', -0.3),
			array('1e6', 1e6),
			array('-2e6', -2e6),
		);
	}

    public static function providerStrings() {
        return array(
            array('"str"', 'str'),
            array('"str\nand\nmany\nlines"', "str\nand\nmany\nlines"),
            array('"str and \'substr\'"', "str and 'substr'"),
            array('"str and \"substr\""', 'str and "substr"'),
            array("'str'", 'str'),
            array("'str\\nin\\none\\nline'", 'str\nin\none\nline'),
            array("'str and \"substr\"'", 'str and "substr"'),
            array("'str and \'substr\''", "str and 'substr'"),
            array('"$one"', '1'),
            array('"$one $two"', '1 2'),
            array('"$one and $two"', '1 and 2'),
            array('"a $one and $two b"', 'a 1 and 2 b'),
            array('"{$one}"', '1'),
            array('"a {$one} b"', 'a 1 b'),
            array('"{$one + 2}"', '3'),
            array('"{$one * $two + 1}"', '3'),
            array('"{$one} and {$two}"', '1 and 2'),
            array('"$one and {$two}"', '1 and 2'),
            array('"{$one} and $two"', '1 and 2'),
            array('"a {$one} and {$two} b"', 'a 1 and 2 b'),
            array('"{$one+1} and {$two-1}"', '2 and 1'),
            array('"a {$one+1} and {$two-1} b"', 'a 2 and 1 b'),
            array('"a {$one|dots} and {$two|dots} b"', 'a 1... and 2... b'),
            array('"a {$one|dots} and $two b"', 'a 1... and 2 b'),
            array('"a $one and {$two|dots} b"', 'a 1 and 2... b'),
        );
    }

	public function providerVariables() {
		return array();
	}

	public static function providerObjects() {
		return array();
	}

	public static function providerArrays() {
		$scalars = array();
		$data = array(
			array('[]', array()),
			array('[[],[]]', array(array(), array())),
		);
		foreach(self::providerScalars() as $scalar) {
			$scalars[0][] = $scalar[0];
			$scalars[1][] = $scalar[1];

			$data[] = array(
				"[".$scalar[0]."]",
				array($scalar[1])
			);
			$data[] = array(
				"['some_key' =>".$scalar[0]."]",
				array('some_key' => $scalar[1])
			);
		}
		$data[] = array(
			"[".implode(", ", $scalars[0])."]",
			$scalars[1]
		);
		return $data;
	}

	public static function providerScalars() {
		return array_merge(
			self::providerNumbers(),
			self::providerStrings()
		);
	}

	public static function providerValues() {
		return array_merge(
			self::providerScalars(),
			self::providerArrays(),
			self::providerVariables(),
			self::providerObjects()
		);
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

	public function proxy() {
		return implode(", ", func_get_args());
	}
}
