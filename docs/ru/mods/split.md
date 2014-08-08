Модификатор split
==============

Разбивает строку с помощью разделителя

```
{$string|split:$delimiter = ","}
```

Возвращает массив строк, полученных разбиением строки с использованием `$delimiter` в качестве разделителя.

```smarty
{var $fruits1 = "banana,apple,pear"|split}
$fruits1 is array ["banana", "apple", "pear"]

{var $fruits2 = "banana,apple,pear"|split:',apple,'}
$fruits2 is array ["banana", "pear"]
```