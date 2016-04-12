Tag {macro}
===========

Macros are comparable with functions in regular programming languages.
They are useful to put often used HTML idioms into reusable elements to not repeat yourself.

### {macro}

Macros can be defined in any template using tag `{macro}`.

```smarty
{macro plus($x, $y, $z=0)}
    x + y + z = {$x + $y + $z}
{/macro}
```

```smarty
{macro.plus x=$num y=100}
```

```smarty
{macro plus($x, $y, $z=0)}
    ...
    {macro.plus x=2 y=$y}
    ...
{/macro}
```

### {import}

Macros can be defined in any template, and need to be "imported" before being used.
The above import call imports the "math.tpl" file (which can contain only macros, or a template and some macros),
and import the functions as items of the `macro` namespace.

```smarty
{import 'math.tpl'}

{macro.plus x=1 y=3}
```

Use another namespace instead of `macro`

```smarty
{import 'math.tpl' as math}
...
{math.plus x=5 y=100}
```

```smarty
{import [plus, minus, exp] from 'math.tpl' as math}
```