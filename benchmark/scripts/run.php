<?php

$opt = getopt("", array(
    "engine:",
    "template:",
    "data:",
    "double",
    "message:"
));

require_once __DIR__.'/bootstrap.php';

extract($opt);

Benchmark::$engine($template, json_decode(file_get_contents($data), true), isset($double), $message);