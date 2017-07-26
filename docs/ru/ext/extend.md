Расширение Fenom
================

# Добавление тегов

В шаблонизаторе принято различать два типа тегов: _компиляторы_ и _функции_.
Компиляторы вызываются во время преобразования кода шаблона в PHP код и возвращают PHP код который будет вставлен вместо тега.
А функции вызываются непременно в момент выполнения шаблона и возвращают непосредственно данные которые будут отображены.
Среди тегов как и в HTML есть строчные и блоковые теги.

## Линейные функции

Примитивное добавление функции можно осуществить следующим образом:

```php
$fenom->addFunction(string $function_name, callable $callback[, callable $parser]);
```

В данном случае запускается стандартный парсер, который автоматически разберет аргументы тега, которые должны быть в формате HTML атрибутов и отдаст их в функцию ассоциативным массивом:
```php
$fenom->addFunction("some_function", function (array $params) { /* ... */ });
```
При необходимости можно переопределить парсер на произвольный:
```php
$fenom->addFunction("some_function", $some_function, function (Fenom\Tokenizer $tokenizer, Fenom\Template $template) { /* parse tag */});
```
Существует более простой способ добавления произвольной функции:

```php
$fenom->addFunctionSmarty(string $function_name, callable $callback);
```

В данном случае парсер сканирует список аргументов коллбека и попробует сопоставить с аргументами тега.

```php
// ... class XYCalcs ..
public static function calc($x, $y = 5) { /* ... */}
// ...
$fenom->addFunctionSmart('calc', 'XYCalcs::calc');
```
пример выше позволяет объявить тег `{calc}` и спользовать его:
```smarty
{calc x=$top y=50} или {calc y=50 x=$top} вызовет XYCalcs::calc($top, 50)
{calc x=$top} или {calc $top} вызовет XYCalcs::calc($top)
```
Таким образом вы успешно можете добавлять Ваши функции или методы.

## Блоковые функции

Добавление блоковой функции аналогичен добавлению строковой за исключением того что есть возможность указать парсер для закрывающего тега.

```php
$fenom->addBlockFunction(string $function_name, callable $callback[, callable $parser_open[, callable $parser_close]]);
```

Сам коллбек принимает первым аргументом контент между открывающим и закрывающим тегом, а вторым аргументом - ассоциативный массив из аргуметов тега:
```php
$fenom->addBlockFunction('some_block_function', function (array $params, $content) { /* ... */});
```

## Линейный компилятор

Добавление строчного компилятора осуществляеться очень просто:

```php
$fenom->addCompiler(string $compiler, callable $parser);
```

Парсер должен принимать `Fenom\Tokenizer $tokenizer`, `Fenom\Template $template` и возвращать PHP код.
Компилятор так же можно импортировать из класса автоматически

```php
$fenom->addCompilerSmart(string $compiler, $storage);
```

`$storage` может быть как классом так и объектом. В данном случае шаблонизатор будет искать метод `tag{$compiler}`, который будет взят в качестве парсера тега.

## Блоковый компилятор

Добавление блочного компилятора осуществяется двумя способами. Первый

```php
$fenom->addBlockCompiler(string $compiler, array $parsers, array $tags);
```

где `$parser` ассоциативный массив `["open" => parser, "close" => parser]`, сождержащий парсер на открывающий и на закрывающий тег, а `$tags` содержит список внутренних тегов в формате `["tag_name"] => parser`, которые могут быть использованы только с этим компилятором.
Второй способ добавления парсера через импортирование из класса или объекта методов:

```php
$fenom->addBlockCompilerSmart(string $compiler, $storage, array $tags, array $floats);
```

# Добавление модификаторов

```
$fenom->addModifier(string $modifier, callable $callback);
```

* `$modifier` - название модификатора, которое будет использоваться в шаблоне
* `$callback` - функция обратного вызова, которая будет вызвана для изменения данных

Например:

```smarty
{$variable|my_modifier:$param1:$param2}
```

