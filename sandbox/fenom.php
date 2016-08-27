<?php

require_once __DIR__.'/../src/Fenom.php';
require_once __DIR__.'/../tests/tools.php';

\Fenom::registerAutoload();

$vars = [
    [
        "id" => 1,
        "name" => "Блаблабла",
        "hidden_url" => "/"
    ],
    [
        "id" => 2,
        "name" => "Каталог",
        "hidden_url" => "/catalog"
    ],
    [
        "id" => 3,
        "name" => "Сыромолочная группа",
        "hidden_url" => "/catalog/cat_1.html"
    ],
    [
        "id" => 4,
        "name" => "Сыры",
        "hidden_url" => "/catalog/cat_2.html"
    ],
];

$fenom = Fenom::factory(__DIR__.'/templates', __DIR__.'/compiled');
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::FORCE_VERIFY);
//var_dump($fenom->compile("nested.tpl", [])->getTemplateCode());
//exit;
var_dump($fenom->fetch('bug249/bread.tpl', ["arr" => $vars]));
//var_dump($fenom->compile('bug249/bread.tpl', false)->getBody());
//var_dump($fenom->compile("bug158/main.tpl", [])->getTemplateCode());
//var_dump($fenom->display("bug158/main.tpl", []));
// $fenom->getTemplate("problem.tpl");

/*
 *
 * Array
(
    [0] => Array
        (
            [id] => 1
            [name] => Блаблабла
            [hidden_url] => /
        )
    [1] => Array
        (
            [id] => 2
            [name] => Каталог
            [hidden_url] => /catalog/
        )
    [2] => Array
        (
            [orig_id] => 1
            [hidden_url] => /catalog/cat_1.html
            [name] => Сыромолочная группа
        )
    [3] => Array
        (
            [orig_id] => 2
            [hidden_url] => /catalog/cat_2.html
            [name] => Сыры
        )
    [4] => Array
        (
            [orig_id] => 6
            [hidden_url] => /catalog/cat_6.html
            [name] => Сыр плавленый
        )
)

 */