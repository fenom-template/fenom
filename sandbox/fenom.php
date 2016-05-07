<?php

require_once __DIR__.'/../src/Fenom.php';
require_once __DIR__.'/../tests/tools.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/../tests/resources/compile');
$fenom->setOptions(Fenom::AUTO_RELOAD);
$fenom->addModifier('firstimg', function ($img) {
    return $img;
});
var_dump($fenom->compileCode('{foreach $list as $k => $e last=$l first=$f index=$i} {if $f}first{/if} {$i}: {$k} => {$e}, {if $l}last{/if} {/foreach}')->getTemplateCode());
//var_dump($fenom->compile("bug158/main.tpl", [])->getTemplateCode());
//var_dump($fenom->display("bug158/main.tpl", []));
// $fenom->getTemplate("problem.tpl");