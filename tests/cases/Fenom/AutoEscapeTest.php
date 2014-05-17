<?php

namespace Fenom;


class AutoEscapeTest extends TestCase
{


    public static function providerHTML()
    {
        $html = "<script>alert('injection');</script>";
        $escaped = htmlspecialchars($html, ENT_COMPAT, 'UTF-8');
        $vars = array(
            "html" => $html
        );
        return array(
            // variable
            array('{$html}, {$html}', "$html, $html", $vars, 0),
            array('{$html}, {$html}', "$escaped, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{raw $html}, {$html}', "$html, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{raw $html}, {$html}', "$html, $html", $vars, 0),
            array('{raw "{$html|up}"}, {$html}', strtoupper($html) . ", $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{autoescape true}{$html}{/autoescape}, {$html}', "$escaped, $html", $vars, 0),
            array('{autoescape false}{$html}{/autoescape}, {$html}', "$html, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{autoescape true}{$html}{/autoescape}, {$html}', "$escaped, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{autoescape false}{$html}{/autoescape}, {$html}', "$html, $html", $vars, 0),
            array('{autoescape true}{raw $html}{/autoescape}, {$html}', "$html, $html", $vars, 0),
            array('{autoescape false}{raw $html}{/autoescape}, {$html}', "$html, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{autoescape true}{raw $html}{/autoescape}, {$html}', "$html, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{autoescape false}{raw $html}{/autoescape}, {$html}', "$html, $html", $vars, 0),
            // inline function
            array('{test_function text=$html}, {$html}', "$html, $html", $vars, 0),
            array('{test_function text=$html}, {$html}', "$escaped, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array('{test_function:raw text=$html}, {$html}', "$html, $escaped", $vars, \Fenom::AUTO_ESCAPE),
            array(
                '{test_function:raw text="{$html|up}"}, {$html}',
                strtoupper($html) . ", $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_function text=$html}{/autoescape}, {test_function text=$html}',
                "$escaped, $html",
                $vars,
                0
            ),
            array(
                '{autoescape false}{test_function text=$html}{/autoescape}, {test_function text=$html}',
                "$html, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_function text=$html}{/autoescape}, {test_function text=$html}',
                "$escaped, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape false}{test_function text=$html}{/autoescape}, {test_function text=$html}',
                "$html, $html",
                $vars,
                0
            ),
            array(
                '{autoescape true}{test_function:raw text=$html}{/autoescape}, {test_function text=$html}',
                "$html, $html",
                $vars,
                0
            ),
            array(
                '{autoescape false}{test_function:raw text=$html}{/autoescape}, {test_function text=$html}',
                "$html, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_function:raw text=$html}{/autoescape}, {test_function text=$html}',
                "$html, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape false}{test_function:raw text=$html}{/autoescape}, {test_function text=$html}',
                "$html, $html",
                $vars,
                0
            ),
            // block function
            array('{test_block_function}{$html}{/test_block_function}', $html, $vars, 0),
            array('{test_block_function}{$html}{/test_block_function}', $escaped, $vars, \Fenom::AUTO_ESCAPE),
            array('{test_block_function:raw}{$html}{/test_block_function}', $html, $vars, \Fenom::AUTO_ESCAPE),
            array(
                '{test_block_function:raw}{"{$html|up}"}{/test_block_function}',
                strtoupper($html),
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_block_function}{$html}{/test_block_function}{/autoescape}, {test_block_function}{$html}{/test_block_function}',
                "$escaped, $html",
                $vars,
                0
            ),
            array(
                '{autoescape false}{test_block_function}{$html}{/test_block_function}{/autoescape}, {test_block_function}{$html}{/test_block_function}',
                "$html, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_block_function}{$html}{/test_block_function}{/autoescape}, {test_block_function}{$html}{/test_block_function}',
                "$escaped, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape false}{test_block_function}{$html}{/test_block_function}{/autoescape}, {test_block_function}{$html}{/test_block_function}',
                "$html, $html",
                $vars,
                0
            ),
            array(
                '{autoescape true}{test_block_function:raw}{$html}{/test_block_function}{/autoescape}, {test_block_function}{$html}{/test_block_function}',
                "$html, $html",
                $vars,
                0
            ),
            array(
                '{autoescape false}{test_block_function:raw}{$html}{/test_block_function}{/autoescape}, {test_block_function}{$html}{/test_block_function}',
                "$html, $escaped",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_block_function}{$html}{/test_block_function}{/autoescape}, {test_block_function:raw}{$html}{/test_block_function}',
                "$escaped, $html",
                $vars,
                \Fenom::AUTO_ESCAPE
            ),
            array(
                '{autoescape true}{test_block_function:raw}{$html}{/test_block_function}{/autoescape}, {test_block_function:raw}{$html}{/test_block_function}',
                "$html, $html",
                $vars,
                0
            ),
        );
    }

    /**
     * @dataProvider providerHTML
     */
    public function testEscaping($tpl, $result, $vars, $options)
    {
        $this->values = $vars;
        $this->fenom->setOptions($options);
        $this->assertRender($tpl, $result);
    }
}