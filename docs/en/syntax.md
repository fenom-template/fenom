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

Unnamed system variable starts with `$.` and allows access to global system variables and template information:

* `$.get` — array `$_GET`.
* `$.post` — array `$_POST`.
* `$.cookie` — array `$_COOKIE`.
* `$.session` — array `$_SESSION`.
* `$.globals` — array `$GLOBALS`.
* `$.request` — array `$_REQUEST`.
* `$.files` — array `$_FILES`.
* `$.server` — array `$_SERVER`.
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


Безименная системная переменная начинается с `$.` и предоставляет доступ к глобальным системным переменным и системной информации:

* `$.env` — array `$_ENV`.
* `$.get` — array `$_GET`.
* `$.post` — array `$_POST`.
* `$.files` — array `$_FILES`.
* `$.cookie` — array `$_COOKIE`.
* `$.server` — array `$_SERVER`.
* `$.session` — array `$_SESSION`.
* `$.globals` — array `$GLOBALS`.
* `$.request` — array `$_REQUEST`.
* `$.tpl.name` returns name for current template.
* `$.tpl.basename` returns name without schema for current template.
* `$.tpl.scm` returns schema for current template.
* `$.tpl.options` returns options as integer for current template.
* `$.tpl.depends` <!-- возвращает массив шаблонов на которые ссылается текущий шаблон.-->
* `$.tpl.time` returns last modified timestamp for current template
* `$.version` returns Fenom version.
* `$.const` returns the value of a PHP constant: `$.const.PHP_EOL` get value of constant `PHP_EOL`. 
   Supported namespace for constants, use dot instead of back-slash for namespace separators: `$.const.Storage.FS::DIR_SEPARATOR`  get value of constant `Storage\FS::DIR_SEPARATOR`.
   But if constant `Storage\FS::DIR_SEPARATOR` does not exists then constant `Storage\FS\DIR_SEPARATOR` will be taken.
* `$.php`call PHP static method. `$.php.Storage.FS::put($filename, $data)` calls method `Storage\FS::put($filename, $data)`.
  `$.php.Storage.FS.put($filename, $data)` `Storage\FS\put($filename, $data)`
* System function `$.fetch($name, $values)` calls Fenom::fetch() in template. `$name` — template name, 
  `$values` — additional variables.
