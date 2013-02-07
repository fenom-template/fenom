<?php
require_once "/data/downloads/Smarty3/libs/Smarty.class.php";

$smarty = new Smarty();

$smarty->addTemplateDir(__DIR__.'/../templates');
$smarty->setCompileDir(__DIR__.'/../compiled');

$ct = microtime(1);
$tpl = $smarty->createTemplate('simple.tpl');
/* @var Smarty_Internal_Template $tpl */
$tpl->compileTemplateSource();
$ct = microtime(1) - $ct;

$_data = array(
    "name" => "Ivan",
    "email" => "bzick@megagroup.ru"
);

$smarty->assign($_data);
$data = $smarty->fetch('simple.tpl');

$t = microtime(1);
$smarty->assign($_data);
$data = $smarty->fetch('simple.tpl');
$dt = microtime(1) - $t;


echo "\n=====================\nCompile: $ct\nDisplay: $dt\n";