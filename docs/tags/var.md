Tag {var} [RU]
==============

Тег {var} предназначен для создания переменных в шаблонах.

```smarty
{var $var=EXPR}
```

```smarty
{var $var}
  ... any content ...
{/var}
```

```smarty
{var $var|modifiers}
  ... any content ...
{/var}
```

К названию новой переменной предъявляются те же требования, что и к [именам переменных](http://www.php.net/manual/en/language.variables.basics.php) в PHP.
Выражение EXPR подразумевает любое поддерживаемое выражение.

```smarty
{var $v = 5}
{var $v = "value"}

{var $v = $x+$y}
{var $v = 4}
{var $v = $z++ + 1}
{var $v = --$z}
{var $v = $y/$x}
{var $v = $y-$x}
{var $v = $y*$x-2}
{var $v = ($y^$x)+7}

Присваивание массивов

{var $v = [1,2,3]}
{var $v = []}
{var $v = ["one"|upper => 1, 4 => $x, "three" => 3]}
{var $v = ["key1" => $y*$x-2, "key2" => ["z" => $z]]}

Присваивание результата выполнения функции

{var $v = count([1,2,3])+7}

{var $v}
    Some long {$text|trim}
{/var}

{var $v|escape} {* apply modifier to variable*}
    Some long {$text|trim}
{/var}
```
