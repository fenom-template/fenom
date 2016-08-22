<?php

require(__DIR__ . "/../src/Fenom.php");
Fenom::registerAutoload();

define('FENOM_RESOURCES', __DIR__ . "/resources");

require_once FENOM_RESOURCES . "/actions.php";
require_once __DIR__ . "/TestCase.php";
require_once __DIR__ . "/tools.php";

ini_set('date.timezone', 'Europe/Moscow');