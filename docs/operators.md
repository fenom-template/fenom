Operators
=========

### Math

Operators: `+ - / *`

```smarty
{$a + $b * $c/$d - $e*5 + 1e3}
```

### Boolean

Operators:  `|| && and or < > <= >= == === !== !=`

```smarty
{if $a && $b >= 5 && $c != 3} {/if}
```

### Bitwize

Operators: `| & << >> |= &= <<= >>=`

```smarty
{if $a & 1} {var $b |= $flags} {/if}
```

### Unary

Operators:  `^ ~ - !`

```smarty
{var $b |= $flags & ^$c}
```

### Ternar

Operators: `? :`

```smarty
{var $a = true}
{$a ? 5 : 10} {* outputs 5 *}
{var $a = false}
{$a ? 5 : 10} {* outputs 10 *}
```

### Variable operator

Checking variable value
```smarty
{if $a?} {* instead of {if !empty($a)} *}
```

Checking variable existence
```smarty
{if $a!} {* instead of {if isset($a)} *}
```

Get default if variable is empty
```smarty
{$a?:"some text"} {* instead of {if empty($a) ? "some text" : $a} *}
```

Get default if variable doesn't exist
```smarty
{$a!:"some text"} {* instead of {if isset($a) ? $a : "some text"} *}
```
