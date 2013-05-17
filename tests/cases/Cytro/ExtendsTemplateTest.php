<?php
namespace Cytro;
use Cytro, Cytro\TestCase;
use Symfony\Component\Process\Exception\LogicException;

class ExtendsTemplateTest extends TestCase {

    public static function templates() {
        return array(
            array(
                "name"  => "level.0.tpl",
                "level" => 0,
                "blocks" => array(
                    "b1" => "default 5",
                    "b2" => "empty 0"
                ),
                "result" => array(
                    "b1" => "default 5",
                    "b2" => "empty 0"
                ),
            ),
            array(
                "name"  => "level.1.tpl",
                "level" => 1,
                "blocks" => array(
                    "b1" => "from level 1"
                ),
                "result" => array(
                    "b1" => "from level 1",
                    "b2" => "empty 0"
                ),
            ),
            array(
                "name"  => "level.2.tpl",
                "level" => 2,
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
                "name"  => "level.3.tpl",
                "level" => 3,
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

    public static function generate($block_mask, $extend_mask) {
        $t = array();
        foreach(self::templates() as $level => $tpl) {
            $src = 'level#'.$level.' ';
            foreach($tpl["blocks"] as $bname => &$bcode) {
                $src .= sprintf($block_mask, $bname, $bname.': '.$bcode)." level#$level ";
            }
            $dst = "level#0 ";
            foreach($tpl["result"] as $bname => &$bcode) {
                $dst .= $bname.': '.$bcode.' level#0 ';
            }
            if($level) {
                $src = sprintf($extend_mask, $level-1).' '.$src;
            }
            $t[ $tpl["name"] ] = array("src" => $src, "dst" => $dst);
        }
        return $t;
    }
    /**
     * @group static-extend
     */
    public function testTemplateExtends() {
        $vars = array(
            "b1" => "b1",
            "b2" => "b2",
            "b3" => "b3",
            "b4" => "b4",
            "level" => "level",
            "default" => 5
        );
        $tpls = self::generate('{block "%s"}%s{/block}', '{extends "level.%d.tpl"}');
        foreach($tpls as $name => $tpl) {
            $this->tpl($name, $tpl["src"]);
//            var_dump($src, "----\n\n----", $dst);ob_flush();fgetc(STDIN);
            $this->assertSame($this->cytro->fetch($name, $vars), $tpl["dst"]);
        }
        $tpls = self::generate('{block "{$%s}"}%s{/block}', '{extends "level.%d.tpl"}');
        arsort($tpls);
        foreach($tpls as $name => $tpl) {
            $this->tpl("d.".$name, $tpl["src"]);
//            var_dump($tpl["src"], "----\n\n----", $tpl["dst"]);ob_flush();fgetc(STDIN);
            $this->assertSame($this->cytro->fetch("d.".$name, $vars), $tpl["dst"]);
//            var_dump($name);ob_flush();fgets(STDIN);
        }
    }
}

