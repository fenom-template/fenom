Operators
=========

### Arithmetic operators

* `$a + $b` - addition
* `$a - $b` - subtraction
* `$a * $b` - multiplication
* `$a / $b` - division
* `$a % $b` - modulus

```smarty
{$a + $b * $c/$d - $e*5 + 1e3}
```

### Logical operators

* `$a || $b` - or
* `$a && $b` - and
* `!$a` - not, unary operator
* `$a and $b` - and
* `$a or $b` - or
* `$a xor $b` - xor

```smarty
{if $b && $c} ... {/if}
```

### Comparison operators

* `$a < $b` - less than
* `$a > $b` - greater than
* `$a <= $b` - less than or equal to
* `$a >= $b` - greater than or equal to
* `$a == $b` - equal
* `$a === $b` - identical
* `$a !== $b` - not identical
* `$a != $b` - not equal
* `$a <> $b` - not equal

```smarty
{if $b >= 5} ... {/if}
```

### Bitwise operators

* `$a | $b` - or
* `$a & $b` - and
* `$a ^ $b` - xor
* `~$a` - not, unary operator
* `$a << $b` - shift left
* `$a >> $b` - shift right

```smarty
{if $a & 1} {var $b = 4 | $flags} {/if}
```

### Assignment operators

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

### String operators

* `$a ~ $b`  - return concatenation of variables `$a` and `$b`
* `$a ~~ $b` - return concatenation of variables `$a` and `$b` separated by a space
* `$a ~= $b` - assignment with concatenation

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