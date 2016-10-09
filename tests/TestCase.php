<?php
namespace Fenom;

use Fenom, Fenom\Provider as FS;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public $template_path = 'template';
    /**
     * @var Fenom
     */
    public $fenom;

    public $values;

    public static function getVars()
    {
        return array(
            "zero"  => 0,
            "one"   => 1,
            "two"   => 2,
            "three" => 3,
            "float" => 4.5,
            "bool"  => true,
            "obj"   => new \StdClass,
            "list"  => array(
                "a"   => 1,
                "one" => 1,
                "b"   => 2,
                "two" => 2
            ),
            "level1" => array(
                "level2" => array(
                    "one" => 1,
                    "two" => 2
                )
            ),
            "num"  => array(
                1 => "one",
                2 => "two",
                3 => "three",
                4 => "four"
            ),
            0       => "empty value",
            1       => "one value",
            2       => "two value",
            3       => "three value",
        );
    }

    public function getCompilePath()
    {
        return FENOM_RESOURCES . '/compile';
    }

    public function setUp()
    {
        if (!file_exists($this->getCompilePath())) {
            mkdir($this->getCompilePath(), 0777, true);
        } else {
            FS::clean($this->getCompilePath());
        }

        $this->fenom = Fenom::factory(FENOM_RESOURCES . '/' . $this->template_path, $this->getCompilePath());
        $this->fenom->addProvider('persist', new Provider(FENOM_RESOURCES . '/provider'));
        $this->fenom->addModifier('dots', __CLASS__ . '::dots');
        $this->fenom->addModifier('concat', __CLASS__ . '::concat');
        $this->fenom->addModifier('append', __CLASS__ . '::append');
        $this->fenom->addFunction('test_function', __CLASS__ . '::inlineFunction');
        $this->fenom->addBlockFunction('test_block_function', __CLASS__ . '::blockFunction');
        $this->values = $this->getVars();
    }

    public static function dots($value)
    {
        return $value . "...";
    }

    public static function concat()
    {
        return call_user_func_array('var_export', func_get_args());
    }

    public static function append()
    {
        return implode("", func_get_args());
    }


    public static function inlineFunction($params)
    {
        return isset($params["text"]) ? $params["text"] : "";
    }

    public static function blockFunction($params, $text)
    {
        return $text;
    }

    public static function setUpBeforeClass()
    {
        if (!file_exists(FENOM_RESOURCES . '/template')) {
            mkdir(FENOM_RESOURCES . '/template', 0777, true);
        } else {
            FS::clean(FENOM_RESOURCES . '/template/');
        }
    }

    public function tpl($name, $code)
    {
        $dir = dirname($name);
        if ($dir != "." && !is_dir(FENOM_RESOURCES . '/template/' . $dir)) {
            mkdir(FENOM_RESOURCES . '/template/' . $dir, 0777, true);
        }
        file_put_contents(FENOM_RESOURCES . '/template/' . $name, $code);
        return filemtime(FENOM_RESOURCES . '/template/' . $name);
    }

    public function tpls(array $list) {
        foreach($list as $name => $tpl) {
            $this->tpl($name, $tpl);
        }
    }

    /**
     * Compile and execute template
     *
     * @param string $code source of template
     * @param array $vars variables of template
     * @param string $result expected result.
     * @param int $options
     * @param bool $dump dump source and result code (for debug)
     * @return \Fenom\Template
     */
    public function exec($code, $vars, $result, $options = 0, $dump = false)
    {
        $this->fenom->setOptions($options);
        $tpl = $this->fenom->compileCode($code, "runtime.tpl");
        if ($dump) {
            echo "\n========= DUMP BEGIN ===========\n" . $code . "\n--- to ---\n" . $tpl->getBody(
                ) . "\n========= DUMP END =============\n";
        }
        $this->assertSame(Modifier::strip($result, true), Modifier::strip($tpl->fetch($vars), true), "Test $code");
        return $tpl;
    }

    public function execTpl($name, $code, $vars, $result, $dump = false)
    {
        $this->tpl($name, $code);
        $tpl = $this->fenom->getTemplate($name);
        if ($dump) {
            echo "\n========= DUMP BEGIN ===========\n" . $code . "\n--- to ---\n" . $tpl->getBody(
                ) . "\n========= DUMP END =============\n";
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
    public function execError($code, $exception, $message, $options = 0)
    {
        $opt = $this->fenom->getOptions();
        $this->fenom->setOptions($options);
        try {
            $this->fenom->compileCode($code, "inline.tpl");
        } catch (\Exception $e) {
            $this->assertSame($exception, get_class($e), "Exception $code");
            $this->assertStringStartsWith($message, $e->getMessage());
            $this->fenom->setOptions($opt);
            return;
        }
        $this->fenom->setOptions($opt);
        $this->fail("Code $code must be invalid");
    }

    public function assertRender($tpl, $result, array $vars = array(), $debug = false)
    {
        $template = $this->fenom->compileCode($tpl);
        if ($debug) {
            print_r("\nDEBUG $tpl:\n" . $template->getBody());
        }
        $this->assertSame($result, $template->fetch($vars + $this->values));
        return $template;
    }


    public static function providerNumbers()
    {
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

    public static function providerStrings()
    {
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

    public function providerVariables()
    {
        return array(
            array('$one', 1),
            array('$list.one', 1),
            array('$list[$$.DEVELOP]', 1),
        );
    }

    public static function providerObjects()
    {
        return array();
    }

    public static function providerArrays()
    {
        $scalars = array();
        $data    = array(
            array('[]', array()),
            array('[[],[]]', array(array(), array())),
        );
        foreach (self::providerScalars() as $scalar) {
            $scalars[0][] = $scalar[0];
            $scalars[1][] = $scalar[1];

            $data[] = array(
                "[" . $scalar[0] . "]",
                array($scalar[1])
            );
            $data[] = array(
                "['some_key' =>" . $scalar[0] . "]",
                array('some_key' => $scalar[1])
            );
        }
        $data[] = array(
            "[" . implode(", ", $scalars[0]) . "]",
            $scalars[1]
        );
        return $data;
    }

    public static function providerScalars()
    {
        return array_merge(
            self::providerNumbers(),
            self::providerStrings()
        );
    }

    public static function providerValues()
    {
        return array_merge(
            self::providerScalars(),
            self::providerArrays(),
            self::providerVariables(),
            self::providerObjects()
        );
    }
}

const HELPER_CONSTANT = 'helper.const';

class Helper
{

    const CONSTANT = "helper.class.const";

    public $word = 'helper';

    public function __construct($word)
    {
        $this->word = $word;
        $this->self = $this;
    }

    public static function method() {
        return new \ArrayObject(array("page" => new \ArrayObject(array("title" => "test page"), \ArrayObject::ARRAY_AS_PROPS)), \ArrayObject::ARRAY_AS_PROPS);
    }

    public function chunk()
    {
        return $this;
    }

    public function __toString()
    {
        return $this->word;
    }

    public function getArray() {
        return array(1,2,3);
    }
}

function helper_func($string, $pad = 10) {
    return str_pad($string, $pad, ".");
}

