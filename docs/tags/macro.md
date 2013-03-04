Tag {macro}
============

Declare macro

```smarty
{macro plus(x, y, z=0)}
    x + y + z = {$x + $y + $z}
{/macro}
```

Invoke macro

```smarty
{macro.plus x=$num y=100}
```

### {import}

Import [macro](./macro.md) from another template

```smarty
{import 'math.tpl'}
```

```smarty
{import 'math.tpl' as math}
...
{math.plus x=5 y=100}
```
