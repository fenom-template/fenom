Модификатор split
==============

Объединяет элементы массива в строку.

```
{$array|join:$delimiter = ","}
```

Объединяет элементы массива с помощью строки `$delimiter`.

```smarty
{var $fruits1 = ["banana", "apple", "pear"]}
{$fruits1|join} выведет banana, apple, pear
{$fruits1|join:" is not "} выведет banana is not apple is not pear
```