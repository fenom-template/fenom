<?php
use MF\Aspect;
require_once __DIR__ . "/../../../lib/Autoload.php";

Aspect::addTemplateDir(__DIR__.'/../templates');
Aspect::setCompileDir(__DIR__.'/../compiled');

/*$ct = microtime(1);
$data = Aspect::compile('syntax.tpl',0);
$ct = microtime(1) - $ct;
echo "\n=====================\nCompile: $ct\n";*/

$_data = require_once __DIR__.'/data.php';

$data = Aspect::fetch('syntax.tpl', $_data, 0);


$dt = microtime(1);
$data = Aspect::fetch('syntax.tpl', $_data, 0);
$dt = microtime(1) - $dt;

$data = MF\Misc\Str::strip($data, true);
echo "$data\n====\n".md5($data)."\n=====================\nDisplay: $dt\n";
var_dump("Pick memory: ".memory_get_peak_usage());
?>