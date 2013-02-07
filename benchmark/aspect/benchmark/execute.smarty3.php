<?php
require_once "/data/downloads/Smarty3/libs/Smarty.class.php";
require_once __DIR__ . "/../../../lib/Autoload.php";
$smarty = new Smarty();

$smarty->addTemplateDir(__DIR__.'/../templates');
$smarty->setCompileDir(__DIR__.'/../compiled');

/*$ct = microtime(1);
$tpl = $smarty->createTemplate('syntax.tpl');
/* @var Smarty_Internal_Template $tpl */
/*$tpl->compileTemplateSource();
$ct = microtime(1) - $ct;
echo "\n=====================\nCompile: $ct\n";
*/

$_data = require_once __DIR__.'/data.php';

$smarty->assign($_data);
$data = $smarty->fetch('syntax.tpl');

$t = microtime(1);
$smarty->assign($_data);
$data = $smarty->fetch('syntax.tpl');
$dt = microtime(1) - $t;

$data = MF\Misc\Str::strip($data, true);
echo "$data\n====\n".md5($data)."\n=====================\nDisplay: $dt\n";
var_dump("Pick memory: ".memory_get_peak_usage());