Модификатор ematch
==============

Выполняет проверку на соответствие регулярному выражению.
[Подробнее](http://www.php.net/manual/ru/reference.pcre.pattern.syntax.php) о регулярных выражениях.


```
{$string|ematch:$pattern}
```

Ищет в заданном тексте `$subject` совпадения с шаблоном `$pattern`.

```smarty
{if $color|ematch:'/^(.*?)gr[ae]y$/i'}
  какой-то оттенок серого ...
{/if}
```