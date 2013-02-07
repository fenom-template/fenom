<?php
use MF\Aspect;
require_once __DIR__ . "/../../../lib/Autoload.php";

Aspect::addTemplateDir(__DIR__.'/../templates');
Aspect::setCompileDir(__DIR__.'/../compiled');
$total = 0;

$t = microtime(1);
$tpl = Aspect::compile('syntax.tpl');
$t = microtime(1) - $t;
var_dump("First compile: ".$t);


$t = microtime(1);
$tpl = Aspect::compile('syntax.tpl');
$t = microtime(1) - $t;
var_dump("Second compile: ".$t);


var_dump("Pick memory: ".memory_get_peak_usage());
?>