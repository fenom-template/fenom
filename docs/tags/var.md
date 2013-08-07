Tag {var}
=========

The tag {var} is used for assigning template variables during the execution of a template.

```smarty
{var $var=EXPR}
```

```smarty
{var $var}
  ... any content ...
{/var}
```

```smarty
{var $var|modifiers}
  ... any content ...
{/var}
```

Variable names follow the same rules as other labels in PHP. 
A valid variable name starts with a letter or underscore, followed by any number of letters, numbers, or underscores.

```smarty
{var $v = 5}
{var $v = "value"}

{var $v = $x+$y}
{var $v = 4}
{var $v = $z++ + 1}
{var $v = --$z}
{var $v = $y/$x}
{var $v = $y-$x}
{var $v = $y*$x-2}
{var $v = ($y^$x)+7}
```

Creating array

```smarty
{var $v = [1,2,3]}
{var $v = []}
{var $v = ["one"|upper => 1, 4 => $x, "three" => 3]}
{var $v = ["key1" => $y*$x-2, "key2" => ["z" => $z]]}
```

Getting function result into variable

```smarty
{var $v = count([1,2,3])+7}
```

Collect the output of the template into a variable 

```smarty
{var $v}
    Some long {$text|trim}
{/var}

{var $v|escape} {* apply modifier to variable*}
    Some long {$text|trim}
{/var}
```
