<?php

require_once __DIR__.'/../src/Fenom.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled');
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::AUTO_STRIP);
echo($fenom->compile("problem.tpl", false)->getBody());
// $fenom->getTemplate("problem.tpl");