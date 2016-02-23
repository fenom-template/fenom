Modifier lower
==============

Modifier is used to lowercase a variable or string. Have short alias `low`
This is equivalent to the PHP [strtolower()](http://docs.php.net/lower) function.

```smarty
{var $name = "Bzick"}

{$name}         output Bzick
{$name|lower}   output bzick
{$name|low}      output bzick too
```
