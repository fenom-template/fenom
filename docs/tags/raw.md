Tag {raw} [RU]
==================

Тег `{raw <expression>}` позволяет не экранировать результат выражения если включена глобальная настройка экранирование вывода или выражение распологасется в экранироуемой области.

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

Для результатов функций так же может быть отключено экранирование:

```smarty
{autoescape true}
    ...
    {my_func page=5} {* escape *}
    {raw:my_func page=5} {* unescape *}
    ...
    {my_block_func page=5}
        ...
    {/my_block_func} {* escape *}
    {raw:my_block_func page=5}
        ...
    {/my_block_func} {* unescape *}
    ...
{/autoescate}
```

На компиляторы свойство raw не распространяется.