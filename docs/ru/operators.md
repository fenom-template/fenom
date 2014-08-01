Операторы
=========

### Арифметические операторы

Все же помнят арифметику?

* `-$a`     - отрицание знака, смена знака `$a`.
* `$a + $b` - сложение, сумма `$a` и `$b`.
* `$a - $b` - вычитаение, разность `$a` и `$b`.
* `$a * $b` - умножение, произведение `$a` и `$b`.
* `$a / $b` - деление, частное от деления `$a` на `$b`.
* `$a % $b` - деление по модулю, целочисленный остаток от деления `$a` на `$b`.

```smarty
{$a + $b * $c/$d - $e*5 + 1e3}
```

### Логические операторы

* `$a || $b` - логичесое ИЛИ
* `$a && $b` - лигическое И
* `!$a` - отрицание, унарный оператор
* `$a or $b` - логическое ИЛИ
* `$a and $b` - логическое И
* `$a xor $b` - xor, логическое сложение

```smarty
{if $b && $c} ... {/if}
```

### Операторы сравнения

* `$a < $b` - строгое неравество, `$a` меньше `$b`
* `$a > $b` - строгое неравество, `$a` больше `$b`
* `$a <= $b` - не строгое не равество, `$a` меньше или равно `$b`
* `$a >= $b` - не строгое не равество, `$a` больше или равно `$b`
* `$a == $b` - Равно, `$a` равно `$b`
* `$a === $b` - Тождественно равно, `$a` иденично `$b`. Отличается от равества тем что проверяет так же тип значений; если '0' == 0 — истина, то уже '0' === 0 — ложно.
* `$a !== $b` - не иденично, `$a` отличается от `$b`
* `$a != $b` - неравенство, `$a` отличается от `$b`
* `$a <> $b` - неравенство

```smarty
{if $b >= 5} ... {/if}
```

В случае, если вы сравниваете число со строкой или две строки, содержащие числа, каждая строка будет преобразована в число, и сравниваться они будут как числа.
Преобразование типов не происходит при использовании `===` или `!==` так как в этом случае кроме самих значений сравниваются еще и типы.

### Побитовые операторы

Побитовые операторы позволяют считывать и устанавливать конкретные биты целых чисел.

* `$a | $b` - битовое ИЛИ, устанавливаются те биты, которые установлены в `$a` или в `$b`.
* `$a & $b` - битовое И, устанавливаются только те биты, которые установлены и в `$a`, и в `$b`.
* `$a ^ $b` - битовое исключающее ИЛИ, устанавливаются только те биты, которые установлены либо только в `$a`, либо только в `$b`, но не в обоих одновременно.
* `~$a` - битовое отрицание, устанавливаются те биты, которые не установлены в `$a`, и наоборот.
* `$a << $b` - битовый сдвиг влево, все биты переменной `$a` сдвигаются на `$b` позиций влево (каждая позиция подразумевает "умножение на 2")
* `$a >> $b` - битовый сдвиг вправо, все биты переменной `$a` сдвигаются на `$b` позиций вправо (каждая позиция подразумевает "деление на 2")

```smarty
{if $a & 1} {var $b = 4 | $flags} {/if}
```

### Assignment Operators

* `$a = $b` - assignment
* `$a += $b` - assignment with addition
* `$a -= $b` - assignment with subtraction
* `$a *= $b` - assignment with multiplication
* `$a /= $b` - assignment with division
* `$a %= $b` - assignment with modulus
* `$a &= $b` - assignment with bitwise And
* `$a |= $b` - assignment with bitwise or
* `$a ^= $b` - assignment with bitwise xor
* `$a <<= $b` - assignment with left shift
* `$a >>= $b` - assignment with right shift


```smarty
{var $b |= $flags}
```

### Incrementing/Decrementing operators

* `++$a` - increment the variable and use it
* `$a++` - use the variable and increment it
* `--$a` - decrement the variable and use it
* `$a--` - use the variable and decrement it

### String operator

* `$a ~ $b` - return concatenation of variables `$a` and `$b`

### Ternary operators

* `$a ? $b : $c` - returns `$b` if `$a` is not empty, and `$c` otherwise
* `$a ! $b : $c` - returns `$b` if `$a` is set, and `$c` otherwise
* `$a ?: $c` - returns `$a` if `$a` is not empty, and `$c` otherwise
* `$a !: $c` - returns `$a` if `$a` is set, and `$c` otherwise

```smarty
{var $a = true}
{$a ? 5 : 10} {* outputs 5 *}
{var $a = false}
{$a ? 5 : 10} {* outputs 10 *}
```

### Check operators

* `$a?` - returns `TRUE` if `$a` is not empty
* `$a!` - returns `TRUE` if `$a` is set

```smarty
{if $a?} {* instead of {if !empty($a)} *}
{if $a!} {* instead of {if isset($a)} *}
{$a?:"some text"} {* instead of {if empty($a) ? "some text" : $a} *}
{$a!:"some text"} {* instead of {if isset($a) ? $a : "some text"} *}
```

### Test operator

Tests can be negated by using the `is not` operator.

* `$a is $b` - $a identical $b
* `$a is integer` - test variable type. Type may be int/integer, bool/boolean, float/double/decimal, array, object, scalar, string, callback/callable, number/numeric.
* `$a is iterable` - test variable for iteration.
* `$a is template` - variable `$a` contain existing template name.
* `$a is empty` - checks if a variable is empty.
* `$a is set` - checks if a variable is set.
* `$a is even` - variable `$a` is even.
* `$a is odd` - variable `$a` is odd.
* `$a is MyClass` or `$a is \MyClass` - variable `$a` instance of `MyClass` class

### Containment operator

Tests can be negated by using the `not in` operator.

* `$a in $b` - variable `$a` contains in `$b`, $b may be string, plain or assoc array.
* `$a in list $b` - variable `$a` contains in array `$b` as value
* `$a in keys $b` - array `$b` contain key `$a`
* `$a in string $b` - variable `$a` contains in string `$b` as substring

```smarty
{'df' in 'abcdefg'}
{5 in [1, 5, 25, 125]}
{99 in keys [1, 5, 25, 99 => 125]}
```