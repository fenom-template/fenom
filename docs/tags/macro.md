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

Use tag [{import}](./import.md) for importing existing macroses into another template