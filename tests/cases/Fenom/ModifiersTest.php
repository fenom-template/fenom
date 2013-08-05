<?php
namespace Fenom;


class ModifiersTest extends TestCase
{

    public static function providerTruncate()
    {
        $lorem = 'Lorem ipsum dolor sit amet'; // en
        $uni = 'Лорем ипсум долор сит амет'; // ru
        return array(
            // ascii chars
            array($lorem, 'Lorem ip...', 8),
            array($lorem, 'Lorem ip!!!', 8, '!!!'),
            array($lorem, 'Lorem...', 8, '...', true),
            array($lorem, 'Lorem ip...sit amet', 8, '...', false, true),
            array($lorem, 'Lorem...amet', 8, '...', true, true),
            // unicode
            array($uni, 'Лорем ип...', 8),
            array($uni, 'Лорем ип!!!', 8, '!!!'),
            array($uni, 'Лорем...', 8, '...', true),
            array($uni, 'Лорем ип...сит амет', 8, '...', false, true),
            array($uni, 'Лорем...амет', 8, '...', true, true),
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
        $this->assertEquals($out, $tpl->fetch(array(
            "text" => $in,
            "count" => $count,
            "delim" => $delim,
            "by_words" => $by_words,
            "middle" => $middle
        )));
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
        $this->assertEquals($out, $tpl->fetch(array(
            "text" => $in,
        )));
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
        $this->assertEquals($out, $tpl->fetch(array(
            "data" => $in,
        )));
    }

}