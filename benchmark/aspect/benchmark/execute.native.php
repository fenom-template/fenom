<?php
use MF\Aspect;
require_once __DIR__ . "/../../../lib/Autoload.php";


$tpl = require_once __DIR__.'/data.php';
require __DIR__.'/../templates/syntax.php';
require __DIR__.'/../templates/subdir/subtpl.php';

ob_start();
template_syntax($tpl);
$data = ob_get_clean();

$dt = microtime(1);
ob_start();
template_syntax($tpl);
$data = ob_get_clean();
$dt = microtime(1) - $dt;

$data = MF\Misc\Str::strip($data, true);
echo "$data\n====\n".md5($data)."\n=====================\nDisplay: $dt\n";
var_dump("Pick memory: ".memory_get_peak_usage());
?>