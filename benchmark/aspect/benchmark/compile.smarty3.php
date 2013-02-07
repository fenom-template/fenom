<?php
require_once "/data/downloads/Smarty3/libs/Smarty.class.php";

$smarty = new Smarty();

$smarty->addTemplateDir(__DIR__.'/../templates');
$smarty->setCompileDir(__DIR__.'/../compiled');

$t = microtime(1);
$tpl = $smarty->createTemplate('syntax.tpl');
/* @var Smarty_Internal_Template $tpl */
$tpl->compileTemplateSource();
$t = microtime(1) - $t;
var_dump("First compile: ".$t);

$t = microtime(1);
$tpl = $smarty->createTemplate('syntax.tpl');
/* @var Smarty_Internal_Template $tpl */
$tpl->compileTemplateSource();
$t = microtime(1) - $t;
var_dump("Second compile: ".$t);

var_dump("Pick memory: ".memory_get_peak_usage());