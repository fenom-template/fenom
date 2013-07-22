<?php
require_once __DIR__.'/scripts/bootstrap.php';

$opt = getopt("h", array(
    /** @var string $message */
    "cleanup",
    /** @var boolean $stress */
    "stress:",
    /** @var boolean $auto_reload */
    "auto_reload",
    /** @vat boolean $help */
    /** @vat boolean $h */
    "help"
));

$opt += array(
    "stress" => 0
);

extract($opt);

if(isset($h) || isset($help)) {
    echo "
Start: ".basename(__FILE__)." [--stress COUNT] [--auto_reload] [--cleanup]
Usage: ".basename(__FILE__)." [--help | -h]
";
    exit;
}

Benchmark::$stress = intval($stress);
Benchmark::$auto_reload = isset($auto_reload);

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

echo "\nDone\n";
if(isset($cleanup)) {
    echo "Cleanup.\n";
    passthru("rm -rf ".__DIR__."/compile/*");
    passthru("rm -f ".__DIR__."/templates/inheritance/smarty/*");
    passthru("rm -f ".__DIR__."/templates/inheritance/twig/*");
}
