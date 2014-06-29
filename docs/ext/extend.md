Extends Fenom
=============

*TODO*

# Add tags

В шаблонизаторе принято различать два типа тегов: _компиляторы_ и _функции_.
Compilers invokes during compilation template to PHP source and have to
Компиляторы вызываются во время преобразования кода шаблона в PHP код и возвращяю PHP код который будет вставлен вместо тега.
А функции вызываются непременно в момент выполнения шаблона и возвращают непосредственно данные которые будут отображены.
Среди тегов как и в HTML есть строчные и блоковые теги.

## Inline function

Примитивное добавление функции можно осуществить следующим образом:

```php
$fenom->addFunction(string $function_name, callable $callback[, callable $parser]);
```

В данном случае запускается стандартный парсер, который автоматически разберет аргументы тега, которые должны быть в формате HTML аттрибутов и отдаст их в функцию ассоциативным массивом:
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
then
```smarty
{calc x=$top y=50} or {calc y=50 x=$top} is XYCalcs::calc($top, 50)
{calc x=$top} or {calc $top} is XYCalcs::calc($top)
```
Таким образом вы успешно можете добавлять Ваши функции или методы.

## Block function

Добавление блоковой функции аналогичен добавлению строковой за исключением того что есть возможность указать парсер для закрывающего тега.

```php
$fenom->addBlockFunction(string $function_name, callable $callback[, callable $parser_open[, callable $parser_close]]);
```

Сам коллбек принимает первым аргументом контент между открывающим и закрывающим тегом, а вторым аргументом - ассоциативный массив из аргуметов тега:
```php
$fenom->addBlockFunction('some_block_function', function ($content, array $params) { /* ... */});
```

## Inline compiler

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

## Block compiler

Добавление блочного компилятора осуществяется двумя способами. Первый

```php
$fenom->addBlockCompiler(string $compiler, array $parsers, array $tags);
```

где `$parser` ассоциативный массив `["open" => parser, "close" => parser]`, сождержащий парсер на открывающий и на закрывающий тег, а `$tags` содержит список внутренних тегов в формате `["tag_name"] => parser`, которые могут быть использованы только с этим компилятором.
Второй способ добавления парсера через импортирование из класса или объекта методов:

```php
$fenom->addBlockCompilerSmart(string $compiler, $storage, array $tags, array $floats);
```

# Add modifiers

```
$fenom->addModifier(string $modifier, callable $callback);
```

* `$modifier` - название модификатора, которое будет использоваться в шаблоне
* `$callback` - коллбек, который будет вызван для изменения данных

For example:

```smarty
{$variable|my_modifier:$param1:$param2}
```

```php
$fenom->addModifier('my_modifier', function ($variable, $param1, $param2) {
    // ...
});
```

# Extends test operator

```php
$fenom->addTest($name, $code);
?>
```

# Add template provider

Бывает так что шаблны не хранятся на файловой сиситеме, а хранятся в некотором хранилище, например, в базе данных MySQL.
В этом случае шаблонизатору нужно описать как забирать шаблоны из хранилища, как проверять дату изменения шаблона и где хранить кеш шаблонов (опционально).
Эту задачу берут на себя Providers, это объекты реальзующие интерфейс `Fenom\ProviderInterface`.

# Extends accessor

# Extends cache

Изначально Fenom не расчитывался на то что кеш скомпиленых шаблонов может располагаться не на файловой системе.
Однако, в теории, есть возможность реализовать свое кеширование для скомпиленых шаблонов без переопределения шаблонизатора.
Речь идет о своем протоколе, отличным от `file://`, который [можно определить](http://php.net/manual/en/class.streamwrapper.php) в PHP.

Ваш протол должени иметь класс реализации протокола как указан в документации [Stream Wrapper](http://www.php.net/manual/en/class.streamwrapper.php).
Класс протокола может иметь не все указанные в документации методы. Вот список методов, необходимых шаблонизатору:

* [CacheStreamWrapper::stream_open](http://www.php.net/manual/en/streamwrapper.stream-open.php)
* [CacheStreamWrapper::stream_write](http://www.php.net/manual/en/streamwrapper.stream-write.php)
* [CacheStreamWrapper::stream_close](http://www.php.net/manual/en/streamwrapper.stream-close.php)
* [CacheStreamWrapper::rename](http://www.php.net/manual/en/streamwrapper.rename.php)

For `include`:

* [CacheStreamWrapper::stream_stat](http://www.php.net/manual/en/streamwrapper.stream-stat.php)
* [CacheStreamWrapper::stream_read](http://www.php.net/manual/en/streamwrapper.stream-read.php)
* [CacheStreamWrapper::stream_eof](http://www.php.net/manual/en/streamwrapper.stream-eof.php)

**Note**
(On 2014-05-13) Zend OpCacher doesn't support custom protocols except `file://` and `phar://`.

For example,

```php
$this->setCacheDir("redis://hash/compiled/");
```

* `$cache = fopen("redis://hash/compiled/XnsbfeDnrd.php", "w");`
* `fwrite($cache, "... <template content> ...");`
* `fclose($cache);`
* `rename("redis://hash/compiled/XnsbfeDnrd.php", "redis://hash/compiled/main.php");`
