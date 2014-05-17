<?php

namespace Ts {
    class Math {
        public static function multi($x, $y) {
            return $x * $y;
        }
    }
}

namespace {
    require_once __DIR__.'/../src/Fenom.php';

    \Fenom::registerAutoload();

    $fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled', Fenom::FORCE_COMPILE);

    $fenom->display("extends/75-child.tpl", array(
        "user" => array(
            "name" => "Ivka",
            'type' => 'new'
        ),
        'type' => 'new'
    ));
}