Modifier |upper
===============

Modifier is used to uppercase a variable or string. Have short alias `up`
This is equivalent to the PHP [strtoupper()](http://docs.php.net/strtoupper) function.

```smarty
{var $name = "Bzick"}

{$name}         output Bzick
{$name|upper}   output BZICK
{$name|up}      output BZICK too
```