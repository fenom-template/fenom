<?php

require(__DIR__ . "/../src/Fenom.php");
Fenom::registerAutoload();

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

function dumpt()
{
    foreach (func_get_args() as $arg) {
        fwrite(STDERR, "DUMP: " . call_user_func("print_r", $arg, true) . "\n");

    }
    $e = new Exception();
    echo "-------\nDump trace: \n" . $e->getTraceAsString() . "\n";
}

if(PHP_VERSION_ID > 50400) {
    function php_gte_54() {}
}