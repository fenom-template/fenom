<?php

require_once __DIR__.'/../src/Fenom.php';
require_once __DIR__.'/../tests/tools.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled');
$fenom->setOptions(Fenom::AUTO_RELOAD);
$fenom->display(["blocks/body.tpl", "layout.tpl"], []);
// $fenom->getTemplate("problem.tpl");