```php
$fenom->addModifier('my_modifier', function ($variable, $param1, $param2) {
    // ...
});
```

# Расширение тестового оператора

```php
$fenom->addTest(string $name, string $code);
```
`$code` - PHP код для условия, с маркером для замены на значение или переменную. 
Например, тест на целое число `is int` можно добавить как `$fenom->addTest('int', 'is_int(%s)')`. 
В шаблоне тесты выглядит как `{$a is int}`, а после компиляции выглядит приблизительно так - `is_int($a)`.

# Расширение глобальной переменной

Fenom обладает определенным [набором глобальных переменных](../syntax.md#Системная-переменная).
Однако их может не хватать для удобной работы и в этом случае потребуется добавить свои или переопределить/удалить существующие.
Метод `Fenom::addAccessor(string $name, callable $parser)` позволяет добавить свой обработчик-парсер `$parser`,
который будет вызван при встрече с глобальной переменной `$name` **во время компиляции шаблона**.

```php
$fenom->addAccessor('project', function (Fenom\Tokenizer $tokens) { /* code */ }); 
```

Указанный вторым аргументом, парсер будет вызван при встречи компилятором конструкции `$.project`.
Парсер сам должен разобрать все токены из набора токенов `$tokens` до того момента пока не посчитает что ему их хватит для
интерпретации. Возвращает парсер PHP код, который должен представлять значение восле выполенения, то есть его можно втавить в `if()`.

Через метод `Fenom::addAccessor($name, $parser)` можно переопределить уже любую другую существующую глобальную переменную.
Метод `Fenom::removeAccessor($name)` позволяет удалить любую определенную глобальную переменную или функцию по ее имени.

## Готовые решения

Орпеделить парсер для глобальной переменной весьма трудозатратно и требует полного понимания как работают парсеры в Fenom.
Это не удобно. Поэтому есть несколько предзаготовленных (умных) парсеров, которые берут рутину на себя, а пользователю остается указать ключевые параметры.

Умные парсеты добавляются через метод `Fenom::addAccessorSmart(string $name, string $accessor, string $parser)`,
где `$name` имя глобальной переменной, `$accessor` — параметр к парсеру, `$parser` — предопределенный парсер.

### Доступ к свойству

Парсер `Fenom::ACCESSOR_PROPERTY` позволит обратится к указанному свойству шаблонизатора из шаблона.
Параметр `$accessor` выступает как **имя свойства**:

```php
    $fenom->addAccessorSmart("site", "data", Fenom::ACCESSOR_PROPERTY);
    $fenom->data = [
        "domain" => 'example.ru',
        "support" => 'support@example.ru'
    ];
```
В шаблоне появится глобальная переменная `$.site`:
```smarty
<div class="copyright">© <a href="//{$.site.domain}">{$.site.domain}</a></div>
<div class="support">Support <a href="mailto:{$.site.support}">{$.site.support}</a></div>
```
Свойством может быть любое значение — масиив, объект и т.д.

### Доступ к методу

Парсер `Fenom::ACCESSOR_METHOD` позволит обратится к указанному методу шаблонизатора из шаблона.
Параметр `$accessor` выступает как **имя метода**:
```php
    $fenom->addAccessorSmart("fetch", "fetch", Fenom::ACCESSOR_METHOD);
```
В шаблоне появится глобальная функция `$.fetch`:
```smarty
{set $menu = $.fetch("site/menu.tpl")} {* $menu = $fenom->fetch("site/menu.tpl") *}
```
Шаблонизатор не проверят количество и тип параметров которые передает в метод.

### Доступ к значению

Парсер `Fenom::ACCESSOR_VAR` позволит обратится к указанному значению из шаблона.
Параметр `$accessor` выступает как **PHP выражение**, описывающее значение:
```php
    $fenom->addAccessorSmart("storage", "App::getInstance()->storage", Fenom::ACCESSOR_VAR);
```
В шаблоне появится глобальная переменная `$.storage`:
```smarty
{set $st = $.storage.di.stamp} {* $st = App::getInstance()->storage['di']['stamp'] *}
```

### Доступ к callable

Парсер `Fenom::ACCESSOR_CALL` позволит вызвать указанную финкцию или метод из шаблона.
Параметр `$accessor` выступает как **PHP выражение**, описывающее название функции или метод:
```php
    $fenom->addAccessorSmart("di", "App::getInstance()->di->get", Fenom::ACCESSOR_CALL);
```
`App::getInstance()->di->get` доллжно быть callable, то есть
```php
is_callable([App::getInstance()->di, "get"]) === true;
```
В шаблоне появится глобальная переменная `$.di`:
```smarty
{set $st = $.di("stamp")} {* $st = App::getInstance()->di->get("stamp") *}
```
Шаблонизатор не проверят количество и тип параметров которые передает в метод или функцию.

# Источники шаблонов

Шаблоны можно получать из самых разных источников.
Когда вы отображаете или вызываете шаблон, либо когда вы подключаете один шаблон к другому, вы указываете источник,
вместе с соответствующим путём и названием шаблона. Если источник явно не задан, то используется источник `Fenom\Provider`,
который считывает шаблоны из указанной директории.

Источник шаблонов должен реализовать интерфейс `Fenom\ProviderInterface`.
Используйте метод `$fenom->setProvider(...)`  что бы добавить источник в шаблонизатор, указав название источника и, если есть необходимость,
задать директорию кеша для шаблонов из этого источника. Рассмотрим на примере, реализуем источник шаблонов из базы данных.

Создадим источник:

```php

class DbProvider implements Fenom\ProviderInterface {
    // ...
}

```

Добавляем источник, указав удобное имя.

```php

$provider = new DbProvider();
$fenom->setProvider("db", $provider, "/tmp/cached/db");
```

Теперь источник можно использовать.

```php
$fenom->display("db:index.tpl", $vars);
```

```smarty
{include "db:menu.tpl"}
```

# Расширение кеша (эксперементальное)

Изначально Fenom не рассчитывался на то что кеш скомпиленых шаблонов может располагаться не на файловой системе.
Однако, в теории, есть возможность реализовать свое кеширование для скомпиленых шаблонов без переопределения шаблонизатора.
Речь идет о своем протоколе, отличным от `file://`, который [можно определить](http://php.net/manual/en/class.streamwrapper.php) в PHP.

Ваш протокол должен иметь класс реализации как указано в документации [Stream Wrapper](http://www.php.net/manual/en/class.streamwrapper.php).
Класс протокола может иметь не все указанные в документации методы. Вот список методов, необходимых шаблонизатору:

* [CacheStreamWrapper::stream_open](http://www.php.net/manual/en/streamwrapper.stream-open.php)
* [CacheStreamWrapper::stream_write](http://www.php.net/manual/en/streamwrapper.stream-write.php)
* [CacheStreamWrapper::stream_close](http://www.php.net/manual/en/streamwrapper.stream-close.php)
* [CacheStreamWrapper::rename](http://www.php.net/manual/en/streamwrapper.rename.php)

Для работы через `include`:

* [CacheStreamWrapper::stream_stat](http://www.php.net/manual/en/streamwrapper.stream-stat.php)
* [CacheStreamWrapper::stream_read](http://www.php.net/manual/en/streamwrapper.stream-read.php)
* [CacheStreamWrapper::stream_eof](http://www.php.net/manual/en/streamwrapper.stream-eof.php)

**Note**
(On 2014-05-13) Zend OpCacher кроме `file://` и `phar://` не поддерживает другие протоколы.

Пример работы кеша

```php
$this->setCacheDir("redis://hash/compiled/");
```

* `$cache = fopen("redis://hash/compiled/XnsbfeDnrd.php", "w");`
* `fwrite($cache, "... <template content> ...");`
* `fclose($cache);`
* `rename("redis://hash/compiled/XnsbfeDnrd.php", "redis://hash/compiled/main.php");`
