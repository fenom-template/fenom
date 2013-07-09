<?php
require_once __DIR__.'/scripts/bootstrap.php';
exec("rm -rf ".__DIR__."/compile/*");

echo "Smarty3 vs Twig vs Fenom\n\n";

echo "Generate templates... ";
passthru("php ".__DIR__."/templates/inheritance/smarty.gen.php");
passthru("php ".__DIR__."/templates/inheritance/twig.gen.php");
echo "Done\n";

echo "Testing a lot output...\n";

Benchmark::runs("smarty3", 'echo/smarty.tpl',   __DIR__.'/templates/echo/data.json');
Benchmark::runs("twig",    'echo/twig.tpl',     __DIR__.'/templates/echo/data.json');
Benchmark::runs("fenom",   'echo/smarty.tpl',   __DIR__.'/templates/echo/data.json');
//if(extension_loaded("phalcon")) {
//    Benchmark::runs("volt",   'echo/twig.tpl',  __DIR__.'/templates/echo/data.json');
//}

echo "\nTesting 'foreach' of big array...\n";

Benchmark::runs("smarty3", 'foreach/smarty.tpl', __DIR__.'/templates/foreach/data.json');
Benchmark::runs("twig",    'foreach/twig.tpl',   __DIR__.'/templates/foreach/data.json');
Benchmark::runs("fenom",   'foreach/smarty.tpl', __DIR__.'/templates/foreach/data.json');
//if(extension_loaded("phalcon")) {
//    Benchmark::runs("volt",   'foreach/twig.tpl', __DIR__.'/templates/foreach/data.json');
//}

echo "\nTesting deep 'inheritance'...\n";

Benchmark::runs("smarty3", 'inheritance/smarty/b100.tpl', __DIR__.'/templates/foreach/data.json');
Benchmark::runs("twig",    'inheritance/twig/b100.tpl', __DIR__.'/templates/foreach/data.json');
Benchmark::runs("fenom",  'inheritance/smarty/b100.tpl', __DIR__.'/templates/foreach/data.json');
//if(extension_loaded("phalcon")) {
//    Benchmark::runs("volt",  'inheritance/twig/b100.tpl', __DIR__.'/templates/foreach/data.json');
//}

echo "\nTesting " . Benchmark::STRESS_REQUEST_COUNT ." separate renderings...\n";
Benchmark::stress("smarty3", 'foreach/smarty.tpl', __DIR__.'/templates/foreach/data.json', Benchmark::STRESS_REQUEST_COUNT);
// twig takes minutes, so not comparable
//Benchmark::stress("twig",    'foreach/twig.tpl',   __DIR__.'/templates/foreach/data.json', Benchmark::STRESS_REQUEST_COUNT);
Benchmark::stress("fenom",   'foreach/smarty.tpl', __DIR__.'/templates/foreach/data.json', Benchmark::STRESS_REQUEST_COUNT);

echo "\nDone. Cleanup.\n";
//passthru("rm -rf ".__DIR__."/compile/*");
passthru("rm -f ".__DIR__."/templates/inheritance/smarty/*");
passthru("rm -f ".__DIR__."/templates/inheritance/twig/*");

echo "\nSmarty3 vs Fenom (more details)\n\n";

echo "Coming soon\n";