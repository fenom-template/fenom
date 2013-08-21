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

* При вызове метода `Fenom::display($template, $vars)` шаблонизатор [ищет](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L712) в своем хранилище уже загруженный шаблон.
Если шаблона [нет](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L727) - либо [загружает](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L762) код шаблона с файловой системыб либо [инициирует](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L759) его [компиляцию](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L788).
Последовательность компиляции шаблона следующая:
    * [Создается](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom.php#L660) "пустой" `Fenom\Template`
    * В него [загружется](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L157) исходный шаблон [из провайдера](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L167)
    * Исходный шаблон проходит pre-фильтры.
    * Начинается [разбор](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L196) исходного шаблона.
        * [Ищется](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L204) первый открывающий тег символ - `{`
        * [Смотрятся](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L205) следующий за `{` символ.
            * Если `}` или пробельный символ - ищется следующий символ `{`
            * Если `*` - ищется `*}`, текст до которого, в последствии, вырезается.
            * Ищется символ `}`. Полученный фрагмент шаблона считается тегом.
                * Если [был тег](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L238) `{ignore}` название тега проверяется на закрытие этого тега.
                * Для тега [создается](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L245) токенайзер и отдается в [диспетчер](https://github.com/bzick/fenom/blob/1.2.2/src/Fenom/Template.php#L488) тегов
                * Диспетчер тега вызывает различные парсеры выражений, компилятор тега и возвращает PHP код.
        * Проверяется стек на наличие не закрытых блоков тегов
    * PHP код проходит post-фильтры
    * Код шаблона сохраняеться на файлувую систему
    * Код шаблона выполняется для будущего использования

