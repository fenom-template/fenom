<?php

function myMod($str) {
    return "(myMod)".$str."(/myMod)";
}

function myFunc($params) {
    return "MyFunc:".$params["name"];
}

function myBlockFunc($params, $content) {
    return "Block:".$params["name"].':'.trim($content).':Block';
}

function myCompiler(Cytro\Tokenizer $tokenizer, Cytro\Template $tpl) {
    $p = $tpl->parseParams($tokenizer);
    return 'echo "PHP_VERSION: ".PHP_VERSION." (for ".'.$p["name"].'.")";';
}

function myBlockCompilerOpen(Cytro\Tokenizer $tokenizer, Cytro\Scope $scope) {
    return myCompiler($tokenizer, $scope->tpl);
}

function myBlockCompilerClose(Cytro\Tokenizer $tokenizer, Cytro\Scope $scope) {
    return 'echo "End of compiler";';
}

function myBlockCompilerTag(Cytro\Tokenizer $tokenizer, Cytro\Scope $scope) {
    $p = $scope->tpl->parseParams($tokenizer);
    return 'echo "Tag ".'.$p["name"].'." of compiler";';
}