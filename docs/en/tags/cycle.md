Tag {cycle}
===========

`{cycle}` is used to alternate a set of values.

```smarty
{foreach 1..10}
    <div class="{cycle ["odd", "even"]}">
{/foreach}


{foreach 1..10}
    <div class="{cycle ["odd", "even"] index=$i}">
{/foreach}
```