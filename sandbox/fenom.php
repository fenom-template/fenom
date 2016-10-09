<?php

require_once __DIR__.'/../src/Fenom.php';
require_once __DIR__.'/../tests/tools.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled');
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::FORCE_VERIFY | Fenom::FORCE_INCLUDE);
//var_dump($fenom->compile("nested.tpl", [])->getTemplateCode());
//exit;
var_dump($fenom->compile('bug241/recursive.tpl', false)->getBody());
//var_dump($fenom->compile('bug249/bread.tpl', false)->getBody());
//var_dump($fenom->compile("bug158/main.tpl", [])->getTemplateCode());
//var_dump($fenom->display("bug158/main.tpl", []));
// $fenom->getTemplate("problem.tpl");
