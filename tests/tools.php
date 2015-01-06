<?php

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