<?php
//$data = json_decode(file_get_contents(__DIR__.'/echo/data.json'), true);
require_once __DIR__.'/../scripts/bootstrap.php';
exec("rm -rf ".__DIR__."/../compile/*");

echo "A lot outputs...\n";

Benchmark::run("smarty3", 'echo/smarty.tpl', __DIR__.'/echo/data.json', false, '!compiled and !loaded');
Benchmark::run("smarty3", 'echo/smarty.tpl', __DIR__.'/echo/data.json', false, 'compiled and !loaded');
Benchmark::run("smarty3", 'echo/smarty.tpl', __DIR__.'/echo/data.json', true, 'compiled and loaded');

Benchmark::run("twig", 'echo/twig.tpl', __DIR__.'/echo/data.json', false, '!compiled and !loaded');
Benchmark::run("twig", 'echo/twig.tpl', __DIR__.'/echo/data.json', false, 'compiled and !loaded');
Benchmark::run("twig", 'echo/twig.tpl', __DIR__.'/echo/data.json', true, 'compiled and loaded');

Benchmark::run("cytro", 'echo/smarty.tpl', __DIR__.'/echo/data.json', false, '!compiled and !loaded');
Benchmark::run("cytro", 'echo/smarty.tpl', __DIR__.'/echo/data.json', false, 'compiled and !loaded');
Benchmark::run("cytro", 'echo/smarty.tpl', __DIR__.'/echo/data.json', true, 'compiled and loaded');
exit;

require(__DIR__.'/../../vendor/autoload.php');
$smarty = new Smarty();
$smarty->compile_check = false;

$smarty->setTemplateDir(__DIR__);
$smarty->setCompileDir(__DIR__."/../compile/");

$start = microtime(true);
$smarty->assign($data);
$smarty->fetch('echo/smarty.tpl');
var_dump("Smarty3: ".(microtime(true)-$start));

$start = microtime(true);
$smarty->assign($data);
$smarty->fetch('echo/smarty.tpl');
var_dump("Smarty3 cached: ".(microtime(true)-$start));

Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem(__DIR__);
$twig = new Twig_Environment($loader, array(
	'cache' => __DIR__."/../compile/",
	'autoescape' => false, 
	'auto_reload' => false,
));

$start = microtime(true);
$template = $twig->loadTemplate('echo/twig.tpl');
$template->render($data);
var_dump("Twig: ".(microtime(true)-$start));

$start = microtime(true);
$template = $twig->loadTemplate('echo/twig.tpl');
$template->render($data);
var_dump("Twig cached: ".(microtime(true)-$start));

$cytro = Cytro::factory(__DIR__, __DIR__."/../compile/", Cytro::AUTO_RELOAD);

$start = microtime(true);
$template = $cytro->fetch('echo/smarty.tpl', $data);
var_dump("Cytro: ".(microtime(true)-$start));

$start = microtime(true);
$template = $cytro->fetch('echo/smarty.tpl', $data);
var_dump("Cytro cached: ".(microtime(true)-$start));

