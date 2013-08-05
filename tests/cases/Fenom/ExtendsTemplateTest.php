<?php
namespace Fenom;
use Fenom, Fenom\TestCase;

class ExtendsTemplateTest extends TestCase
{

    public function _testSandbox()
    {
        $this->fenom = Fenom::factory(FENOM_RESOURCES . '/provider', FENOM_RESOURCES . '/compile');
        try {
            print_r($this->fenom->getTemplate('use/child.tpl')->getBody());
        } catch (\Exception $e) {
            echo "$e";
        }
        exit;
    }

    /**
     * Templates skeletons
     * @param array $vars
     * @return array
     */
    public static function templates(array $vars)
    {
        return array(
            array(
                "name" => "level.0.tpl",
                "level" => 0,
                "blocks" => array(
                    "b1" => "default {\$default}",
                    "b2" => "empty 0"
                ),
                "result" => array(
                    "b1" => "default " . $vars["default"],
                    "b2" => "empty 0"
                ),
            ),
            array(
                "name" => "level.1.tpl",
                "level" => 1,
                "use" => false,
                "blocks" => array(
                    "b1" => "from level 1"
                ),
                "result" => array(
                    "b1" => "from level 1",
                    "b2" => "empty 0"
                ),
            ),
            array(
                "name" => "level.2.tpl",
                "level" => 2,
                "use" => false,
                "blocks" => array(
                    "b2" => "from level 2",
                    "b4" => "unused block"
                ),
                "result" => array(
                    "b1" => "from level 1",
                    "b2" => "from level 2"
                ),
            ),
            array(
                "name" => "level.3.tpl",
                "level" => 3,
                "use" => false,
                "blocks" => array(
                    "b1" => "from level 3",
                    "b2" => "also from level 3"
                ),
                "result" => array(
                    "b1" => "from level 3",
                    "b2" => "also from level 3"
                ),
            )
        );
    }

    /**
     * Generate templates by skeletons
     *
     * @param $block_mask
     * @param $extend_mask
     * @param array $skels
     * @return array
     */
    public static function generate($block_mask, $extend_mask, $skels)
    {
        $t = array();
        foreach ($skels as $level => $tpl) {
            $src = 'level#' . $level . ' ';

            foreach ($tpl["blocks"] as $bname => &$bcode) {
                $src .= sprintf($block_mask, $bname, $bname . ': ' . $bcode) . " level#$level ";
            }
            $dst = "level#0 ";
            foreach ($tpl["result"] as $bname => &$bcode) {
                $dst .= $bname . ': ' . $bcode . ' level#0 ';
            }
            if ($level) {
                $src = sprintf($extend_mask, $level - 1) . ' ' . $src;
            }
            $t[$tpl["name"]] = array("src" => $src, "dst" => $dst);
        }
        return $t;
    }

    public function _testTemplateExtends()
    {
        $vars = array(
            "b1" => "b1",
            "b2" => "b2",
            "b3" => "b3",
            "b4" => "b4",
            "level" => "level",
            "default" => 5
        );
        $tpls = self::generate('{block "%s"}%s{/block}', '{extends "level.%d.tpl"}', self::templates($vars));
        foreach ($tpls as $name => $tpl) {
            $this->tpl($name, $tpl["src"]);
            $this->assertSame($this->fenom->fetch($name, $vars), $tpl["dst"]);
        }
        return;
        $vars["default"]++;
        $this->fenom->flush();
        $tpls = self::generate('{block "{$%s}"}%s{/block}', '{extends "level.%d.tpl"}', self::templates($vars));
        arsort($tpls);
        foreach ($tpls as $name => $tpl) {
            $this->tpl("d." . $name, $tpl["src"]);
            $this->assertSame($this->fenom->fetch("d." . $name, $vars), $tpl["dst"]);
        }
        $vars["default"]++;
        $this->fenom->flush();
        $tpls = self::generate('{block "%s"}%s{/block}', '{extends "$level.%d.tpl"}', self::templates($vars));
        arsort($tpls);
        foreach ($tpls as $name => $tpl) {
            $this->tpl("x." . $name, $tpl["src"]);
            $this->assertSame($this->fenom->fetch("x." . $name, $vars), $tpl["dst"]);
        }
    }

    /**
     * @group use
     */
    public function testUse()
    {
        $this->fenom = Fenom::factory(FENOM_RESOURCES . '/provider', FENOM_RESOURCES . '/compile');
        $this->assertSame("<html>\n block 1 blocks \n block 2 child \n</html>", $this->fenom->fetch('use/child.tpl'));
    }

    public function _testParent()
    {

    }
}

