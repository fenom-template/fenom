<?php

require_once __DIR__.'/../../vendor/autoload.php';

$t = new Fenom\Tokenizer('some "asd {$$ddd} dfg" some');

var_dump($t->tokens);