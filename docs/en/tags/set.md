Tag {set}
=========

The tag {set} is used for assigning template variables during the execution of a template.

```smarty
{set $var=EXPR}
```

```smarty
{set $var}
  ... any content ...
{/set}
```

```smarty
{set $var|modifiers}
  ... any content ...
{/set}
```

Variable names follow the same rules as other labels in PHP. 
A valid variable name starts with a letter or underscore, followed by any number of letters, numbers, or underscores.

```smarty
{set $v = 5}
{set $v = "value"}

{set $v = $x+$y}
{set $v = 4}
{set $v = $z++ + 1}
{set $v = --$z}
{set $v = $y/$x}
{set $v = $y-$x}
{set $v = $y*$x-2}
{set $v = ($y^$x)+7}
```

Works this array too

```smarty
{set $v = [1,2,3]}
{set $v = []}
{set $v = ["one"|upper => 1, 4 => $x, "three" => 3]}
{set $v = ["key1" => $y*$x-2, "key2" => ["z" => $z]]}
```

Getting function result into variable

```smarty
{set $v = count([1,2,3])+7}
```

Fetch the output of the template into variable

```smarty
{set $v}
    Some long {$text|trim}
{/set}

{set $v|escape} {* apply modifier to variable*}
    Some long {$text|trim}
{/set}
```

### {add}

The tag {add} the same tag as {set} except that sets the value of the variable if it does not exist.

```smarty
{add $var = 'value'}
```

instead of

```smarty
{if $var is not set}
    {set $var = 'value'}
{/if}
```

### {var}

Old name of tag {set}. Currently tag {var} the same tag as {set}.