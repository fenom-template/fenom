Теги
====

Теги делятся на компилеры и функции.
Компилеры формируют синтаксис языка шаблона, добавляя такой функционал как foreach, if, while и т.д. В то время как функции - обычный вызов некоторой именованной функции

Добавить компилер:

```php
$aspect->addCompiler($compiler, $parser);
```

* `$compiler` - имя модификатора
* `$parser`  - функция разбора тега в формате function (MF\Tokenizer $tokens, MF\Aspect\Template $tpl) {}

Добавить блочный компилер:

```php
$aspect->addBlockCompiler($compiler, $parsers, $tags);
```