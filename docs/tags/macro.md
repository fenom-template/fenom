Tag {macro}
============

Declare

```smarty
declare macros

{macro paginator(current, total, skip=true)}
    ... paginator code ...
{/macro}
```

Invoke

```smarty
{paginator current=$page total=100}
```

Use tag [{import}](./import.md) for extending macros for another templates.