<?php

require_once __DIR__."/../vendor/autoload.php";



define('ASPECT_RESOURCES', __DIR__."/resources");

require_once ASPECT_RESOURCES."/actions.php";
require_once __DIR__."/TestCase.php";

function drop() {
    call_user_func_array("var_dump", func_get_args());
    $e = new Exception();
    echo "-------\nDump trace: \n".$e->getTraceAsString()."\n";
    exit();
}