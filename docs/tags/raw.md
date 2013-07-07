Tag {raw} [RU]
==================

Тег `{raw <expression>}` позволяет выводить результат выражения или функций без экранирования, игнорируя глобальную настройку `auto_secape`.

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

Для результатов функций то же может быть отключено экранирование:

```smarty
{autoescape true}
    ...
    {my_func page=5} {* escape *}
    {raw:my_func page=5} {* unescape *}
    ...
{/autoescate}
```

На компиляторы свойство raw не распространяется.