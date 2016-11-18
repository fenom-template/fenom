<?php
namespace Fenom;

use Fenom\Template,
    Fenom,
    Fenom\Render;

/**
 * Test template parsing
 *
 * @package Fenom
 */
class TemplateTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->tpl('welcome.tpl', '<b>Welcome, {$username} ({$email})</b>');

    }

    public static function providerVars()
    {
        $a         = array("a" => "World");
        $obj       = new \stdClass;
        $obj->name = "Object";
        $obj->list = $a;
        $obj->c    = "c";
//        $world = new
        $b = array(
            "b"     => array(
                "c"      => "Username",
                "c_char" => "c",
                "mcp"    => "Master",
                'm{$c}p' => "Unknown",
                'obj'    => $obj
            ),
            "c"     => "c",
            "world" => new Helper('world')
        );
        $c = array_replace_recursive($b, array("b" => array(3 => $b["b"], 4 => "Mister")));
        return array(
            array('hello, {$a}!', $a, 'hello, World!'),
            array('hello, {$b.c}!', $b, 'hello, Username!'),
            array('hello, {$b."c"}!', $b, 'hello, Username!'),
            array('hello, {$b.\'c\'}!', $b, 'hello, Username!'),
            array('hello, {$b[c]}!', $b, 'hello, Username!'),
            array('hello, {$b["c"]}!', $b, 'hello, Username!'),
            array('hello, {$b[\'c\']}!', $b, 'hello, Username!'),
            array('hello, {$b[ $b.c_char ]}!', $b, 'hello, Username!'),
            array('hello, {$b[ "$c" ]}!', $b, 'hello, Username!'),
            array('hello, {$b.$c}!', $b, 'hello, Username!'),
            array('hello, {$b."$c"}!', $b, 'hello, Username!'),
            array('hello, {$b."{$c}"}!', $b, 'hello, Username!'),
            array('hello, {$b[ "{$c}" ]}!', $b, 'hello, Username!'),
            array('hello, {$b[ ("{$c}") ]}!', $b, 'hello, Username!'),
            array('hello, {$b[ "mcp" ]}!', $b, 'hello, Master!'),
            array('hello, {$b[ "m{$c}p" ]}!', $b, 'hello, Master!'),
            array('hello, {$b."m{$c}p"}!', $b, 'hello, Master!'),
            array(
                'hello, {$b[ "m{$b.c_char}p" ]}!',
                $b,
                'hello, Master!'
            ),
            array('hello, {$b[ \'m{$c}p\' ]}!', $b, 'hello, Unknown!'),
            array('hello, {$b.4}!', $c, 'hello, Mister!'),
            array('hello, {$b[4]}!', $c, 'hello, Mister!'),
            array('hello, {$b.3.c}!', $c, 'hello, Username!'),
            array('hello, {$b.3.$c}!', $c, 'hello, Username!'),
            array('hello, {$b.3[$b.c_char]}!', $c, 'hello, Username!'),
            array('hello, {$b[3].c}!', $c, 'hello, Username!'),
            array('hello, {$b[2+1].c}!', $c, 'hello, Username!'),
            array('hello, {$b[(2+1)].c}!', $c, 'hello, Username!'),
            array('hello, {$b[9/3].c}!', $c, 'hello, Username!'),
            array('hello, {$b[3].$c}!', $c, 'hello, Username!'),
            array('hello, {$b[(3)].$c}!', $c, 'hello, Username!'),
            array('hello, {$b[3][$b.c_char]}!', $c, 'hello, Username!'),
            array(
                'hello, {$b[ "m{$b.c_char}p" ]} and {$b.3[$b.c_char]}!',
                $c,
                'hello, Master and Username!'
            ),
            array('hello, {$b.obj->name}!', $c, 'hello, Object!'),
            array('hello, {$b.obj->list.a}!', $c, 'hello, World!'),
            array('hello, {$b[obj]->name}!', $c, 'hello, Object!'),
            array('hello, {$b["obj"]->name}!', $c, 'hello, Object!'),
            array('hello, {$b."obj"->name}!', $c, 'hello, Object!'),
            array(
                'hello, {$b.obj->name|upper}!',
                $c,
                'hello, OBJECT!'
            ),
            array(
                'hello, {$b.obj->list.a|upper}!',
                $c,
                'hello, WORLD!'
            ),
            array('hello, {$b[ $b.obj->c ]}!', $b, 'hello, Username!'),
            array('hello, {$b[ ( $b.obj->c ) ]}!', $b, 'hello, Username!'),
            array(
                'hello, {$b[ "{$b.obj->c}" ]}!',
                $b,
                'hello, Username!'
            ),
            array('hello, {"World"}!', $a, 'hello, World!'),
            array('hello, {"W{$a}d"}!', $a, 'hello, WWorldd!'),
            array('hello, {$world->chunk(1)->self->chunk("new")}!', $b, 'hello, world!'),
            array('hello, {!$world->chunk(1)->self->chunk("new")}!', $b, 'hello, !'),
            array(':: {Fenom\Helper::method()->page->title} ::', $b, ':: test page ::'),
        );
    }

    public static function providerDo() {
        $vars = array(
            "c" => 4,
            "world" => new Helper('world')
        );
        return array(
            array('{do "nope"}', $vars, ""),
            array('{do $c++} -> {$c}', $vars, "-> 5"),
            array('{do $world->chunk(1)}', $vars, ""),
        );
    }


    public static function providerVarsInvalid()
    {
        return array(
            array('hello, {$a.}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('hello, {$b[}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('hello, {$b.}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('hello, {$b[c}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('hello, {$b.c]}!', 'Fenom\Error\CompileException', "Unexpected token ']'"),
            array('hello, {$b[ ]}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('hello, {$b[9/].c}!', 'Fenom\Error\CompileException', "Unexpected token ']'"),
            array('hello, {$b[3]$c}!', 'Fenom\Error\CompileException', "Unexpected token '\$c'"),
            array('hello, {$b[3]c}!', 'Fenom\Error\CompileException', "Unexpected token 'c'"),
            array(
                'hello, {$b.obj->valid()}!',
                'Fenom\Error\SecurityException',
                "Forbidden to call methods",
                Fenom::DENY_METHODS
            ),
        );
    }

    public static function providerModifiers()
    {
        $b = array(
            "a"           => "World",
            "b"           => array(
                "c" => "Username",
            ),
            "c"           => "c",
            "lorem"       => "Lorem ipsum dolor sit amet",
            "next"        => " next -->",
            "rescue"      => "Chip & Dale",
            "rescue_html" => "Chip &amp; Dale",
            "rescue_url"  => "Chip+%26+Dale",
            "date"        => "26-07-2012",
            "time"        => 1343323616,
            "tags"        => "my name is <b>Legion</b>"
        );
        return array(
            array('hello, {$a|upper}!', $b, 'hello, WORLD!'),
            array('hello, {$b.c|upper}!', $b, 'hello, USERNAME!'),
            array('hello, {$b."c"|upper}!', $b, 'hello, USERNAME!'),
            array('hello, {$b["C"|lower]|upper}!', $b, 'hello, USERNAME!'),
            array('Mod: {$rescue|escape}!', $b, 'Mod: Chip &amp; Dale!'),
            array('Mod: {$rescue|escape:"html"}!', $b, 'Mod: Chip &amp; Dale!'),
            array('Mod: {$rescue|escape:"url"}!', $b, 'Mod: Chip+%26+Dale!'),
            array('Mod: {$rescue|escape:"unknown"}!', $b, 'Mod: Chip & Dale!'),
            array('Mod: {$rescue|escape:"js"}!', $b, 'Mod: "Chip & Dale"!'),
            array('Mod: {$rescue_html|unescape}!', $b, 'Mod: Chip & Dale!'),
            array('Mod: {$rescue_html|unescape:"html"}!', $b, 'Mod: Chip & Dale!'),
            array('Mod: {$rescue_url|unescape:"url"}!', $b, 'Mod: Chip & Dale!'),
            array('Mod: {$rescue|unescape:"unknown"}!', $b, 'Mod: Chip & Dale!'),
            array('Mod: {$time|date_format:"%Y %m %d"}!', $b, 'Mod: 2012 07 26!'),
            array('Mod: {$date|date_format:"%Y %m %d"}!', $b, 'Mod: 2012 07 26!'),
            array('Mod: {$time|date:"Y m d"}!', $b, 'Mod: 2012 07 26!'),
            array('Mod: {$date|date:"Y m d"}!', $b, 'Mod: 2012 07 26!'),
            array('Mod: {$tags|strip_tags}!', $b, 'Mod: my name is Legion!'),
            array('Mod: {$b.c|json_encode}!', $b, 'Mod: "Username"!'),
            array('Mod: {($time/1024/1024)|round:2}!', $b, 'Mod: 1281.09!'),
            array('Mod: {time()|date:"Y m d"}!', $b, 'Mod: ' . date("Y m d") . '!'),
        );
    }

    public static function providerModifiersInvalid()
    {
        return array(
            array('Mod: {$lorem|}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array(
                'Mod: {$lorem|str_rot13}!',
                'Fenom\Error\CompileException',
                "Modifier str_rot13 not found",
                Fenom::DENY_NATIVE_FUNCS
            ),
            array('Mod: {$lorem|my_encode}!', 'Fenom\Error\CompileException', "Modifier my_encode not found"),
            array('Mod: {$lorem|truncate:}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('Mod: {$lorem|truncate:abs}!', 'Fenom\Error\CompileException', "Unexpected token 'abs'"),
            array('Mod: {$lorem|truncate:80|}!', 'Fenom\Error\CompileException', "Unexpected end of expression"),
        );
    }

    public static function providerExpressions()
    {
        $b = array(
            "x" => $x = 9,
            "y" => 27,
            "z" => 8.9,
            "k" => array("i" => "")
        );
        return array(
            array('Exp: {' . $x . '+$y} result', $b, 'Exp: 36 result'),
            array('Exp: {$y/' . $x . '} result', $b, 'Exp: 3 result'),
            array('Exp: {$y-' . $x . '} result', $b, 'Exp: 18 result'),
            array('Exp: {' . $x . '*$y} result', $b, 'Exp: 243 result'),
            array('Exp: {$y^' . $x . '} result', $b, 'Exp: 18 result'),
            array('Exp: {$x+$y} result', $b, 'Exp: 36 result'),
            array('Exp: {$y/$x} result', $b, 'Exp: 3 result'),
            array('Exp: {$y-$x} result', $b, 'Exp: 18 result'),
            array('Exp: {$y*$x} result', $b, 'Exp: 243 result'),
            array('Exp: {$y^$x} result', $b, 'Exp: 18 result'),
            array('Exp: {-($x)} result', $b, 'Exp: -9 result'),
            array('Exp: {!$x} result', $b, 'Exp: result'),
            array('Exp: {!($x)} result', $b, 'Exp: result'),
            array('Exp: {!5} result', $b, 'Exp: result'),
            array('Exp: {-1} result', $b, 'Exp: -1 result'),
            array('Exp: {$z = 5} {$z} result', $b, 'Exp: 5 5 result'),
            array('Exp: {$k.i = "str"} {$k.i} result', $b, 'Exp: str str result'),
            array(
                'Exp: {($y*$x - (($x+$y) + $y/$x) ^ $y)/4} result',
                $b,
                'Exp: 53.75 result'
            ),
            array('Exp: {$x+max($x, $y)} result', $b, 'Exp: 36 result'),
            array('Exp: {max(1,2)} result', $b, 'Exp: 2 result'),
            array('Exp: {round(sin(pi()), 8)} result', $b, 'Exp: 0 result'),
            array(
                'Exp: {max($x, $y) + round(sin(pi()), 8) - min($x, $y) +3} result',
                $b,
                'Exp: 21 result'
            ),
        );
    }

    public static function providerExpressionsInvalid()
    {
        return array(
            array('If: {-"hi"} end', 'Fenom\Error\CompileException', "Unexpected token '-'"),
            array('If: {-[1,2]} end', 'Fenom\Error\CompileException', "Unexpected token '-'"),
            array('If: {($a++)++} end', 'Fenom\Error\CompileException', "Unexpected token '++'"),
            array('If: {$a + * $c} end', 'Fenom\Error\CompileException', "Unexpected token '*'"),
            array('If: {$a + } end', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array('If: {$a + =} end', 'Fenom\Error\CompileException', "Unexpected token '='"),
            array('If: {$a + 1 =} end', 'Fenom\Error\CompileException', "Unexpected token '='"),
            array('If: {$a + 1 = 6} end', 'Fenom\Error\CompileException', "Unexpected token '='"),
            array('If: {/$a} end', 'Fenom\Error\CompileException', "Unexpected token '\$a'"),
            array('If: {$a == 5 > 4} end', 'Fenom\Error\CompileException', "Unexpected token '>'"),
            array('If: {$a != 5 <= 4} end', 'Fenom\Error\CompileException', "Unexpected token '<='"),
            array('If: {$a != 5 => 4} end', 'Fenom\Error\CompileException', "Unexpected token '=>'"),
            array('If: {$a + (*6)} end', 'Fenom\Error\CompileException', "Unexpected token '*'"),
            array('If: {$a + ( 6} end', 'Fenom\Error\CompileException', "Unexpected end of expression, expect ')'"),
            array('If: {$a end', 'Fenom\Error\CompileException', "Unclosed tag in line"),
            array('If: {!!$a}', 'Fenom\Error\CompileException', "Unexpected token '!'"),
        );
    }


    public static function providerIncludeInvalid()
    {
        return array(
            array('Include {include} template', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array(
                'Include {include another="welcome.tpl"} template',
                'Fenom\Error\CompileException',
                "Unexpected token 'another'"
            ),
            array(
                'Include {include "../../TestCase.php"} template',
                'Fenom\Error\SecurityException',
                "Template ../../TestCase.php not found"
            ),
        );
    }

    public static function providerInsert()
    {
        $a      = array(
            "name"        => "welcome",
            "tpl"         => "welcome.tpl",
            "fragment"    => "come",
            "pr_fragment" => "Come",
            "pr_name"     => "Welcome",
            "username"    => "Master",
            "email"       => "dev@null.net"
        );
        $result = 'Include <b>Welcome, Master (dev@null.net)</b> template';
        return array(
            array('Include {insert "welcome.tpl"} template', $a, $result),
            array("Include {insert 'welcome.tpl'} template", $a, $result),
        );
    }

    public static function providerInsertInvalid()
    {
        return array(
            array('Include {insert} template', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array(
                'Include {insert another="welcome.tpl"} template',
                'Fenom\Error\CompileException',
                "Unexpected token 'another'"
            ),
            array(
                'Include {insert $tpl} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "$tpl"} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "{$tpl}"} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "$name.tpl"} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "{$name}.tpl"} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "{$pr_name|lower}.tpl"} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "wel{$fragment}.tpl"} template',
                'Fenom\Error\CompileException',
                "Tag {insert} accept only static template name"
            ),
            array(
                'Include {insert "welcome.tpl" email="flame@dev.null"} template',
                'Fenom\Error\CompileException',
                "Unexpected token 'email'"
            ),
        );
    }

    public static function providerIf()
    {
        $a = array(
            "val1" => 1,
            "val0" => 0,
            "x"    => 9,
            "y"    => 27
        );
        return array(
            array('if: {if 1} block1 {/if} end', $a, 'if: block1 end'),
            array('if: {if 1} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if 0} block1 {/if} end', $a, 'if: end'),
            array('if: {if $val0} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array('if: {if $val1} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if $val1 || $val0} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if $val1 && $val0} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array(
                'if: {if $x-9} block1 {elseif $x} block2 {else} block3 {/if} end',
                $a,
                'if: block2 end'
            ),
            array(
                'if: {if round(sin(pi()), 8)} block1 {elseif $x} block2 {else} block3 {/if} end',
                $a,
                'if: block2 end'
            ),
            array(
                'if: {if round(sin(pi()), 8)} block1 {elseif $val0} block2 {else} block3 {/if} end',
                $a,
                'if: block3 end'
            ),
            array('if: {if empty($val0)} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if $val0?} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array('if: {if $val1?} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if $val0!} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if $val1!} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if $val0.x.y.z?} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array('if: {if $val0.x.y.z!} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array('if: {if true} block1 {else} block2 {/if} end', $a, 'if: block1 end'),
            array('if: {if false} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array('if: {if null} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array('if: {if max(2, 4) > 1 && max(2, 3) < 1} block1 {else} block2 {/if} end', $a, 'if: block2 end'),
            array(
                'if: {if ($val1 || $val0) && $x} block1 {else} block2 {/if} end',
                $a,
                'if: block1 end'
            ),
            array('if: {if $unexist} block1 {else} block2 {/if} end', $a, 'if: block2 end', Fenom::FORCE_VERIFY),
            array('if: {if !$unexist} block1 {else} block2 {/if} end', $a, 'if: block1 end', Fenom::FORCE_VERIFY),
        );
    }

    public static function providerIfInvalid()
    {
        return array(
            array('If: {if} block1 {/if} end', 'Fenom\Error\CompileException', "Unexpected end of expression"),
            array(
                'If: {if 1} block1 {elseif} block2 {/if} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array(
                'If: {if 1} block1 {else} block2 {elseif 0} block3 {/if} end',
                'Fenom\Error\CompileException',
                "Incorrect use of the tag {elseif}"
            ),
            array(
                'If: {if 1} block1 {else} block2 {/if} block3 {elseif 0} end',
                'Fenom\Error\CompileException',
                "Unexpected tag 'elseif' (this tag can be used with 'if')"
            ),
        );
    }

    public static function providerCreateVar()
    {
        $a = array(
            "x" => 9,
            "y" => 27,
            "z" => 99
        );
        return array(
            array('Create: {set $v = $x+$y} Result: {$v} end', $a, 'Create: Result: 36 end'),
            array('Create: {add $x = $x+$y} Result: {$x} end', $a, 'Create: Result: 9 end'),
            array('Create: {add $v = 3} {add $v = 9} Result: {$v} end', $a, 'Create: Result: 3 end'),
            array(
                'Create: {set $v =
                            $x
                            +
                            $y} Result: {$v} end',
                $a,
                'Create: Result: 36 end'
            ),
            array('Create: {set $v = $z++} Result: {$v}, {$z} end', $a, 'Create: Result: 99, 100 end'),
            array('Create: {set $v = $z++ + 1} Result: {$v}, {$z} end', $a, 'Create: Result: 100, 100 end'),
            array('Create: {set $v = --$z} Result: {$v}, {$z} end', $a, 'Create: Result: 98, 98 end'),
            array('Create: {set $v = $y/$x} Result: {$v} end', $a, 'Create: Result: 3 end'),
            array('Create: {set $v = $y-$x} Result: {$v} end', $a, 'Create: Result: 18 end'),
            array('Create: {set $v = $y*$x-2} Result: {$v} end', $a, 'Create: Result: 241 end'),
            array('Create: {set $v = ($y^$x)+7} Result: {$v} end', $a, 'Create: Result: 25 end'),
            array('Create: {set $v = [1,2,3]} Result: {$v.1} end', $a, 'Create: Result: 2 end'),
            array(
                'Create: {set $v = []} Result: {if $v} have items {else} empty {/if} end',
                $a,
                'Create: Result: empty end'
            ),
            array(
                'Create: {set $v = ["one"|upper => 1, 4 => $x, "three" => 3]} Result: {$v.three}, {$v.4}, {$v.ONE} end',
                $a,
                'Create: Result: 3, 9, 1 end'
            ),
            array(
                'Create: {set $v = ["key1" => $y*$x-2, "key2" => ["z" => $z]]} Result: {$v.key1}, {$v.key2.z} end',
                $a,
                'Create: Result: 241, 99 end'
            ),
            array(
                'Create: {set $v = count([1,2,3])+7} Result: {$v} end',
                $a,
                'Create: Result: 10 end'
            ),
        );
    }

    public static function providerCreateVarInvalid()
    {
        return array(
            array('Create: {set $v} Result: {$v} end', 'Fenom\Error\CompileException', "Unclosed tag: {set} opened"),
            array('Create: {add $v} Result: {$v} end', 'Fenom\Error\CompileException', "Unclosed tag: {add} opened"),
            array(
                'Create: {set $v = } Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array('Create: {set $v = 1++} Result: {$v} end', 'Fenom\Error\CompileException', "Unexpected token '++'"),
            array('Create: {set $v = c} Result: {$v} end', 'Fenom\Error\CompileException', "Unexpected token 'c'"),
            array(
                'Create: {set $v = ($a)++} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected token '++'"
            ),
            array(
                'Create: {set $v = --$a++} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected token '++'"
            ),
            array(
                'Create: {set $v = $a|upper++} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected token '++'"
            ),
            array(
                'Create: {set $v = max($a,2)++} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected token '++'"
            ),
            array(
                'Create: {set $v = max($a,2)} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Function max not found",
                Fenom::DENY_NATIVE_FUNCS
            ),
            array(
                'Create: {set $v = 4*} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array('Create: {set $v = ""$a} Result: {$v} end', 'Fenom\Error\CompileException', "Unexpected token '\$a'"),
            array(
                'Create: {set $v = [1,2} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array(
                'Create: {set $v = empty(2)} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected token 2, isset() and empty() accept only variables"
            ),
            array(
                'Create: {set $v = isset(2)} Result: {$v} end',
                'Fenom\Error\CompileException',
                "Unexpected token 2, isset() and empty() accept only variables"
            ),

        );
    }

    public static function providerArrays()
    {
        return array(
            array('{set $arr[] = 4}', array(4)),
            array('{set $arr = []}', array()),
            array('{set $arr = [1]}', array(1)),
            array('{set $arr = [1,]}', array(1)),
            array('{set $arr = [1, 2, 3, 5]}', array(1, 2, 3, 5)),
            array('{set $arr = [1, true, false, null, -1, 1.1, -2.2, 5, "str"]}', array(1, true, false, null, -1, 1.1, -2.2, 5, "str")),
            array('{set $arr = [5 => 1, "two" => 2, 3]}', array(5 => 1, "two" => 2, 3)),
            array('{set $arr = [1 + 1, 2 * 2, 3 / 3 + 7,]}', array(1 + 1, 2 * 2, 3 / 3 + 7)),
            array('{set $arr = [$zero, $two => $one, $num.3 => $.const.PHP_VERSION]}', array(0, 2 => 1, "three" => PHP_VERSION)),
            array('{set $arr = [5 - 1 => 1, "two"|up => "two"|low, 3 => count([1,2])]}', array(4 => 1, "TWO" => "two", 3 => 2)),

            array('{set $arr = [[1]]}', array(array(1))),
            array('{set $arr = [[],[]]}', array(array(),array())),
            array('{set $arr = [1, [2, 3], 5]}', array(1, array(2, 3), 5)),
            array('{set $arr = [1, [true, false, null, -1, 1.1, -2.2, 5], "str"]}', array(1, array(true, false, null, -1, 1.1, -2.2, 5), "str")),
            array('{set $arr = [5 => [1, "two" => 2], 3]}', array(5 => array(1, "two" => 2), 3)),
            array('{set $arr = [1 + 1, [2 * 2, 3 / 3 + 7,],]}', array(1 + 1, array(2 * 2, 3 / 3 + 7))),
            array('{set $arr = [$zero, [$two => $one, $num.3 => $.const.PHP_VERSION]]}', array(0, array(2 => 1, "three" => PHP_VERSION))),
            array('{set $arr = [5 - 1 => 1, ["two"|up => ("two"|low ~ "..."), 3 => count([1,2])]]}', array(4 => 1, array("TWO" => "two...", 3 => 2))),
        );
    }

    public static function providerUnset() {
        return array(
            array('{set $a = 5} {unset $a} {if $a is not set}not set{/if}', 'not set'),
            array('{set $a = ["b" => 5, "c" => 6]} {unset $a.b} {if $a.b is not set}not set{/if} but c is {$a.c}', 'not set but c is 6'),
            array('{set $a = ["b" => 5, "c" => 6]} {unset $a.b $a.c} {if $a.b is not set}not set{/if} {if $a.c is not set}not set{/if}', 'not set not set'),
        );
    }

    public static function providerTernary()
    {
        $a = array(
            "a"        => 1,
            "em"       => "empty",
            "empty"    => array(
                "array"  => array(),
                "int"    => 0,
                "string" => "",
                "double" => 0.0,
                "bool"   => false,
            ),
            "nonempty" => array(
                "array"  => array(1, 2),
                "int"    => 2,
                "string" => "abc",
                "double" => 0.2,
                "bool"   => true,
            )
        );
        return array(
            // ?
            array('{if $a?} right {/if}', $a),
            array('{if 1?} right {/if}', $a),
            array('{if 0?} no way {else} right {/if}', $a),
            array('{if $unexists?} no way {else} right {/if}', $a),
            array('{if $empty.array?} no way {else} right {/if}', $a),
            array('{if $empty.int?} no way {else} right {/if}', $a),
            array('{if $empty.string?} no way {else} right {/if}', $a),
            array('{if $empty.double?} no way {else} right {/if}', $a),
            array('{if $empty.bool?} no way {else} right {/if}', $a),
            array('{if $empty.unexist?} no way {else} right {/if}', $a),
            array('{if $nonempty.array?} right {/if}', $a),
            array('{if $nonempty.int?} right {/if}', $a),
            array('{if $nonempty.string?} right {/if}', $a),
            array('{if $nonempty.double?} right {/if}', $a),
            array('{if $nonempty.bool?} right {/if}', $a),
            // ?: ...
            array('{$a?:"empty"}', $a, "1"),
            array('{$unexists?:"empty"}', $a, "empty"),
            array('{$empty.array?:"empty"}', $a, "empty"),
            array('{$empty.int?:"empty"}', $a, "empty"),
            array('{$empty.string?:"empty"}', $a, "empty"),
            array('{$empty.double?:"empty"}', $a, "empty"),
            array('{$empty.bool?:"empty"}', $a, "empty"),
            array('{$empty.unexist?:"empty"}', $a, "empty"),
            array('{0?:"empty"}', $a, "empty"),
            // ? ... : ....
            array('{$unexists ? "no way" : "right"}', $a),
            array('{0 ? "no way" : "right"}', $a),
            array('{$a ? "right" : "no way"}', $a),
            array('{1 ? "right" : "no way"}', $a),
            array('{$a == 1 ? "right" : "no way"}', $a),
            array('{$a > 0 ? "right" : "no way"}', $a),
            // !
            array('{if $a!} right {/if}', $a),
            array('{if 1!} right {/if}', $a),
            array('{if 0!} right {/if}', $a),
            array('{if null!} no way {else} right  {/if}', $a),
            array('{if $unexists!} no way {else} right {/if}', $a),
            array('{if $empty.array!} right {/if}', $a),
            array('{if $empty.int!} right {/if}', $a),
            array('{if $empty.string!} right {/if}', $a),
            array('{if $empty.double!} right {/if}', $a),
            array('{if $empty.bool!} right {/if}', $a),
            array('{if $empty.unexist!} no way {else} right {/if}', $a),
            array('{if $nonempty.array!} right {/if}', $a),
            array('{if $nonempty.int!} right {/if}', $a),
            array('{if $nonempty.string!} right {/if}', $a),
            array('{if $nonempty.double!} right {/if}', $a),
            array('{if $nonempty.bool!} right {/if}', $a),
            // ! ... : ...
            array('{$unexists ! "no way" : "right"}', $a),
            array('{$a ! "right" : "no way"}', $a),
            array('{1 ! "right" : "no way"}', $a),
            // !: ...
            array('{$unexists !: "right"}', $a),
            array('{$a !: "right"}', $a, '1'),
            array('{1 !: "right"}', $a, '1'),
        );
    }

    public static function providerForeachInvalid()
    {
        return array(
            array(
                'Foreach: {foreach} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
//            array(
//                'Foreach: {foreach $list} {$e}, {/foreach} end',
//                'Fenom\Error\CompileException',
//                "Unexpected end of expression"
//            ),
//            array(
//                'Foreach: {foreach $list+1 as $e} {$e}, {/foreach} end',
//                'Fenom\Error\CompileException',
//                "Unexpected token '+'"
//            ),
            array(
                'Foreach: {foreach array_random() as $e} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Function array_random not found"
            ),
            array(
                'Foreach: {foreach $list as $e+1} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '+'"
            ),
            array(
                'Foreach: {foreach $list as $k+1 => $e} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '+'"
            ),
            array(
                'Foreach: {foreach $list as max($i,1) => $e} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token 'max'"
            ),
            array(
                'Foreach: {foreach $list as max($e,1)} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token 'max'"
            ),
            array(
                'Foreach: {foreach $list => $e} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '=>'"
            ),
            array(
                'Foreach: {foreach $list $k => $e} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '\$k'"
            ),
            array(
                'Foreach: {foreach $list as $k =>} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array(
                'Foreach: {foreach last=$l $list as $e } {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token 'last' in expression"
            ),
            array(
                'Foreach: {foreach $list as $e unknown=1} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unknown foreach property 'unknown'"
            ),
            array(
                'Foreach: {foreach $list as $e index=$i+1} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '+'"
            ),
            array(
                'Foreach: {foreach $list as $e first=$f+1} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '+'"
            ),
            array(
                'Foreach: {foreach $list as $e last=$l+1} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token '+'"
            ),
            array(
                'Foreach: {foreach $list as $e index=max($i,1)} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token 'max'"
            ),
            array(
                'Foreach: {foreach $list as $e first=max($i,1)} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token 'max'"
            ),
            array(
                'Foreach: {foreach $list as $e last=max($i,1)} {$e}, {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected token 'max'"
            ),
            array(
                'Foreach: {foreach $list as $e} {$e}, {foreachelse} {break} {/foreach} end',
                'Fenom\Error\CompileException',
                "Improper usage of the tag {break}"
            ),
            array(
                'Foreach: {foreach $list as $e} {$e}, {foreachelse} {continue} {/foreach} end',
                'Fenom\Error\CompileException',
                "Improper usage of the tag {continue}"
            ),
        );
    }

    public static function providerIgnores()
    {
        $a = array("a" => "lit. A");
        return array(
            array('{if 0}none{/if} literal: {$a} end', $a, 'literal: lit. A end'),
            array('{if 0}none{/if} literal:{ignore} {$a} {/ignore} end', $a, 'literal: {$a} end'),
            array('{if 0}none{/if} literal: { $a} end', $a, 'literal: { $a} end'),
            array('{if 0}none{/if} literal: {  $a}{$a}{  $a} end', $a, 'literal: {  $a}lit. A{  $a} end'),
            array(
                '{if 0}none{/if} literal: {
                            $a} end',
                $a,
                'literal: { $a} end'
            ),
            array('{if 0}none{/if}literal: function () { return 1; } end', $a, 'literal: function () { return 1; } end'),
            array('{if:ignore 1}literal: {$a} end{/if}', $a, 'literal: {$a} end'),
        );
    }

    public static function providerSwitchInvalid()
    {
        return array(
            array(
                'Switch: {switch}{case 1} one {/switch} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array(
                'Switch: {switch 1}{case} one{/switch} end',
                'Fenom\Error\CompileException',
                "Unexpected end of expression"
            ),
            array(
                'Switch: {switch 1}{case $var} one {/switch} end',
                'Fenom\Error\CompileException',
                "Unexpected token '\$var' in expression"
            ),
        );
    }

    public static function providerWhile()
    {
        $a = array("a" => 3);
        return array(
            array('While: {while false} block {/while} end', $a, 'While: end'),
            array('While: {while --$a} {$a}, {/while} end', $a, 'While: 2, 1, end'),
            array('While: {while --$a} {$a},{break} break {/while} end', $a, 'While: 2, end'),
            array('While: {while --$a} {$a},{continue} continue {/while} end', $a, 'While: 2, 1, end'),
        );
    }

    public static function providerWhileInvalid()
    {
        return array(
            array('While: {while} block {/while} end', 'Fenom\Error\CompileException', "Unexpected end of expression"),
        );
    }

    public static function providerLayersInvalid()
    {
        return array(
            array(
                'Layers: {foreach $list as $e} block1 {if 1} {foreachelse} {/if} {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected tag 'foreachelse' (this tag can be used with 'foreach')"
            ),
            array(
                'Layers: {foreach $list as $e} block1 {if 1}  {/foreach} {/if} end',
                'Fenom\Error\CompileException',
                "Unexpected closing of the tag 'foreach'"
            ),
            array('Layers: {blah} end', 'Fenom\Error\CompileException', "Unexpected tag 'blah'"),
            array(
                'Layers: {foreach 4..6 as $a} block1 {if 1} {foreachelse} {/if} {/foreach} end',
                'Fenom\Error\CompileException',
                "Unexpected tag 'foreachelse' (this tag can be used with 'foreach')"
            ),
            array(
                'Layers: {foreach 4..6 as $a} block1 {if 1}  {/foreach} {/if} end',
                'Fenom\Error\CompileException',
                "Unexpected closing of the tag 'foreach'"
            ),
            array(
                'Layers: {switch 1} {if 1} {case 1} {/if} {/switch} end',
                'Fenom\Error\CompileException',
                "Unexpected tag 'case' (this tag can be used with 'switch')"
            ),
            array('Layers: {/switch} end', 'Fenom\Error\CompileException', "Unexpected closing of the tag 'switch'"),
            array('Layers: {if 1} end', 'Fenom\Error\CompileException', "Unclosed tag: {if}"),
        );
    }

    public static function providerExtends()
    {
        return array(
            array('{extends file="parent.tpl"}{block name="bk1"} block1 {/block}', "Template extended by block1"),
            array('{extends "parent.tpl"}{block "bk1"} block1 {/block}', "Template extended by block1"),
            array(
                '{extends "parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} garbage',
                "Template extended by block1"
            ),
            array(
                '{extends file="parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} garbage',
                "Template multi-extended by block1"
            ),
            array(
                '{extends "parent.tpl"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} {block "bk3"} block3 {/block} garbage',
                "Template multi-extended by block1"
            ),
            array(
                '{extends "parent.tpl"}{set $bk = "bk3"}{block "bk1"} block1 {/block}{block "bk2"} block2 {/block} {block "$bk"} block3 {/block} garbage',
                "Template multi-extended by block1"
            ),
        );
    }

    public static function providerIsOperator()
    {
        return array(
            // is {$type}
            array('{if $one is int} block1 {else} block2 {/if}', 'block1'),
            array('{if $one && $one is int} block1 {else} block2 {/if}', 'block1'),
            array('{if $zero && $one is int} block1 {else} block2 {/if}', 'block2'),
            array('{if $one is 1} block1 {else} block2 {/if}', 'block1'),
            array('{if $one is 2} block1 {else} block2 {/if}', 'block2'),
            array('{if $one is not int} block1 {else} block2 {/if}', 'block2'),
            array('{if $one is not 1} block1 {else} block2 {/if}', 'block2'),
            array('{if $one is not 2} block1 {else} block2 {/if}', 'block1'),
            array('{if $one is $one} block1 {else} block2 {/if}', 'block1'),
            array('{if $bool is true} block1 {else} block2 {/if}', 'block1'),
            array('{if $float is float} block1 {else} block2 {/if}', 'block1'),
            array('{if $float is not float} block1 {else} block2 {/if}', 'block2'),
            array('{if $obj is object} block1 {else} block2 {/if}', 'block1'),
            array('{if $obj is $obj} block1 {else} block2 {/if}', 'block1'),
            array('{if $list is array} block1 {else} block2 {/if}', 'block1'),
            array('{if $list is iterable} block1 {else} block2 {/if}', 'block1'),
            array('{if $list is not scalar} block1 {else} block2 {/if}', 'block1'),
            array('{if $list is $list} block1 {else} block2 {/if}', 'block1'),
            array('{if $one is scalar} block1 {else} block2 {/if}', 'block1'),
            // is set
            array('{if $one is set} block1 {else} block2 {/if}', 'block1'),
            array('{if $one is not set} block1 {else} block2 {/if}', 'block2'),
            array('{if $unexists is set} block1 {else} block2 {/if}', 'block2'),
            array('{if $unexists is not set} block1 {else} block2 {/if}', 'block1'),
            array('{if 5 is set} block1 {else} block2 {/if}', 'block1'),
            array('{if time() is set} block1 {else} block2 {/if}', 'block1'),
            array('{if null is set} block1 {else} block2 {/if}', 'block2'),
            array('{if 0 is empty} block1 {else} block2 {/if}', 'block1'),
            array('{if "" is empty} block1 {else} block2 {/if}', 'block1'),
            array('{if [] is empty} block1 {else} block2 {/if}', 'block1'),
            array('{if "data" is empty} block1 {else} block2 {/if}', 'block2'),
            array('{if time() is not empty} block1 {else} block2 {/if}', 'block1'),
            // is empty
            array('{if $one is empty} block1 {else} block2 {/if}', 'block2'),
            array('{if $one is not empty} block1 {else} block2 {/if}', 'block1'),
            array('{if $unexists is empty} block1 {else} block2 {/if}', 'block1'),
            array('{if $unexists is not empty} block1 {else} block2 {/if}', 'block2'),
            array('{if $zero is empty} block1 {else} block2 {/if}', 'block1'),
            array('{if $zero is not empty} block1 {else} block2 {/if}', 'block2'),
            // instaceof
            array('{if $obj is StdClass} block1 {else} block2 {/if}', 'block1'),
            array('{if $obj is \StdClass} block1 {else} block2 {/if}', 'block1'),
            array('{if $obj is not \My\StdClass} block1 {else} block2 {/if}', 'block1'),
            // event, odd
            array('{if $one is odd} block1 {else} block2 {/if}', 'block1'),
            array('{if $one is even} block1 {else} block2 {/if}', 'block2'),
            array('{if ($one + 1) is even} block1 {else} block2 {/if}', 'block1'),
            array('{if $two is even} block1 {else} block2 {/if}', 'block1'),
            array('{if $two is odd} block1 {else} block2 {/if}', 'block2'),
            array('{if ($two+1) is odd} block1 {else} block2 {/if}', 'block1'),
            // template
            array('{if "welcome.tpl" is template} block1 {else} block2 {/if}', 'block1'),
            array('{if "welcome2.tpl" is template} block1 {else} block2 {/if}', 'block2'),

        );
    }

    public static function providerInOperator()
    {
        return array(
            array('{if $one in "qwertyuiop 1"} block1 {else} block2 {/if}', 'block1'),
            array('{if $one in string "qwertyuiop 1"} block1 {else} block2 {/if}', 'block1'),
            array('{if $one in "qwertyuiop"} block1 {else} block2 {/if}', 'block2'),
            array('{if $one not in "qwertyuiop 1"} block1 {else} block2 {/if}', 'block2'),
            array('{if $one not in "qwertyuiop"} block1 {else}v block2 {/if}', 'block1'),
            array('{if $one in [1, 2, 3]} block1 {else} block2 {/if}', 'block1'),
            array('{if $one in list [1, 2, 3]} block1 {else} block2 {/if}', 'block1'),
            array('{if $one in ["one", "two", "three"]} block1 {else} block2 {/if}', 'block2'),
            array('{if $one in keys [1 => "one", 2 => "two", 3 => "three"]} block1 {else} block2 {/if}', 'block1'),
            array('{if $one in $two} block1 {else} block2 {/if}', 'block2'),
            array('{if $one in $list} block1 {else} block2 {/if}', 'block1'),
            array('{if "one" in $num} block1 {else} block2 {/if}', 'block1'),
            array('{if "one" in $level1.level2} block1 {else} block2 {/if}', 'block1'),
        );
    }

    public static function providerInOperatorInvalid()
    {
        return array(
            array('{$one not all 3}', 'Fenom\Error\CompileException', "Unexpected token 'not'"),
            array('{$one in all}', 'Fenom\Error\CompileException', "Unexpected token 'all'"),
            array('{$one in string [1,2,3]}', 'Fenom\Error\CompileException', "Can not use string operation for array"),
            array('{$one in list "blah"}', 'Fenom\Error\CompileException', "Can not use array operation for string"),
            array('{$one in true}', 'Fenom\Error\CompileException', "Unexpected token 'true'"),
        );
    }

    public static function providerConcat()
    {
        return array(
            array('{"string" ~ $one ~ up("end")}', "string1END"),
            array('{"string" ~ $one++ ~ "end"}', "string1end"),
            array('{"string" ~ ++$one ~ "end"}', "string2end"),
            array('{"string" ~ "one" ~ "end"}', "stringoneend"),
            array('{"string" ~ 1 ~ "end"}', "string1end"),
            array('{$one ~= "string"} is {$one}', "1string is 1string"),

            array('{"string" ~~ $one ~~ up("end")}', "string 1 END"),
            array('{"string" ~~ $one++ ~~ "end"}', "string 1 end"),
            array('{"string" ~~ ++$one ~~ "end"}', "string 2 end"),
            array('{"string" ~~ "one" ~~ "end"}', "string one end"),
            array('{"string" ~~ 1 ~~ "end"}', "string 1 end"),
            array('{$one ~= "string"} is {$one}', "1string is 1string"),
        );
    }

    public function providerStatic()
    {
        return array(
//            array('{Fenom\TemplateTest::multi x=3 y=4}', '12'),
            array('{Fenom\TemplateTest::multi(3,4)}', '12'),
            array('{12 + Fenom\TemplateTest::multi(3,4)}', '24'),
            array('{12 + 3|Fenom\TemplateTest::multi:4}', '24'),
        );
    }

    public function providerStaticInvalid()
    {
        return array(
            array(
                '{Fenom\TemplateTest::multi x=3 y=4}',
                'Fenom\Error\SecurityException',
                "Static methods are disabled",
                Fenom::DENY_STATICS
            ),
            array(
                '{Fenom\TemplateTest::multi(3,4)}',
                'Fenom\Error\SecurityException',
                "Static methods are disabled",
                Fenom::DENY_STATICS
            ),
            array(
                '{12 + Fenom\TemplateTest::multi(3,4)}',
                'Fenom\Error\SecurityException',
                "Static methods are disabled",
                Fenom::DENY_STATICS
            ),
            array(
                '{12 + 3|Fenom\TemplateTest::multi:4}',
                'Fenom\Error\SecurityException',
                "Static methods are disabled",
                Fenom::DENY_STATICS
            ),
            array(
                '{Fenom\TemplateTest::multi_invalid x=3 y=4}',
                'Fenom\Error\CompileException',
                'Method Fenom\TemplateTest::multi_invalid doesn\'t exist'
            ),
            array(
                '{Fenom\TemplateTest::multi_invalid(3,4)}',
                'Fenom\Error\CompileException',
                'Method Fenom\TemplateTest::multi_invalid doesn\'t exist'
            ),
            array(
                '{12 + Fenom\TemplateTest::multi_invalid(3,4)}',
                'Fenom\Error\CompileException',
                'Method Fenom\TemplateTest::multi_invalid doesn\'t exist'
            ),
            array(
                '{12 + 3|Fenom\TemplateTest::multi_invalid:4}',
                'Fenom\Error\CompileException',
                'Method Fenom\TemplateTest::multi_invalid doesn\'t exist'
            ),
        );
    }

    /**
     * @dataProvider providerScalars
     */
    public function testScalars($code, $result)
    {
        $this->exec("{" . $code . "}", $this->values, $result);
    }

    /**
     * @dataProvider providerVars
     */
    public function testVars($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }

    /**
     * @dataProvider providerDo
     */
    public function testDo($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }


    /**
     * @dataProvider providerVarsInvalid
     */
    public function testVarsInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerModifiers
     */
    public function testModifiers($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }

    /**
     * @dataProvider providerModifiersInvalid
     */
    public function testModifiersInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @group expression
     * @dataProvider providerExpressions
     */
    public function testExpressions($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }

    /**
     * @dataProvider providerExpressionsInvalid
     */
    public function testExpressionsInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }


    public static function providerInclude()
    {
        $a       = array(
            "name"        => "welcome",
            "tpl"         => "welcome.tpl",
            "fragment"    => "come",
            "pr_fragment" => "Come",
            "pr_name"     => "Welcome",
            "username"    => "Master",
            "email"       => "dev@null.net"
        );

        $result  = 'Include <b>Welcome, Master (dev@null.net)</b>  template';
        $result2 = 'Include <b>Welcome, Flame (dev@null.net)</b>  template';
        $result3 = 'Include <b>Welcome, Master (flame@dev.null)</b>  template';
        $result4 = 'Include <b>Welcome, Flame (flame@dev.null)</b>  template';

        $recursive_result = 'Include <b>Hello, Master (dev@null.net)</b> template';
        $recursive_result2 = 'Include <b>Hello, Flame (dev@null.net)</b> template';
        return array(
            array('Include {include "welcome.tpl"} template', $a, $result),
            array('Include {include "welcome.tpl"} template', $a, $result, Fenom::FORCE_INCLUDE),
            array('Include {include "recursive.tpl"} template', $a, $recursive_result, Fenom::FORCE_INCLUDE),
            array('Include {include $tpl} template', $a, $result),
            array('Include {include "$tpl"} template', $a, $result),
            array('Include {include "{$tpl}"} template', $a, $result),
            array('Include {include "$name.tpl"} template', $a, $result),
            array('Include {include "{$name}.tpl"} template', $a, $result),
            array('Include {include "{$pr_name|lower}.tpl"} template', $a, $result),
            array('Include {include "wel{$fragment}.tpl"} template', $a, $result),
            array('Include {include "wel{$pr_fragment|lower}.tpl"} template', $a, $result),
            array('Include {include "welcome.tpl" username="Flame"} template', $a, $result2),
            array('Include {include "welcome.tpl" username="Flame"} template', $a, $result2, Fenom::FORCE_INCLUDE),
            array('Include {include "recursive.tpl" username="Flame"} template', $a, $recursive_result2, Fenom::FORCE_INCLUDE),
            array('Include {include "welcome.tpl" email="flame@dev.null"} template', $a, $result3),
            array(
                'Include {include "welcome.tpl" email="flame@dev.null"} template',
                $a,
                $result3,
                Fenom::FORCE_INCLUDE
            ),
            array(
                'Include {include "welcome.tpl" username="Flame" email="flame@dev.null"} template',
                $a,
                $result4,
            ),
        );
    }

    /**
     * @group dev
     * @dataProvider providerInclude
     */
    public function testInclude($code, $vars, $result, $options = 0)
    {
        $this->tpls(array(
            'welcome.tpl' => '<b>Welcome, {$username} ({$email})</b>',
            'recursive.tpl' => '<b>Hello, {$username}  ({$email}){if false}{include "recursive.tpl"}{/if}</b>'
        ));
        $this->exec($code, $vars, $result, $options);
    }

    /**
     * @dataProvider providerIncludeInvalid
     * @group testIncludeInvalid
     */
    public function testIncludeInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @group insert
     * @dataProvider providerInsert
     */
    public function testInsert($code, $vars, $result)
    {
        $this->values = $vars;
        $this->tpl("insert.tpl", $code);
        $tpl = $this->fenom->getTemplate('insert.tpl');
        $this->assertSame($result, $tpl->fetch($vars));
        $this->assertTrue($tpl->isValid());
    }

    /**
     * @group insert
     * @dataProvider providerInsertInvalid
     */
    public function testInsertInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerIf
     * @group test-if
     */
    public function testIf($code, $vars, $result, $options = 0)
    {
        $this->exec($code, $vars, $result, $options);
    }

    /**
     * @dataProvider providerIfInvalid
     */
    public function testIfInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerCreateVar
     */
    public function testCreateVar($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }

    /**
     * @dataProvider providerCreateVarInvalid
     */
    public function testCreateVarInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerArrays
     * @group arrays
     */
    public function testArrays($code, $vars)
    {
        $v = $this->getVars();
        $v['vars'] = $vars;
        $this->exec($code.'{if $arr === $vars}equal{/if}', $v, 'equal');
    }

    /**
     * @dataProvider providerUnset
     * @group unset
     */
    public function testUnset($code, $result)
    {
        $this->exec($code, $this->getVars(), $result);
    }

    /**
     * @dataProvider providerCreateVarInvalid
     */
//    public function testCreateVarInvalid($code, $exception, $message, $options = 0)
//    {
//        $this->execError($code, $exception, $message, $options);
//    }

    /**
     * @group ternary
     * @dataProvider providerTernary
     */
    public function testTernary($code, $vars, $result = 'right')
    {
        $this->exec(__FUNCTION__ . ": $code end", $vars, __FUNCTION__ . ": $result end");
    }


    public static function providerForeach()
    {
        $a = array(
            "list"  => array(1 => "one", 2 => "two", 3 => "three"),
            "empty" => array(),
            "obj"   => new Helper("testing")
        );
        return array(
            array('Foreach: {foreach $list as $e} {$e}, {/foreach} end', $a, 'Foreach: one, two, three, end'),
            array('Foreach: {foreach $list as $e} {$e},{break} break {/foreach} end', $a, 'Foreach: one, end'),
            array('Foreach: {foreach $list} 1, {/foreach} end', $a, 'Foreach: 1, 1, 1, end'),
            array(
                'Foreach: {foreach $list as $e} {$e},{continue} continue {/foreach} end',
                $a,
                'Foreach: one, two, three, end'
            ),
            array(
                'Foreach: {foreach ["one", "two", "three"] as $e} {$e}, {/foreach} end',
                $a,
                'Foreach: one, two, three, end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e} {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: 1 => one, 2 => two, 3 => three, end'
            ),
            array(
                'Foreach: {foreach [1 => "one", 2 => "two", 3 => "three"] as $k => $e} {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: 1 => one, 2 => two, 3 => three, end'
            ),
            array('Foreach: {foreach $empty as $k => $e} {$k} => {$e}, {/foreach} end', $a, 'Foreach: end'),
            array('Foreach: {foreach [] as $k => $e} {$k} => {$e}, {/foreach} end', $a, 'Foreach: end'),
            array('Foreach: {foreach $obj->getArray() as $k => $e} {$k} => {$e}, {/foreach} end', $a, 'Foreach: 0 => 1, 1 => 2, 2 => 3, end'),
            array('Foreach: {foreach $unexists as $k => $e} {$k} => {$e}, {/foreach} end', $a, 'Foreach: end'),
            array(
                'Foreach: {foreach $empty as $k => $e} {$k} => {$e}, {foreachelse} empty {/foreach} end',
                $a,
                'Foreach: empty end'
            ),
            array(
                'Foreach: {foreach $list as $e index=$i} {$i}: {$e}, {/foreach} end',
                $a,
                'Foreach: 0: one, 1: two, 2: three, end'
            ),
            array(
                'Foreach: {foreach $list as $e} {$e@index}: {$e}, {/foreach} end',
                $a,
                'Foreach: 0: one, 1: two, 2: three, end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e index=$i} {$i}: {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: 0: 1 => one, 1: 2 => two, 2: 3 => three, end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e} {$e@index}: {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: 0: 1 => one, 1: 2 => two, 2: 3 => three, end'
            ),
            array(
                'Foreach: {foreach $empty as $k => $e index=$i} {$i}: {$k} => {$e}, {foreachelse} empty {/foreach} end',
                $a,
                'Foreach: empty end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e first=$f index=$i} {if $f}first{/if} {$i}: {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: first 0: 1 => one, 1: 2 => two, 2: 3 => three, end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e} {if $e@first}first{/if} {$e@index}: {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: first 0: 1 => one, 1: 2 => two, 2: 3 => three, end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e last=$l first=$f index=$i} {if $f}first{/if} {$i}: {$k} => {$e}, {if $l}last{/if} {/foreach} end',
                $a,
                'Foreach: first 0: 1 => one, 1: 2 => two, 2: 3 => three, last end'
            ),
            array(
                'Foreach: {foreach $list as $k => $e} {if $e@first}first{/if} {$e@index}: {$k} => {$e}, {if $e@last}last{/if} {/foreach} end',
                $a,
                'Foreach: first 0: 1 => one, 1: 2 => two, 2: 3 => three, last end'
            ),
            array(
                'Foreach: {foreach $empty as $k => $e last=$l first=$f index=$i} {if $f}first{/if} {$i}: {$k} => {$e}, {if $l}last{/if} {foreachelse} empty {/foreach} end',
                $a,
                'Foreach: empty end'
            ),
            array(
                'Foreach: {foreach [1 => "one", 2 => "two", 3 => "three"] as $k => $e last=$l first=$f index=$i} {if $f}first{/if} {$i}: {$k} => {$e}, {if $l}last{/if} {/foreach} end',
                $a,
                'Foreach: first 0: 1 => one, 1: 2 => two, 2: 3 => three, last end'
            ),
            array(
                'Foreach: {foreach 1..3 as $k => $e} {$k} => {$e}, {/foreach} end',
                $a,
                'Foreach: 0 => 1, 1 => 2, 2 => 3, end'
            ),
            array(
                'Foreach: {foreach 1..3 as $k => $e last=$l} {$k} => {$e}, {if $l}last{/if} {/foreach} end',
                $a,
                'Foreach: 0 => 1, 1 => 2, 2 => 3, last end'
            ),
            array(
                'Foreach: {foreach $.get.items as $e} {$e}, {/foreach} end',
                $a,
                'Foreach: one, two, three, end'
            ),
        );
    }

    /**
     * @dataProvider providerForeach
     * @backupGlobals
     */
    public function testForeach($code, $vars, $result)
    {
        $_GET['items'] = array('one', 'two', 'three');
        $this->exec($code, $vars, $result);
    }

    /**
     * @dataProvider providerForeachInvalid
     */
    public function testForeachInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerFor
     */
//    public function testFor($code, $vars, $result)
//    {
//        $this->exec($code, $vars, $result);
//    }

    /**
     * @dataProvider providerForInvalid
     */
//    public function testForInvalid($code, $exception, $message, $options = 0)
//    {
//        $this->execError($code, $exception, $message, $options);
//    }

    /**
     * @group testIgnores
     * @dataProvider providerIgnores
     */
    public function testIgnores($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }



    public static function providerSwitch()
    {
        $code1 = 'Switch: {switch $a}
        {case 1, "one"} one
        {case 2, "two"} two
        {case "string", default} str
        {default} def
        {/switch} end';

        $code2 = 'Switch: {switch $a}
        {case 1, "one"} one
        {case 2, "two"} two
        {case "string"} str
        {/switch} end';

        $code3 = 'Switch: {switch $a} invalid
        {case 1, "one"} one
        {/switch} end';

        $code4 = 'Switch:{switch $a}{case 1}<b>one</b>{/switch}end';

        return array(
            array($code1, array("a" => 1), 'Switch: one end'),
            array($code1, array("a" => 'one'), 'Switch: one end'),
            array($code1, array("a" => 2), 'Switch: two end'),
            array($code1, array("a" => 'two'), 'Switch: two end'),
            array($code1, array("a" => "string"), 'Switch: str end'),
            array($code1, array("a" => "unk"), 'Switch: str def end'),
            array($code2, array("a" => "unk"), 'Switch: end'),
            array($code3, array("a" => 1), 'Switch: one end'),
            array($code3, array("a" => 'one'), 'Switch: one end'),
            array($code4, array("a" => 1), 'Switch:<b>one</b>end'),
        );
    }


    /**
     * @group switch
     * @dataProvider providerSwitch
     */
    public function testSwitch($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }

    /**
     * @group switch-bad
     * @dataProvider providerSwitchInvalid
     */
    public function testSwitchInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerWhile
     */
    public function testWhile($code, $vars, $result)
    {
        $this->exec($code, $vars, $result);
    }

    /**
     * @dataProvider providerWhileInvalid
     */
    public function testWhileInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerLayersInvalid
     */
    public function testLayersInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @group is_operator
     * @dataProvider providerIsOperator
     */
    public function testIsOperator($code, $result)
    {
        $this->exec($code, self::getVars(), $result);
    }

    /**
     * @group in_operator
     * @dataProvider providerInOperator
     */
    public function testInOperator($code, $result)
    {
        $this->exec($code, self::getVars(), $result);
    }

    /**
     * @group in_operator_invalid
     * @dataProvider providerInOperatorInvalid
     */
    public function testInOperatorInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }

    /**
     * @dataProvider providerConcat
     * @group testConcat
     */
    public function testConcat($code, $result)
    {
        $this->exec($code, self::getVars(), $result);
    }

    /**
     * @group accessor
     * @dataProvider providerAccessor
     */
//    public function testAccessor($code, $result)
//    {
//        $this->exec($code, self::getVars(), $result);
//    }

    /**
     * @group accessor
     * @dataProvider providerAccessorInvalid
     */
//    public function testAccessorInvalid($code, $exception, $message, $options = 0)
//    {
//        $this->execError($code, $exception, $message, $options);
//    }

    /**
     * @group static
     * @dataProvider providerStatic
     */
    public function testStatic($code, $result)
    {
        $this->exec($code, self::getVars(), $result, true);
    }

    /**
     * Helper
     * @param $x
     * @param int $y
     * @return mixed
     */
    public static function multi($x, $y = 42)
    {
        return $x * $y;
    }

    /**
     * @group static-invalid
     * @dataProvider providerStaticInvalid
     */
    public function testStaticInvalid($code, $exception, $message, $options = 0)
    {
        $this->execError($code, $exception, $message, $options);
    }


    public static function providerRange()
    {
        return array(
            array('{set $a=1..3}', "1,2,3,"),
//            array('{set $a=0..0}', ""),
//            array('{set $a=1..1}', ""),
//            array('{set $a="a".."f"}', "a,b,c,d,e,f,"),
//            array('{set $a=1.."f"}', "1,0,"),
//            array('{set $a="a"..2}', "0,1,2,"),
            array('{set $a=$one..$three}', "1,2,3,"),
            array('{set $a=$one..3}', "1,2,3,"),
            array('{set $a=1..$three}', "1,2,3,"),
            array('{set $a=$one..$three++}', "1,2,3,"),
            array('{set $a=$one..++$three}', "1,2,3,4,"),
            array('{set $a=$one--..$three++}', "1,2,3,"),
            array('{set $a=--$one..++$three}', "0,1,2,3,4,"),
//            array('{set $a="a"|up.."f"|up}', "A,B,C,D,E,F,"),
            array('{set $a=$one|min:0..$three|max:4}', "0,1,2,3,4,"),
            array('{set $a=$one|min:0..4}', "0,1,2,3,4,"),
            array('{set $a=0..$three|max:4}', "0,1,2,3,4,"),
            array('{set $a=0..$three|max:4}', "0,1,2,3,4,"),

            array('{set $a=range(1,3)}', "1,2,3,"),
            array('{set $a=range(1,3, 2)}', "1,3,"),
            array('{set $a=range(1..3, 2)}', "1,3,"),
            array('{set $a=range(1..3, 3)}', "1,"),

            array('{set $a=range(1,3, -1)}', "3,2,1,"),
            array('{set $a=range(1,3, -2)}', "3,1,"),
            array('{set $a=range(1..3, -2)}', "3,1,"),
            array('{set $a=range(1..3, -3)}', "3,"),
        );
    }

    /**
     * @dataProvider providerRange
     * @group testRange
     * @param string $code
     * @param string $result
     */
    public function testRange($code, $result)
    {
        $this->exec($code.'{foreach $a as $v}{$v},{/foreach}', self::getVars(), $result);
    }

}

