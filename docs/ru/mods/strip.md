Модификатор strip
==============

Заменяет все повторяющиеся пробелы, переводы строк и символы табуляции одним пробелом.

This replaces all repeated spaces and tabs with a single space, or with the supplied string.

```smarty
{"   one    two   "|strip}
```
Результат обработки
```
one two
```

Опционально указывается флаг мультистрочности: `true` - тку же срезать переносы строк, `false` - срезать все кроме переносов строк.

```smarty
{"    multi
    line
    text    "|strip:true}
```

Результат обработки
```
multi line text
```