Синтаксис
=========

### Переменные

Вывод значений переменных в шаблонизаторе Aspect идентичен правилам вывода шаблонизатора Smarty

```smarty
{$foo}
{$foo[4]}
{$foo.4}
{$foo.bar}
{$foo.'bar'}
{$foo."bar"}
{$foo[bar]}
{$foo['bar']}
{$foo["bar"]}
{$foo.$bar}
{$foo[$bar]}
{$foo->bar}
{$foo->bar()}
```

Комбинированные варианты

```smarty
{$foo.bar.baz}
{$foo.$bar.$baz}
{$foo[4].baz}
{$foo[4].$baz}
{$foo.bar.baz[4]}
{$foo->bar($baz, 2, $bar)}
{"foo"}
```

### Математические операции

```smarty
{$x+$y}
{$foo[$x+3]}
{$foo[$x+3]*$x+3*$y % 3}
```

[Список всех операторов](./operators.md)

### Объявление переменных

```smarty
{var $foo = "bar"}
{var $foo = 5}
```

в качестве значения так же допускаются математические, логические операции и результаты функций

```smarty
{var $foo = $x + $y}
{var $foo = $x.y[z] + $y}
{var $foo = strlen($a)}
{var $foo = myfunct( ($x+$y)*3 )}
{var $foo.bar.baz = 1}
```

Подробнее смотрите [{var}](./tags/var.md)

#### Объявление массивов

```smarty
{var $foo=[1,2,3]}
{var $foo=['y'=>'yellow','b'=>'blue']}  can be associative
{var $foo=[1,[9,8],3]}   can be nested
```

в качестве ключа и значения так же допускаются математические, логические операции и результаты функций

```smarty
{var $foo=[$a, $d.c, $a + $f]}
{var $foo=['y'=>'yellow', $color=>$colors[ $color ]}  can be associative
{var $foo=[1,[$parent ,$a + $e],3]}   can be nested
```

### Работа с объектами

```smarty
{$object->method1($x)->method2($y)}
{var $foo=$object->item->method($y, 'named')}
```

Вызов метода в шаблоне можно запретить [настройками](./settings.md)

### Работа со скалярными значениями

Строки в Aspect обрабатываются идентично правилам подстановки переменных в строки в PHP, т.е. в двойных кавычках переменная заменяется на её значение, в одинарных замены не происходит.

```smarty
{var $foo="Username"}
{var $user.name="Username"}
{"Hi, $foo"}          выведет "Hi, Username"
{"Hi, {$foo}"}        выведет "Hi, Username"
{"Hi, {$user.name}"}  выведет "Hi, Username"
{var $message = "Hi, {$user.name}"}
{'Hi, $foo'}          выведет 'Hi, $foo'
{'Hi, {$foo}'}        выведет 'Hi, {$foo}'
```

Переменные в строках так же поддерживают [модификаторы](#modifiers)

```smarty
{"Hi, {$user.name|up}"} outputs Hi, USERNAME
```

Поддерживается вызов методов

```smarty
{"Hi, {$user->getName(true)}"} outputs Hi, Username
```

Числовые значение обрабатывается как есть

```smarty
{2|pow:10}
{var $magick = 5381|calc}
```

### Модификаторы

* Модификаторы позволяют изменить значение переменной перед выводом или использованием в выражении
* Модификаторы записываются после переменной через символ вертикальной черты "|"
* Модификаторы могут иметь параметры, которые записываются через символ двоеточие ":" после имени модификатора
* Параметры модификаторов друг от друга также разделяются символом двоеточие ":"
* В качестве параметров могут использоваться переменные.
* Модификаторы могут составлять цепочки. В этом случае они применяются к переменной последовательно слева направо

```smarty
{var $foo="User"}
{$foo|upper}     выведет "USER"
{$foo|lower}     выведет "user"
{"{$foo|lower}"}     выведет "user"
{"User"|lower}}     выведет "user"
{$looong_text|truncate:80:"..."}  обрежет текст до 80 символов и добавит "..." в конец текста
{$looong_text|lower|truncate:$settings.count:$settings.etc}
{var $foo="Ivan"|upper}    переменная $foo будет содержать "USER"
```

[Список модификаторов](./main.md#modifiers)

### Теги

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

### Игнорирование разделителя

В шаблонизаторе Aspect используются фигурные скобки для отделения HTML от кода Aspect.
Если требуется вывести текст, содержащий фигурные скобки помните о следующих возможностях:

1. Использование блочного тега `{ignore}{/ignore}`. Текст внутри этого тега текст не компилируется шаблонизатором и выводится как есть.
2. Если после открывающей фигурной скобки есть пробельный символ (пробел или `\t`) или перенос строки (`\r` или `\n`), то она не воспринимается как разделитель rкода Aspect и код после неё выводится как есть.

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

### Пробелы и переносы строк

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
] as $key => $val}

    {$key}: {$val}

{/foreach}
```