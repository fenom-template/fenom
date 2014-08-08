Модификатор lower
==============

Переводит строку в нижний регистр. Является эквивалентом функции PHP [strtolower()](http://docs.php.net/ru/lower).
Имеет псевданим `low`.

```smarty
{set $name = "Bzick"}

{$name}         выведет Bzick
{$name|upper}   выведет bzick
{$name|up}      выведет bzick
```