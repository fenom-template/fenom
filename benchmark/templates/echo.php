<?php
$data = json_decode(file_get_contents(__DIR__.'/echo/data.json'), true);

exec("rm -rf ".__DIR__."/../compile/*");

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

$aspect = Aspect::factory(__DIR__, __DIR__."/../compile/", Aspect::CHECK_MTIME);

$start = microtime(true);
$template = $aspect->fetch('echo/smarty.tpl', $data);
var_dump("Aspect: ".(microtime(true)-$start));

$start = microtime(true);
$template = $aspect->fetch('echo/smarty.tpl', $data);
var_dump("Aspect cached: ".(microtime(true)-$start));

