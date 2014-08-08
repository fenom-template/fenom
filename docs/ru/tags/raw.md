Тег {raw}
=========

Тег `{raw <expression>}` позволяет вывести результат выражения без экранирования.


```smarty
{autoescape true}
    ...
    {$var|up} {* escape *}
    {raw $var|up} {* unescape *}
    ...
    {"name is: <b>{$name|low}</b>"} {* escape *}
    {raw "name is: <b>{$name|low}</b>"} {* unescape *}
    ...
{/autoescate}
```

Для функций используйте параметр тега `:raw`:

```smarty
{autoescape true}
    ...
    {my_func page=5} {* escape *}
    {my_func:raw page=5} {* unescape *}
    ...
{/autoescape}
```