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

The tag {add} is similar to {set}, but it only sets the variable if the variable is not already set or is empty.

```smarty
{add $var = 'value'}
```

This is equivalent to:
```smarty
{if !$var}
    {set $var = 'value'}
{/if}
```

The {add} tag also supports block content:

```smarty
{add $var}
    This content will be assigned to $var only if $var is not set
{/add}
```

Or with modifiers:
```smarty
{add $var|trim}
    Content with extra whitespace
{/add}
```

### {var}

Old name of tag {set}. Currently tag {var} the same tag as {set}.