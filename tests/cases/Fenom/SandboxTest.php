<?php

namespace Fenom;


class SandboxTest extends TestCase {

    /**
     * @group sb
     */
    public function test()
    {
//        return;
//        $this->fenom->setOptions(\Fenom::FORCE_VERIFY);
//        $this->fenom->addAccessorSmart('q', 'Navi::$q', \Fenom::ACCESSOR_VAR);
//        $this->assertEquals([1, 2, 4, "as" => 767, "df" => ["qert"]], [1, 2, 4, "as" => 767, "df" => ["qet"]]);
//        $this->fenom->addBlockCompiler('php', 'Fenom\Compiler::nope', function ($tokens, Tag $tag) {
//            return '<?php ' . $tag->cutContent();
//        });
//        $this->tpl('welcome.tpl', '{$a}');
//            var_dump($this->fenom->compileCode('{set $a=$one|min:0..$three|max:4}')->getBody());

//        try {
//            var_dump($this->fenom->compileCode('{foreach $a as $k}A{$k:first}{foreachelse}B{/foreach}')->getBody());
//        } catch (\Exception $e) {
//            print_r($e->getMessage() . "\n" . $e->getTraceAsString());
//            while ($e->getPrevious()) {
//                $e = $e->getPrevious();
//                print_r("\n\n" . $e->getMessage() . " in {$e->getFile()}:{$e->getLine()}\n" . $e->getTraceAsString());
//            }
//        }
//        exit;
    }

} 