* also you may [add](./ext/extend.md#Extends-system-variable) yours system variables and functions.
 

## Scalar values

### Strings

A string literal can be specified in two different ways: double quotes (`"string"`) and single quotes (`'string'`).

#### Double quotes

If the string is enclosed in double-quotes `"`, Fenom will interpret more escape sequences for special characters:


| Последовательность  | Значение |
|---------------------|----------|
| `\n`                | linefeed (LF or 0x0A (10) in ASCII)
| `\r`                | carriage return (CR or 0x0D (13) in ASCII)
| `\t`                | horizontal tab (HT or 0x09 (9) in ASCII)
| `\v`                | vertical tab (VT or 0x0B (11) in ASCII)
| `\f`                | form feed (FF or 0x0C (12) in ASCII)
| `\\`                | backslash
| `\$`                | dollar sign
| `\"`                | double-quote
| `\[0-7]{1,3}`       | the sequence of characters matching the regular expression is a character in octal notation
| `\x[0-9A-Fa-f]{1,2}`| the sequence of characters matching the regular expression is a character in hexadecimal notation

The most important feature of double-quoted strings is the fact that variable names will be expanded.
There are two types of syntax: a simple one and a complex one. The simple syntax is the most common and convenient. 
It provides a way to embed a variable, an array value, or an object property in a string with a minimum of effort.
The complex syntax can be recognised by the curly braces surrounding the expression.

##### Simple syntax

If a dollar sign `$` is encountered, the parser will greedily take as many tokens as possible to form a valid variable name. 
Enclose the variable name in curly braces to explicitly specify the end of the name.

```smarty
{"Hi, $username!"}   outputs "Hi, Username!"
```

For anything more complex, you should use the complex syntax.

##### Complex syntax

This isn't called complex because the syntax is complex, but because it allows for the use of complex expressions.
Any scalar variable, array element or object property with a string representation can be included via this syntax. 
Simply write the expression the same way as it would appear outside the string, and then wrap it in `{` and `}`. 
Since `{` can not be escaped, this syntax will only be recognised when the `$` immediately follows the `{`. 
Use `{\$` to get a literal `{$`. Some examples to make it clear:


```smarty
{"Hi, {$user.name}!"}        outputs: Hi, Username!
{"Hi, {$user->name}!"}       outputs: Hi, Username!
{"Hi, {$user->getName()}!"}  outputs: Hi, Username!
{"Hi, {\$user->name}!"}      outputs: Hi, {\$user->name}!
```

Allows modifiers and operators:

```smarty
{"Hi, {$user.name|up}!"}                  outputs: Hi, USERNAME!
{"Hi, {$user.name|up ~ " (admin)"}!"}     outputs: Hi, USERNAME (admin)!
```

#### Single quotes

The simplest way to specify a string is to enclose it in single quotes (the character `'`).
To specify a literal single quote, escape it with a backslash (`\`). 
To specify a literal backslash, double it (`\\`). 
All other instances of backslash will be treated as a literal backslash: this means that the other escape sequences you might be used to, such as `\r` or `\n`, will be output literally as specified rather than having any special meaning.

```smarty
{'Hi, $foo'}            outputs: 'Hi, $foo'
{'Hi, {$foo}'}          outputs: 'Hi, {$foo}'
{'Hi, {$user.name}'}    outputs: 'Hi, {$user.name}'
{'Hi, {$user.name|up}'} outputs: "Hi, {$user.name|up}"
```

### Integers

Integers can be specified in decimal (base 10), hexadecimal (base 16), octal (base 8) or binary (base 2) notation, optionally preceded by a sign (- or +).

To use octal notation, precede the number with a 0 (zero). 
To use hexadecimal notation precede the number with 0x. 
To use binary notation precede the number with 0b.

```smarty
{var $a = 1234}  decimal number
{var $a = -123}  a negative number
{var $a = 0123}  octal number (equivalent to 83 decimal)
{var $a = 0x1A}  hexadecimal number (equivalent to 26 decimal)
{var $a = 0b11111111}  binary number (equivalent to 255 decimal)
```

**Notice**
Binary notation (`0b1011011`) unavailable on PHP older than 5.3.

**Notice**
The size of an integer is platform-dependent, although a maximum value of about two billion is the usual value (that's 32 bits signed). 
64-bit platforms usually have a maximum value of about 9E18

**Warning** 
If an invalid digit is given in an octal integer (i.e. 8 or 9), the rest of the number is ignored.

### Floating point numbers

Floating point numbers (also known as "floats", "doubles", or "real numbers") can be specified using any of the following syntaxes:

```smarty
{var $a = 1.234}
{var $b = 1.2e3}
{var $c = 7E-10}
```

### Booleans

This is the simplest type. A boolean expresses a truth value. It can be either TRUE or FALSE.
To specify a boolean literal, use the constants TRUE or FALSE. Both are case-insensitive.


```smarty
{set $a = true}
```

### NULL

The special NULL value represents a variable with no value. NULL is the only possible value of type null.

------


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

### Operators

Fenom supports operators on values:

* Arithmetic operators — `+`, `-`, `*`, `/`, `%`
* Logical operators — `||`, `&&`, `!$var`, `and`, `or`, `xor`
* Comparison operators — `>`, `>=`, `<`, `<=`, `==`, `!=`, `!==`, `<>`
* Bitwise operators — `|`, `&`, `^`, `~$var`, `>>`, `<<`
* Assignment operators — `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `&=`, `|=`, `^=`, `>>=`, `<<=`
* String concatenation operators — `$str1 ~ $str2`, `$str1 ~~ $str2`, `$str1 ~= $str2`
* Ternary operators — `$a ? $b : $c`, `$a ! $b : $c`, `$a ?: $c`, `$a !: $c`
* Check operators — `$var?`, `$var!`
* Test operator — `is`, `is not`
* Containment operator — `in`, `not in`

About [operators](./operators.md).

## Arrays

An array can be created using the `[]` language construct. It takes any number of comma-separated `key => value` pairs as arguments.
```
[
    key  => value,
    key2 => value2,
    key3 => value3,
    ...
]
```
The comma after the last array element is optional and can be omitted. 
This is usually done for single-line arrays, i.e. `[1, 2]` is preferred over `[1, 2, ]`. 
For multi-line arrays on the other hand the trailing comma is commonly used, as it allows easier addition of new elements at the end.

```smarty
{set $array = [
    "foo" => "bar",
    "bar" => "foo",
]}
```

The key can either be an integer or a string. The value can be of any type.

Additionally the following key casts will occur:

* Strings containing valid integers will be cast to the integer type. E.g. the key "8" will actually be stored under 8. 
  On the other hand "08" will not be cast, as it isn't a valid decimal integer.
* Floats are also cast to integers, which means that the fractional part will be truncated. 
  E.g. the key 8.7 will actually be stored under 8.
* Bools are cast to integers, too, i.e. the key true will actually be stored under 1 and the key false under 0.
* Null will be cast to the empty string, i.e. the key null will actually be stored under "".
* Arrays and objects can not be used as keys. Doing so will result in a warning: Illegal offset type.

If multiple elements in the array declaration use the same key, only the last one will be used as all others are overwritten.

An existing array can be modified by explicitly setting values in it.
This is done by assigning values to the array, specifying the key after dot or in brackets. 
The key can also be omitted, resulting in an empty pair of brackets (`[]`).

```smarty
{set $arr.key = value}
{set $arr[] = value} {* append value to end of array *}
```

If `$arr` doesn't exist yet, it will be created, so this is also an alternative way to create an array. 
This practice is however discouraged because if `$arr` already contains some value (e.g. string from request variable) 
then this value will stay in the place and `[]` may actually stand for string access operator. 
It is always better to initialize variable by a direct assignment.

## Constants

A constant is an identifier (name) for a simple value in PHP. 
As the name suggests, that value cannot change during the execution of the script. 
A constant is case-sensitive by default. By convention, constant identifiers are always uppercase.

## PHP functions and methods

**TODO**

```smarty
{$.php.some_function($a, $b, $c)}
```

```smarty
{$.php.MyClass::method($a, $b, $c)}
```


```smarty
{$.php.My.NS.some_function($a, $b, $c)}
{$.php.My.NS.MyClass::method($a, $b, $c)}
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

**TODO**

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

**TODO**

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
