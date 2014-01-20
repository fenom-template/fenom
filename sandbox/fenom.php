<?php

require_once __DIR__.'/../vendor/autoload.php';

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled', 0);

$fenom->display("../templates/../fenom.php", array(
    "user" => array(
        "name" => "Ivka",
        'type' => 'new'
    ),
    'type' => 'new'
));