Documentation
=============


### Fenom

* [Быстрый старт](./start.md)
* [Адаптеры для фрейморков](./adapters.md)
* [Для разработчиков](./dev/readme.md)
* [Нстройки](./configuration.md)
* [Синтаксис шаблонов](./syntax.md)
* [Операторы](./operators.md)

***

### Теги

[Использование](./syntax.md#tags) тегов.

* [var](./tags/var.md) — определение переменной
* [if](./tags/if.md), `elseif` и `else` — условный оператор
* [foreach](./tags/foreach.md), `foreaelse`, `break` and `continue` — перебор элементов массива или объекта
* [for](./tags/for.md), `forelse`, `break` and `continue` — цикл
* [switch](./tags/switch.md), `case`, `default` —
* [cycle](./tags/cycle.md) — циклицеский перебор массива значений
* [include](./tags/include.md), `insert` — вставляет и испольняет указанный шаблон
* [extends](./tags/extends.md), `use`, `block` и `parent` — наследование шаблонов
* [filter](./tags/filter.md) — примение модификаторов к фрагменту шаблона
* [ignore](./tags/ignore.md) — игнорирование тегов Fenom
* [macro](./tags/macro.md) и `import` — пользовательские функции шаблонов
* [autoescape](./tags/autoescape.md) — экранирует фрагмент шаблона
* [raw](./tags/raw.md) — отключает экранирование фрагмента шаблона
* [unset](./tags/unset.md) — удаляет переменные
* или [добавте](./ext/extend.md#add-tags) свои


***

### Модификаторы

[Использование](./syntax.md#modifiers) модификаторов.

* [upper](./mods/upper.md) aka `up` — конвертирование строки в верхний регистр
* [lower](./mods/lower.md) aka `low` — конвертирование строки в низкий регистр
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
* [split](./mods/split.md) — разивает строку по подстроке
* [esplit](./mods/esplit.md) — разивает строку по регулярному выражению
* [join](./mods/join.md) — объединяет массив в строку
* так же разрешены функции: `json_encode`, `json_decode`, `count`, `is_string`, `is_array`, `is_numeric`, `is_int`, `is_object`,
`strtotime`, `gettype`, `is_double`, `ip2long`, `long2ip`, `strip_tags`, `nl2br`
* или [добавте](./ext/extend.md#add-modifiers) свои

***

### Операторы

* [Арифметические операторы](./operators.md#arithmetic-operators) — `+`, `-`, `*`, `/`, `%`
* [Логические операторы](./operators.md#logical-operators) — `||`, `&&`, `!$var`, `and`, `or`, `xor`
* [Операторы сравнения](./operators.md#comparison-operators) — `>`, `>=`, `<`, `<=`, `==`, `!=`, `!==`, `<>`
* [Битовые операторы](./operators.md#bitwise-operators) — `|`, `&`, `^`, `~$var`, `>>`, `<<`
* [Операторы присвоения](./operators.md#assignment-operators) — `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `&=`, `|=`, `^=`, `>>=`, `<<=`
* [Строковый оператор](./operators.md#string-operator) — `$str1 ~ $str2`
* [Тернарные операторы](./operators.md#ternary-operators) — `$a ? $b : $c`, `$a ! $b : $c`, `$a ?: $c`, `$a !: $c`
* [Проверяющие операторы](./operators.md#check-operators) — `$var?`, `$var!`
* [Оператор тестирование](./operators.md#test-operator) — `is`, `is not`
* [Оператор содержания](./operators.md#containment-operator) — `in`, `not in`

***

### Расширение

* [Расширение Fenom](./ext/extend.md)
* [Add-ons](./ext/extensions.md)
