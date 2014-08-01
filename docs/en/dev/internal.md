How it work
===========

## Терминология

* Исходный шаблон - изначальный вид шаблона в специальном синтаксисе
* Код шаблона - резальтат компиляции шаблона, PHP код.
* Провайдер - объект, источник исходных шаблонов.

## Классы

* `Fenom` - является хранилищем
    * [шаблонов](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L88)
    * [модификаторов](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L112)
    * [фильтров](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L73)
    * [тегов](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L140)
    * [провайдеров](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L107)
    * [настройки](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L98) - маска из [опций](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L29)
    * [список](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L131) разрешенных функций

    а также обладает соответсвующими setter-ами и getter-ами для настройки.
* `Fenom\Tokenizer` -  разбирает, при помощи [tokens_get_all](http://docs.php.net/manual/en/function.token-get-all.php), строку на токены, которые хранит [массивом](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Tokenizer.php#L84).
Обладает методами для обработки токенов, работающими как с [конкретными токенами](http://docs.php.net/manual/en/tokens.php) так и с их [группами](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Tokenizer.php#L94).
* `Fenom\Render` - простейший шаблон. Хранит
    * `Closure` с [PHP кодом](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Render.php#L30) шаблона
    * [настройки](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Render.php#L19)
    * [зависимости](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Render.php#L59)
* `Fenom\Template` - шаблон с функцией компиляции, расширен от `Fenom\Render`. Содержит различные методы для разбора выражений при помощи `Fenom\Tokenizer`.
* `Fenom\Compiler` - набор правил разбора различных тегов.
* `Fenom\Modifier` - набор модификаторов.
* `Fenom\Scope` - абстрактный уровень блочного тега.
* `Fenom\ProviderInterface` - интерфейс провадеров шаблонов
* `Fenom\Provider` - примитивный провайдер шаблонов с файловой системы.

## Процесс работы

При вызове метода `Fenom::display($template, $vars)` шаблонизатор [ищет](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L712) в своем хранилище уже загруженный шаблон.
Если шаблона [нет](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L727) - либо [загружает](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L762) код шаблона с файловой системыб либо [инициирует](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L759) его [компиляцию](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L788).

### Компиляция шаблонов

* [Создается](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L660) "пустой" `Fenom\Template`
* В него [загружется](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L157) исходный шаблон [из провайдера](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L167)
* Исходный шаблон проходит [pre-фильтры](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L200).
* Начинается [разбор](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L196) исходного шаблона.
    * [Ищется](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L204) первый открывающий тег символ - `{`
    * [Смотрятся](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L205) следующий за `{` символ.
        * Если `}` или пробельный символ - ищется следующий символ `{`
        * Если `*` - ищется `*}`, текст до которого, в последствии, вырезается.
        * Ищется символ `}`. Полученный фрагмент шаблона считается тегом.
        * Если [был тег](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L238) `{ignore}` название тега проверяется на закрытие этого тега.
        * Для тега [создается](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L245) токенайзер и отдается в [диспетчер](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L488) тегов
        * Диспетчер тега вызывает различные парсеры выражений, компилятор тега и возвращает PHP код (см ниже).
        * Полученный фрагмент PHP кода [обрабатывается и прикрепляется](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L362) к коду шаблона.
        * Ищется следующий `{` символ...
        * ...
        * В конце проверяется токенайзер на наличие не используемых токенов, если таковые есть - выбрасывается ошибка.
    * [Проверяется](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L264) стек на наличие не закрытых блоковых тегов
* PHP код проходит [post-фильтры](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L282)
* Код шаблона [сохраняеться](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L799) на файлувую систему
* Код шаблона выполняется для использования

### Как работает токенайзер

Объек токенайзера принимает на вход любую строчку и разбирает ее при помощи функции token_get_all(). Полученные токен складываются в массив. Каждый токен прдсатвляет из себя числовой массив из 4-х элементов:

* Код токена. Это либо число либо один символ.
* Тело токена. Содержимое токена.
* Номер строки в исходной строке
* Пробельные символы, идущие за токеном

Токенайзер обладает внутренним указателем на "текущий" токен, передвигая указатель можно получить доступ к токенам через специальные функции-проверки. Почти все функции-проверки проверяют текущее значение на соответствие кода токену. Вместо кода может быть отдан код группы токенов.

### Как работает диспетчер тегов

* Проверяет, не является выражение в токенайзере [тегом ignore](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L492).
* Проверяет, не является выражение в токенайзере [закрывающим тегом](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L499).
* Проверяет, не является выражение в токенайзере [скалярным значением](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L566).
* По имени тега из [списка тегов](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L140) выбирается массив и запускается [соответсвующий](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L582) парсер.
* Парсер возвращает PHP код

### Как работают парсеры

Парсер всегда получает объект токенайзера. Курсор токенайзера установлен на токен с которого начинается выражение, которое должен разобрать парсер.
Таким образом, по завершению разбора выражения, парсер должен установить курсор токенайзера на первый незнакомый ему символ.
Для примера рассмортим парсер переменной `Fenom\Template::parseVar()`.
В шаблоне имеется тег {$list.one.c|modifier:1.2}. В парсер будет отдан объект токенайзера `new Tokenizer('$list.one.c|modifier:1.2')` с токенами `$list` `.` `one` `.` `c` `|` `modifier` `:` `1.2`.
Указатель курсора установлен на токен `$list`. После разбора токенов, курсор будет установлен на `|` так как это не знакомый парсеру переменных токен. Следующий парсер может быть вызван `Fenom\Template::parseModifier()`, который распарсит модификатор.
