Syntax [RU]
===========

Fenom implement [Smarty](http://www.smarty.net/) syntax with some improvements

## Variable

### Use variables

```smarty
{$foo}
{$bar}
{$foo[4]}
{$foo.4}
{$foo.bar}
{$foo.'bar'}
{$foo."bar"}
{$foo['bar']}
{$foo["bar"]}
{$foo.$bar}
{$foo[$bar]}
{$foo->bar}
{$foo->bar.buz}
```

### System variable

Unnamed system variable starts with `$.` and allow access to global variables and system info:

* `$.get` is `$_GET`.
* `$.post` is `$_POST`.
* `$.cookie` is `$_COOKIE`.
* `$.session` is `$_SESSION`.
* `$.globals` is `$GLOBALS`.
* `$.request` is `$_REQUEST`.
* `$.files` is `$_FILES`.
* `$.server` is `$_SERVER`.
* `$.env` is `$_ENV`.
* `$.tpl.name` returns current template name.
* `$.tpl.schema` returns current schema of the template.
* `$.version` returns version of the Fenom.
* `$.const` paste constant.

```smarty
{if $.get.debug? && $.const.DEBUG}
   ...
{/if}
```

### Multidimensional value support

```smarty
{$foo.bar.baz}
{$foo.$bar.$baz}
{$foo[4].baz}
{$foo[4].$baz}
{$foo.bar.baz[4]}
{$foo[ $bar.baz ]}
```

### Math operations

```smarty
{$x+$y}
{$foo[$x+3]}
{$foo[$x+3]*$x+3*$y % 3}
```

See all [operators](./operators.md)

### Object support

```smarty
{$object->item}
{$object->item|upper} {* apply modifier *}
{$object->item->method($y, 'named')}
{$object->item->method($y->name, 'named')|upper} {* apply modifier to method result*}
```

You may disable call methods in template, see [security options](./settings.md)


### Set variable

```smarty
{var $foo = "bar"}
{var $foo = "bar"|upper} {* apply modifier *}
{var $foo = 5}
{var $foo = $x + $y}
{var $foo = $x.y[z] + $y}
{var $foo = strlen($a)} {* work with functions *}
{var $foo = myfunct( ($x+$y)*3 )}
{var $foo.bar.baz = 1} {* multidimensional value support *}
{var $foo = $object->item->method($y, 'named')} {* work with object fine *}
```

Using block tag

```smarty
{var $foo}
    content {$text|truncate:30}
{/var}
{var $foo|truncate:50} {* apply modifier to content *}
    content {$text}
{/var}
```

Set array

```smarty
{var $foo = [1, 2, 3]} numeric array
{var $foo = ['y' => 'yellow', 'b' => 'blue']} associative array
{var $foo = [1, [9, 8], 3]} can be nested
{var $foo = [1, $two, $three * 3 + 9]}
{var $foo = [$a, $d.c, $a + $f]}
{var $foo = ['y' => 'yellow', $color|upper => $colors[ $color ]}
{var $foo = [1, [$parent, $a->method()], 3]}
```

See also [{var}](./tags/var.md) documentation.


## Scalar values

### Strings

When the string in double quotation marks, all the expressions in the string will be run.
The result of the expression will be inserted into the string instead it.

```smarty
{var $foo="Username"}
{var $user.name="Username"}
{"Hi, $foo"}          outputs "Hi, Username"
{"Hi, {$foo}"}        outputs "Hi, Username"
{"Hi, {$user.name}"}  outputs "Hi, Username"
{"Hi, {$user.name|up}"} outputs "Hi, USERNAME"
{"Hi, {$user->getName(true)}"} outputs Hi, Username
{var $message = "Hi, {$user.name}"}
```

but if use single quote any template expressions will be on display as it is

```smarty
{'Hi, $foo'}            outputs 'Hi, $foo'
{'Hi, {$foo}'}          outputs 'Hi, {$foo}'
{'Hi, {$user.name}'}    outputs 'Hi, {$user.name}'
{'Hi, {$user.name|up}'} outputs "Hi, {$user.name|up}"
```

## Numbers

```smarty
{2|pow:10}
{var $magick = 5381|calc}
{0.2|round}
{1e-6|round}
```

### Modifiers

* Модификаторы позволяют изменить значение переменной перед выводом или использованием в выражении
* To apply a modifier, specify the value followed by a | (pipe) and the modifier name.
* A modifier may accept additional parameters that affect its behavior. These parameters follow the modifier name and are separated by a : (colon).

```smarty
{var $foo="User"}
{$foo|upper}            outputs "USER"
{$foo|lower}            outputs "user"
{"{$foo|lower}"}        outputs "user"
{"User"|lower}}         outputs "user"
{$looong_text|truncate:80:"..."}  truncate the text to 80 symbols and append <continue> symbols, like "..."
{$looong_text|lower|truncate:$settings.count:$settings.etc}
{var $foo="Ivan"|upper}    sets $foo value "USER"
```

[List of modifiers](./main.md#modifiers)

### Tags

Каждый тэг шаблонизатора либо выводит переменную, либо вызывает какую-либо функцию. (переписать)
Тег вызова функции начинается с названия функции и содержит список аргументов:

```smarty
{FUNCNAME attr1 = "val1" attr2 = $val2}
```

Это общий формат функций, но могут быть исключения, например функция [{var}](./tags/var.md), использованная выше.

```smarty
{include file="my.tpl"}
{var $foo=5}
{if $user.loggined}
    Welcome, <span style="color: red">{$user.name}!</span>
{else}
    Who are you?
{/if}
```

В общем случае аргументы принимают любой формат переменных, в том числе результаты арифметических операций и модификаторов.

```smarty
{funct arg=true}
{funct arg=5}
{funct arg=1.2}
{funct arg='string'}
{funct arg="string this {$var}"}
{funct arg=[1,2,34]}
{funct arg=$x}
{funct arg=$x.c}
```

```smarty
{funct arg="ivan"|upper}
{funct arg=$a.d.c|lower}
```

```smarty
{funct arg=1+2}
{funct arg=$a.d.c+4}
{funct arg=($a.d.c|count+4)/3}
```

### Ignoring template code

В шаблонизаторе Fenom используются фигурные скобки для отделения HTML от кода Fenom.
Если требуется вывести текст, содержащий фигурные скобки, помните о следующих возможностях:

1. Использование блочного тега `{ignore}{/ignore}`. Текст внутри этого тега текст не компилируется шаблонизатором и выводится как есть.
2. Если после открывающей фигурной скобки есть пробельный символ (пробел или `\t`) или перенос строки (`\r` или `\n`), то она не воспринимается как разделитель кода Fenom и код после неё выводится как есть.

Пример:

```smarty
{ignore}
<style>
	h1 {font-size: 24px; color: #F00;}
</style>
{/ignore}
<script>
	(function (text) {
		var e = document.createElement('P');
		e.innerHTML = text;
		document.body.appendChild(e);
	})('test');
</ignore>
```

Выведет

```html
<style>
	h1 {font-size: 24px; color: #F00;}
</style>
<script>
	(function (text) {
		var e = document.createElement('P');
		e.innerHTML = text;
		document.body.appendChild(e);
	})('test');
</script>
```

### Whitespaces

Шаблонизатор допускает любое количество пробелов или переносов строк в своём коде

```smarty
{include 'control.tpl'
    options = $list
    name    = $cp.name
    type    = 'select'
}

{foreach [
    "one"   => 1,
    "two"   => 2,
    "three" => 3
] as $key   => $val}

    {$key}: {$val}

{/foreach}
```