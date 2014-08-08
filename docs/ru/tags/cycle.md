Тег {cycle}
===========

Тег {cycle} используется для прохода через множество значений.
С его помощью можно легко реализовать чередование двух или более заданных значений.

```smarty
{for $i=1 to=6}
    <div class="{cycle ["odd", "even"]}">
{/for}


{for $i=1 to=6}
    <div class="{cycle ["odd", "even"] index=$i}">
{/for}
```