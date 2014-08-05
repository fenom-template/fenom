Тег {filter}
=============

Тег {filter} позволяет применить модификаторы на фрагмент шаблона

```smarty
{filter|strip_tags|truncate:20}
Remove all HTML <b>tags</b> and truncate {$text} to 20 symbols
{/filter}
```