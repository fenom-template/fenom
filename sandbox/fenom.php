<?php

require_once __DIR__.'/../src/Fenom.php';
require_once __DIR__.'/../tests/tools.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled');
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::FORCE_COMPILE);
//var_dump($fenom->compileCode('{set $z = "A"~~"B"}')->getBody());
//var_dump($fenom->compile("bug158/main.tpl", [])->getTemplateCode());
var_dump($fenom->display("bug158/main.tpl", []));
// $fenom->getTemplate("problem.tpl");