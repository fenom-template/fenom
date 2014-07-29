Modifier upper
==============

Modifier is used to uppercase a variable or string. Has short alias `up`.
This is equivalent to the PHP [strtoupper()](http://docs.php.net/strtoupper) function.

```smarty
{var $name = "Bzick"}

{$name}         outputs Bzick
{$name|upper}   outputs BZICK
{$name|up}      outputs BZICK too
```
