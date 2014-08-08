Модификатор upper
==============

Переводит строку в верхний регистр. Является эквивалентом функции PHP [strtoupper()](http://docs.php.net/ru/strtoupper).
Имеет псевдоним `up`.

```smarty
{var $name = "Bzick"}

{$name}         выводит Bzick
{$name|upper}   выводит BZICK
{$name|up}      выводит BZICK
```