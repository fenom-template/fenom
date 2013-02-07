<?php
$data = array(
	"inh" => 'inheritance',
	"var1" => 'val1'
);

function trace() {
	$e = new Exception();
	echo $e->getTraceAsString();
	ob_flush();
	exit(0);
}

exec("rm -rf ".__DIR__."/../compile/*");

require(__DIR__.'/../../vendor/autoload.php');

$smarty = new Smarty();
$smarty->compile_check = true;

$smarty->setTemplateDir(__DIR__);
$smarty->setCompileDir(__DIR__."/../compile/");

$start = microtime(true);
$smarty->assign($data);
$smarty->fetch('inheritance/smarty/b100.tpl');
var_dump("Smarty3: ".(microtime(true)-$start));

$start = microtime(true);
$smarty->assign($data);
$smarty->fetch('inheritance/smarty/b100.tpl');
var_dump("Smarty3 cached: ".(microtime(true)-$start));

Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem(__DIR__);
$twig = new Twig_Environment($loader, array(
	'cache' => __DIR__."/../compile/",
	'autoescape' => false, 
	'auto_reload' => false,
));

$start = microtime(true);
$template = $twig->loadTemplate('inheritance/twig/b100.tpl');
$template->render($data);
var_dump("Twig: ".(microtime(true)-$start));

$start = microtime(true);
$template = $twig->loadTemplate('inheritance/twig/b100.tpl');
$template->render($data);
var_dump("Twig cached: ".(microtime(true)-$start));

$aspect = Aspect::factory(__DIR__, __DIR__."/../compile/", Aspect::CHECK_MTIME);

$start = microtime(true);
$template = $aspect->fetch('inheritance/smarty/b100.tpl', $data);
var_dump("Aspect: ".(microtime(true)-$start));

$start = microtime(true);
$template = $aspect->fetch('inheritance/smarty/b100.tpl', $data);
var_dump("Aspect cached: ".(microtime(true)-$start));

