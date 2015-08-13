<?php

require_once __DIR__.'/../src/Fenom.php';
require_once __DIR__.'/../tests/tools.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/../tests/resources/provider', __DIR__.'/../tests/resources/compile');
$fenom->setOptions(Fenom::AUTO_RELOAD);
var_dump($fenom->fetch('extends/auto/parent.tpl'));
//var_dump($fenom->compile("bug158/main.tpl", [])->getTemplateCode());
//var_dump($fenom->display("bug158/main.tpl", []));
// $fenom->getTemplate("problem.tpl");