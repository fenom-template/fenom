Модификатор esplit
===============

Разбивает строку по регулярному выражению.
[Подробнее](http://www.php.net/manual/ru/reference.pcre.pattern.syntax.php) о регулярных выражениях.

```
{$string|esplit:$pattern = '/,\s*/'}
```

По умолчанию модификатор разбивает строку по запятой с возможнымиы проблеами

```smarty
{var $fruits1 = "banana, apple, pear"|esplit}
$fruits1 — массив ["banana", "apple", "pear"]

{var $fruits2 = "banana; apple; pear"|esplit:'/;\s/'} is ["banana", "apple", "pear"]
$fruits2 — массив ["banana", "apple", "pear"]
```