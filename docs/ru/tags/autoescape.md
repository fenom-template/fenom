Tag {autoescape}
=====================

Force enable or disable `auto_escape` option for block area:

```smarty
{autoescape true}
    ...
    Text: {$text} {* value of the variable $text will be escaped *}
    ...
{/autoescape}
```

Also see {raw} tag and :raw tag option
