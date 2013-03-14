Modifier strip [RU]
===================

Для удаления символы пробелов при использовании переменной используйте можификатор `|strip`

```smarty
{"   one    two   "|strip} => 'one two'
```

Что бы убрать переносы строк укажите **TRUE** первым аргументом модификатора

```smarty
{"    multi
    line
    text    "|strip:true} => 'multi line text'
```
