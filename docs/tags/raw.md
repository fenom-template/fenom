Tag {raw}
=========

Tag `{raw <expression>}` allow outputs render results without escaping.
This tag rewrite global option `auto_escape` for specified code.

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

For functions use tag with prefix `raw:`:

```smarty
{autoescape true}
    ...
    {my_func page=5} {* escape *}
    {my_func:raw page=5} {* unescape *}
    ...
{/autoescate}
```

Tag can not be applied to compilers as `foreach`, `if` and other.