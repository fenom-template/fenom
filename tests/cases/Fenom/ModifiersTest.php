<?php
namespace Fenom;


class ModifiersTest extends TestCase
{

    public static function providerTruncate()
    {
        $lorem = 'Lorem ipsum dolor sit amet'; // en
        $uni   = 'Лорем ипсум долор сит амет'; // ru
        return array(
            // ascii chars
            array($lorem, 'Lorem ip...', 8),
            array($lorem, 'Lorem ip!!!', 8, '!!!'),
            array($lorem, 'Lorem...', 8, '...', true),
            array($lorem, 'Lorem ip...sit amet', 8, '...', false, true),
            array($lorem, 'Lorem...amet', 8, '...', true, true),
            array($lorem, $lorem, 100, '...', true, true),
            array($lorem, $lorem, 100, '...', true, false),
            // unicode
            array($uni, 'Лорем ип...', 8),
            array($uni, 'Лорем ип!!!', 8, '!!!'),
            array($uni, 'Лорем...', 8, '...', true),
            array($uni, 'Лорем ип...сит амет', 8, '...', false, true),
            array($uni, 'Лорем...амет', 8, '...', true, true),
            array($uni, $uni, 100, '...', true, true),
            array($uni, $uni, 100, '...', true, false),
        );
    }


    /**
     * @dataProvider providerTruncate
     * @param $in
     * @param $out
     * @param $count
     * @param string $delim
     * @param bool $by_words
     * @param bool $middle
     */
    public function testTruncate($in, $out, $count, $delim = '...', $by_words = false, $middle = false)
    {
        $tpl = $this->fenom->compileCode('{$text|truncate:$count:$delim:$by_words:$middle}');
        $this->assertEquals(
            $out,
            $tpl->fetch(
                array(
                    "text"     => $in,
                    "count"    => $count,
                    "delim"    => $delim,
                    "by_words" => $by_words,
                    "middle"   => $middle
                )
            )
        );
    }

    public static function providerUpLow()
    {
        return array(
            array("up", "lorem", "LOREM"),
            array("up", "Lorem", "LOREM"),
            array("up", "loREM", "LOREM"),
            array("up", "223a", "223A"),
            array("low", "lorem", "lorem"),
            array("low", "Lorem", "lorem"),
            array("low", "loREM", "lorem"),
            array("low", "223A", "223a"),
        );
    }


    /**
     * @dataProvider providerUpLow
     * @param $modifier
     * @param $in
     * @param $out
     */
    public function testUpLow($modifier, $in, $out)
    {
        $tpl = $this->fenom->compileCode('{$text|' . $modifier . '}');
        $this->assertEquals(
            $out,
            $tpl->fetch(
                array(
                    "text" => $in,
                )
            )
        );
    }

    public static function providerLength()
    {
        return array(
            array("length", 6),
            array("длина", 5),
            array("length - длина", 14),
            array(array(1, 33, "c" => 4), 3),
            array(new \ArrayIterator(array(1, "c" => 4)), 2),
            array(true, 0),
            array(new \stdClass(), 0),
            array(5, 0)
        );
    }

    /**
     * @dataProvider providerLength
     * @param $in
     * @param $in
     * @param $out
     */
    public function testLength($in, $out)
    {
        $tpl = $this->fenom->compileCode('{$data|length}');
        $this->assertEquals(
            $out,
            $tpl->fetch(
                array(
                    "data" => $in,
                )
            )
        );
    }

    public static function providerIn()
    {
        return array(
            array('"b"|in:["a", "b", "c"]', true),
            array('"d"|in:["a", "b", "c"]', false),
            array('2|in:["a", "b", "c"]', true),
            array('3|in:["a", "b", "c"]', false),
            array('"b"|in:"abc"', true),
            array('"d"|in:"abc"', false),
        );
    }

    /**
     * @dataProvider providerIn
     */
    public function testIn($code, $valid)
    {
        $tpl = $this->fenom->compileCode('{if '.$code.'}valid{else}invalid{/if}');
        $this->assertEquals($valid ? "valid" : "invalid", $tpl->fetch(array()));
    }

    public function testJoin()
    {
        $tpl = $this->fenom->compileCode('{if "a;b;c" === ["a", "b", "c"]|join:";"}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testJoinString()
    {
        $tpl = $this->fenom->compileCode('{if "a;b;c" === "a;b;c"|join:","}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testJoinOther()
    {
        $tpl = $this->fenom->compileCode('{if "" === true|join:","}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testSplit()
    {
        $tpl = $this->fenom->compileCode('{if ["a", "b", "c"] === "a,b,c"|split:","}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testSplitArray()
    {
        $tpl = $this->fenom->compileCode('{if ["a", "b", "c"] === ["a", "b", "c"]|split:","}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testSplitOther()
    {
        $tpl = $this->fenom->compileCode('{if [] === true|split:","}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testESplit()
    {
        $tpl = $this->fenom->compileCode('{if ["a", "b", "c"] === "a:b:c"|esplit:"/:/"}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testESplitArray()
    {
        $tpl = $this->fenom->compileCode('{if ["a", "b", "c"] === ["a", "b", "c"]|esplit:"/:/"}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testESplitOther()
    {
        $tpl = $this->fenom->compileCode('{if [] === true|esplit:"/:/"}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testReplace()
    {
        $tpl = $this->fenom->compileCode('{if "a;c" === "a,b,c"|replace:",b,":";"}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testEReplace()
    {
        $tpl = $this->fenom->compileCode('{if "a;c" === "a,b,c"|ereplace:"/,b,?/miS":";"}equal{/if}');
        $this->assertEquals("equal", $tpl->fetch(array()));
    }

    public function testMatch()
    {
        $tpl = $this->fenom->compileCode('{if "a,b,c"|match:"a,[bd]*c":";"}match{/if}');
        $this->assertEquals("match", $tpl->fetch(array()));
    }

    public function testEMatch()
    {
        $tpl = $this->fenom->compileCode('{if "a,b,c"|ematch:"/^a,[bd].*?c$/":";"}match{/if}');
        $this->assertEquals("match", $tpl->fetch(array()));
    }

}