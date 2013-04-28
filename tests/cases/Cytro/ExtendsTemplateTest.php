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
            //var_dump($src, "----\n\n----", $dst);ob_flush();fgetc(STDIN);
            $this->assertSame($this->cytro->fetch($name, $vars), $tpl["dst"]);
        }
        $tpls = self::generate('{block "{$%s}"}%s{/block}', '{extends "level.%d.tpl"}');
        arsort($tpls);
        foreach($tpls as $name => $tpl) {
            $this->tpl("d.".$name, $tpl["src"]);
            //var_dump($src, "----\n\n----", $dst);ob_flush();fgetc(STDIN);
            $this->assertSame($this->cytro->fetch("d.".$name, $vars), $tpl["dst"]);
            //var_dump($name);ob_flush();fgets(STDIN);
        }
    }

//    public static function providerDynamicExtends() {
//        $tpls = array();
//        //foreach(self::templates() as $i => $tpl) {
//        //    $tpls[] = array($tpl[0], );
//        //}
//        $data = self::providerExtends();
//        $data[2][1] = str_replace('"b2"', '"b{$two}"', $data[2][1]);
//        return $data;
//    }

//    public function setUp() {
//        $this->cytro = Cytro::factory(CYTRO_RESOURCES.'/template', CYTRO_RESOURCES.'/compile');
//    }
//
//    /**
//     * @dataProvider providerExtends
//     * @param $name
//     * @param $code
//     * @param $vars
//     * @param $result
//     */
//    public function testStaticExtends($name, $code, $vars, $result) {
//        static $i = 0;
//        $vars["iteration"] = $i++;
//        $this->execTpl($name, $code, $vars, $result);
//    }
//
//    /**
//     * @dataProvider providerDynamicExtends
//     * @param $name
//     * @param $code
//     * @param $vars
//     * @param $result
//     */
//    public function testDynamicExtends($name, $code, $vars, $result) {
//        static $i = 0;
//        $vars["iteration"] = $i++;
//        $this->execTpl($name, $code, $vars, $result, 0);
//    }
//
//    /**
//     * @group extends
//     */
//    public function _testParentLevel() {
//	    //echo($this->aspect->getTemplate("parent.tpl")->_body); exit;
//	    $this->assertSame($this->cytro->fetch("parent.tpl", array("a" => "a char")), "Parent template\nBlock1: Block2: Block3: default");
//    }
//
//	/**
//	 * @group extends
//	 */
//	public function testChildLevel1() {
//		//echo($this->aspect->fetch("child1.tpl", array("a" => "a char"))); exit;
//	}
//
//	/**
//	 * @group extends
//	 */
//	public function _testChildLevel3() {
//        echo($this->cytro->getTemplate("child3.tpl")->getBody()); exit;
//	}
}

