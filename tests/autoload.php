<?php

$loader = include(__DIR__ . "/../vendor/autoload.php");
/* @var Composer\Autoload\ClassLoader $loader */
$loader->add('Fenom', __DIR__.'/cases');


define('FENOM_RESOURCES', __DIR__ . "/resources");

require_once FENOM_RESOURCES . "/actions.php";
require_once __DIR__ . "/TestCase.php";

ini_set('date.timezone', 'Europe/Moscow');

function drop()
{
    call_user_func_array("var_dump", func_get_args());
    $e = new Exception();
    echo "-------\nDump trace: \n" . $e->getTraceAsString() . "\n";
    exit();
}

function dump()
{
    foreach (func_get_args() as $arg) {
        fwrite(STDERR, "DUMP: " . call_user_func("print_r", $arg, true) . "\n");

    }
}