<?php

require_once __DIR__.'/../../vendor/autoload.php';

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled', Fenom::FORCE_COMPILE);

$fenom->display("greeting.tpl", array(
    "user" => array(
        "name" => "Ivka"
    )
));