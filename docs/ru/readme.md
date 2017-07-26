Документация
=============

<!--img style="float:right" src="https://ficbook.net/images/user_avatars/avatar_%D0%93%D1%80%D0%B0%D0%BC%D0%BC%D0%B0%D1%80-%D0%9D%D0%B0%D1%86%D0%B8_1382414316.jpg" alt="grammar nazi required"-->

**Внимание! Документация в режиме беты, тексты могут содержать опечатки**

### Fenom

* [Быстрый старт](./start.md)
* [Адаптеры для фрейморков](./adapters.md)
* [Разработка Fenom](./dev/readme.md)
* [Настройки](./configuration.md)
* [Синтаксис](./syntax.md)
    * [Переменные](./syntax.md#Переменные)
    * [Значения](./syntax.md#Скалярные-значения)
    * [Массивы](./syntax.md#Массивы)
    * [Операторы](./operators.md)
    * [Модификаторы](./syntax.md#Модификаторы)
    * [Теги](./syntax.md#Теги)
    * [Параметры тегов](./syntax.md#Параметры-тегов)

***

### Теги

[Использование](./syntax.md#Теги) тегов.

* [set](./tags/set.md), [add](./tags/set.md#add) и [var](./tags/set.md#var) — определение значения переменной
* [if](./tags/if.md), [elseif](./tags/if.md#elseif) и [else](./tags/if.md#else) — условный оператор
* [foreach](./tags/foreach.md), [foreachelse](./tags/foreach.md#foreachelse),
  [break](./tags/foreach.md#break) и [continue](./tags/foreach.md#continue) — перебор элементов массива или объекта
* [switch](./tags/switch.md) и [case](./tags/switch.md#case) — групповой условный оператор
* [cycle](./tags/cycle.md) — циклицеский перебор массива значений
* [include](./tags/include.md), [insert](./tags/include.md#insert) — вставляет и исполняет указанный шаблон
* [extends](./tags/extends.md), [use](./tags/extends.md#use),
  [block](./tags/extends.md#block), [parent](./tags/extends.md#parent) и
  [paste](./tags/extends.md#paste) — [наследование](./inheritance.md) шаблонов
* [filter](./tags/filter.md) — применение модификаторов к фрагменту шаблона
* [ignore](./tags/ignore.md) — игнорирование тегов Fenom
* [macro](./tags/macro.md) и [import](./tags/macro.md#macro) — пользовательские функции шаблонов
* [autoescape](./tags/autoescape.md) — экранирует фрагмент шаблона
* [raw](./tags/raw.md) — отключает экранирование фрагмента шаблона
* [unset](./tags/unset.md) — удаляет переменные
* или [добавьте](./ext/extend.md#Добавление-тегов) свои

Устаревшие теги

* [for](./tags/for.md), `forelse`, `break` and `continue` — цикл

***

### Модификаторы

[Использование](./syntax.md#modifiers) модификаторов.

* [upper](./mods/upper.md) aka `up` — конвертирование строки в верхний регистр
* [lower](./mods/lower.md) aka `low` — конвертирование строки в нижний регистр
* [date_format](./mods/date_format.md) - форматирует дату, штамп времени через strftime() функцию
* [date](./mods/date.md) - форматирует дату, штамп времени через date() функцию
* [truncate](./mods/truncate.md) — обрезает текст до указанной длины
* [escape](./mods/escape.md) aka `e` — экранирует строку
* [unescape](./mods/unescape.md) — убирает экранирование строки
* [strip](./mods/strip.md) — удаляет лишние пробелы
* [length](./mods/length.md) — подсчитывает длину строки, массива, объекта
* [in](./mods/in.md) — проверяет наличие значения в массиве
* [match](./mods/match.md) — проверяет соответствие паттерну
* [ematch](./mods/ematch.md) — проверяет соответствие регулярному выражению
* [replace](./mods/replace.md) — заменяет все вхождения подстроки на строку замену
* [ereplace](./mods/ereplace.md) — заменяет все соответсвия регулярному выражению на строку замену.
* [split](./mods/split.md) — разбивает строку по подстроке
* [esplit](./mods/esplit.md) — разбивает строку по регулярному выражению
* [join](./mods/join.md) — объединяет массив в строку
* так же разрешены функции: `json_encode`, `json_decode`, `count`, `is_string`, `is_array`, `is_numeric`, `is_int`, `is_object`,
`strtotime`, `gettype`, `is_double`, `ip2long`, `long2ip`, `strip_tags`, `nl2br`
* или [добавьте](./ext/extend.md#Добавление-модификаторов) свои

***

### Операторы

* [Арифметические операторы](./operators.md#Арифметические-операторы) — `+`, `-`, `*`, `/`, `%`
* [Логические операторы](./operators.md#Логические-операторы) — `||`, `&&`, `!$var`, `and`, `or`, `xor`
* [Операторы сравнения](./operators.md#Операторы-сравнения) — `>`, `>=`, `<`, `<=`, `==`, `!=`, `!==`, `<>`
* [Битовые операторы](./operators.md#Битовые-операторы) — `|`, `&`, `^`, `~$var`, `>>`, `<<`
* [Операторы присвоения](./operators.md#Операторы-присвоения) — `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `&=`, `|=`, `^=`, `>>=`, `<<=`
* [Строковые операторы](./operators.md#Строковые-операторы) — `$str1 ~ $str2`, `$str1 ~~ $str2`, `$str1 ~= $str2`
* [Тернарные операторы](./operators.md#Тернарные-операторы) — `$a ? $b : $c`, `$a ! $b : $c`, `$a ?: $c`, `$a !: $c`
* [Проверяющие операторы](./operators.md#Проверяющие-операторы) — `$var?`, `$var!`
* [Оператор тестирования](./operators.md#Оператор-тестирования) — `is`, `is not`
* [Оператор содержания](./operators.md#Оператор-содержания) — `in`, `not in`

***

### Расширение

* [Источники шаблонов](./ext/extend.md#Источники-шаблонов)
* [Добавление модификаторов](./ext/extend.md#Добавление-модификаторов)
* [Добавление тегов](./ext/extend.md#Добавление-тегов)
* [Расширение тестового оператора](./ext/extend.md#Расширение-тестового-оператора)
* [Расширение глобальной переменной](./ext/extend.md#Расширение-глобальной-переменной)
* [Расширение Fenom](./ext/extend.md)
* [Add-ons](./ext/extensions.md)
