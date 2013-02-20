Tag {macros}
============


```smarty
declare macros

{macros.paginator(current, total, skip=true)}
    ... paginator code ...
{/macros}
...
invoke macros

{macros.paginator current=$page total=100}
```