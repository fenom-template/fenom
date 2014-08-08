Тег {autoescape}
================

Задает индивидуальное значение параметра `auto_escape` на фрагмент шаблона:

```smarty
{autoescape true}
    ...
    Text: {$text} {* значение переменной $text будет заэкранированно *}
    ...
{/autoescape}
```

Так же смотите тег [{raw}](./raw.md) и параметр тега [:raw](../configuration.md)
