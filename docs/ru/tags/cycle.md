Tag {cycle}
===========

```smarty
{for $i=$a.c..}
    <div class="{cycle ["odd", "even"]}">
{/for}


{for $i=$a.c..}
    <div class="{cycle ["odd", "even"] index=$i}">
{/for}
```