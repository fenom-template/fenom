<?php
use MF\Aspect;
require_once __DIR__ . "/../../../lib/Autoload.php";

Aspect::setTemplateDir(__DIR__.'/../templates');
Aspect::setCompileDir(__DIR__.'/../compiled');

$ct = microtime(1);
$data = Aspect::compile('simple.tpl',0);
$ct = microtime(1) - $ct;

$_data = array(
    "name" => "Ivan",
   "email" => "bzick@megagroup.ru"
);

$data = Aspect::fetch('simple.tpl', $_data, 0);

$dt = microtime(1);
$data = Aspect::fetch('simple.tpl', $_data, 0);
$dt = microtime(1) - $dt;


echo "\n=====================\nCompile: $ct\nDisplay: $dt\n";
?>