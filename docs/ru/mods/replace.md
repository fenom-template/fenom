Модификатор replace
================

Заменяет все вхождения строки поиска на строку замены

```
{$string|replace:$search:$replace}
```

Этот модификатор возвращает строку, в котором все вхождения `$search` в `$subject` заменены на `$replace`.

```smarty
{$fruits|replace:"pear":"orange"}
```