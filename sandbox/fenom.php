<?php
require_once __DIR__.'/../src/Fenom.php';

\Fenom::registerAutoload();

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled', Fenom::AUTO_RELOAD | Fenom::AUTO_ESCAPE);

$fenom->display('greeting.tpl');