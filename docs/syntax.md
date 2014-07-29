Syntax
======

Fenom implements [Smarty](http://www.smarty.net/) syntax with some improvements.
All Fenom tags enclosed in the delimiters `{` and `}`, for example `{var $five = 5}`.
If you wanna leave delimiters as is in the template use [special statements or tags](#ignoring-delimiters).

**Note**
Fenom implements [Smarty](http://www.smarty.net/) syntax but not implements Smarty tags, however, some tags very similar.
But not so bad, Fenom has the [extras](https://github.com/bzick/fenom-extra) that make Fenom like Smarty.

## Variable

Variables in Fenom can be either displayed directly or used as arguments for functions, attributes and modifiers,
inside conditional expressions, etc.

### Use variables

Next example uses simple variables `$user_id` ans `$user_name`
```smarty
<div class="user">Hello, <a href="/users/{$user_id}">{$user_name}</a>.</div>
```

Example outputs next HTML code:
```html
<div class="user">Hello, <a href="/users/17">Bzick</a>.</div>
```

You can also reference associative array variables by specifying the key after a dot `.` symbol or paste key name into square brackets, as in PHP.
```smarty
<div class="user">Hello, <a href="/users/{$user.id}">{$user.name}</a>.</div>
```
`{$user.id}` and `{$user['id']}` are same:
```smarty
<div class="user">Hello, <a href="/users/{$user['id']}">{$user['name']}</a>.</div>
```

Properties of objects assigned from PHP can be referenced by specifying the property name after the `->` symbol:
```smarty
<div class="user">Hello, <a href="/users/{$user->id}">{$user->name}</a>.</div>
```

Methods of objects defined in PHP can be invoked by specifying the method name after the `->` symbol and use parenthesis with arguments:
```smarty
<div class="user">Hello, <a href="/users/{$user->getId()}">{$user->getName()}</a>.</div>
```

*Note*
Be careful, Fenom do not checks existence of the method before invoke.
To avoid the problem class of the object have to define method `__call`, which throws an exception, etc.
Also you can prohibit method call in [settings](./docs/configuration.md).

Below is complex example:

```smarty
{$foo.bar.baz}
{$foo.$bar.$baz}
{$foo[5].baz}
{$foo[5].$baz}
{$foo.bar.baz[4]}
{$foo[ $bar.baz ]}
{$foo[5]}
{$foo.5}
{$foo.bar}
{$foo.'bar'}
{$foo."bar"}
{$foo['bar']}
{$foo["bar"]}
{$foo.$bar}
{$foo[$bar]}
{$foo->bar}
{$foo->bar.buz}
{$foo->bar.buz[ $bar->getId("user") ]}
{$foo->bar(5)->buz(5.5)}
```

### System variable

Unnamed system variable starts with `$.` and allows access to global variables and template information:

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

### Variable operations

Fenom supports math, logic, comparison, containment, test, concatenation operators...

todo

See all [operators](./operators.md)


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
The result of expressions will be inserted into the string instead it.

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

### Integers

Integers can be specified in decimal (base 10), hexadecimal (base 16), octal (base 8) or binary (base 2) notation, optionally preceded by a sign (- or +).

To use octal notation, precede the number with a 0 (zero). To use hexadecimal notation precede the number with 0x.
To use binary notation precede the number with 0b.

```smarty
{var $a = 1234} decimal number
{var $a = -123} a negative number
{var $a = 0123} octal number (equivalent to 83 decimal)
{var $a = 0x1A} hexadecimal number (equivalent to 26 decimal)
{var $a = 0b11111111} binary number (equivalent to 255 decimal)
```

**Note**
The size of an integer is platform-dependent, although a maximum value of about two billion is the usual value (that's 32 bits signed).
64-bit platforms usually have a maximum value of about 9223372036854775807

**Warning**
If an invalid digit is given in an octal integer (i.e. 8 or 9), the rest of the number is ignored.

### Floating point numbers

Floating point numbers (also known as "floats", "doubles", or "real numbers") can be specified using any of the following syntaxes:

```smarty
{var $a = 1.234}
{var $b = 1.2e3}
{var $c = 7E-10}
```

## Modifiers


Variable modifiers can be applied to variables, custom functions or strings.
To apply a modifier, specify the value followed by a | (pipe) and the modifier name.
A modifier may accept additional parameters that affect its behavior.
These parameters follow the modifier name and are separated by a : (colon).

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

## Tags

Basically, tag seems like

```smarty
{FUNCNAME attr1 = "val1" attr2 = $val2}
```

Tags starts with name and may have attributes

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

## Static method support

By default static methods are allowed in templates

```smarty
{Lib\Math::multiple x=3 y=4} static method as tag
{Lib\Math::multiple(3,4)}  inline static method
{12 + Lib\Math::multiple(3,4)}
{12 + 3|Lib\Math::multiple:4}  static method as modifier
```

You may disable call static methods in template, see in [security options](./settings.md) option `deny_static`


## Ignoring template code

It is sometimes desirable or even necessary to have ignore sections it would otherwise parse.
A classic example is embedding Javascript or CSS code in a template.
The problem arises as those languages use the `{` and `}` characters which are also the default delimiters for Fenom.
Fenom has several solutions:

1. Uses block tag `{ignore} {/ignore}`. Anything within `{ignore} {/ignore}` tags is not interpreted, but displayed as-is.
2. The `{` and `}` braces will be ignored so long as they are surrounded by white space.
3. Uses tag option `:ignore` for block tag. Все Fenom теги внутри блока будут проигнорированны

Example:

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
{if:ignore $cdn.yandex}
    var item = {cdn: "//yandex.st/"};
{/if}
</script>
```

Outputs

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
	var item = {cdn: "//yandex.st/"};
</script>
```

### Whitespaces

Tags to allow any number of spaces

```smarty
{include 'control.tpl'
    $options = $list
    $name    = $cp.name
    $type    = 'select'
    isolate  = true
    disable_static = true
}

{foreach [
    "one"   => 1,
    "two"   => 2,
    "three" => 3
] as $key   => $val}

    {$key}: {$val}

{/foreach}
```

### Tag options

TODO

|    name | code | type  | description  |
| ------- | ---- | ----- | ------------ |
|   strip |    s | block | enable `strip` option for a block of the template |
|     raw |    a | any   | ignore escape option |
|  escape |    e | any   | force escape |
|  ignore |    i | block | ignore Fenom syntax |

```smarty
{script:ignore} ... {/script}
{foreach:ignore:strip ...} ... {/foreach}
```
