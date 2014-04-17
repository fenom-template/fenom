<?php

function myMod($str)
{
    return "(myMod)" . $str . "(/myMod)";
}

function myFunc($params)
{
    return "MyFunc:" . $params["name"];
}

function myBlockFunc($params, $content)
{
    return "Block:" . $params["name"] . ':' . trim($content) . ':Block';
}

function myCompiler(Fenom\Tokenizer $tokenizer, Fenom\Tag $tag)
{
    $p = $tag->tpl->parseParams($tokenizer);
    return 'echo "PHP_VERSION: ".PHP_VERSION." (for ".' . $p["name"] . '.")";';
}

function myBlockCompilerOpen(Fenom\Tokenizer $tokenizer, Fenom\Tag $scope)
{
    $p = $scope->tpl->parseParams($tokenizer);
    return 'echo "PHP_VERSION: ".PHP_VERSION." (for ".' . $p["name"] . '.")";';
}

function myBlockCompilerClose(Fenom\Tokenizer $tokenizer, Fenom\Tag $scope)
{
    return 'echo "End of compiler";';
}

function myBlockCompilerTag(Fenom\Tokenizer $tokenizer, Fenom\Tag $scope)
{
    $p = $scope->tpl->parseParams($tokenizer);
    return 'echo "Tag ".' . $p["name"] . '." of compiler";';
}