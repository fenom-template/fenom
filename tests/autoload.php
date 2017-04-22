<?php

require(__DIR__ . "/../src/Fenom.php");
Fenom::registerAutoload();

if(!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

define('FENOM_RESOURCES', __DIR__ . "/resources");

require_once FENOM_RESOURCES . "/actions.php";
require_once __DIR__ . "/TestCase.php";
require_once __DIR__ . "/tools.php";

ini_set('date.timezone', 'Europe/Moscow');

