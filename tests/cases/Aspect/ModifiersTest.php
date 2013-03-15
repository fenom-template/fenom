<?php
namespace Aspect;


class ModifiersTest extends TestCase {

    public static function providerTruncate() {
        $lorem = 'Lorem ipsum dolor sit amet';
        $uni   = 'Лорем ипсум долор сит амет';
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
     * @param bool $by_word
     */
    public function testTruncate($in, $out, $count, $delim = '...', $by_words = false, $middle = false) {
        $tpl = $this->aspect->compileCode('{$text|truncate:$count:$delim:$by_words:$middle}');
        $this->assertEquals($out, $tpl->fetch(array(
            "text" => $in,
            "count" => $count,
            "delim" => $delim,
            "by_words" => $by_words,
            "middle" => $middle
        )));
    }
}