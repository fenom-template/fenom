Setup
=====

## Configure

### Template cache

```php
$fenom->setCompileDir($dir);
```

This method set the name of the directory where template caches are stored. By default this is `/tmp`. This directory must be writeable.

### Template settings

```php
// set options using factory
$fenom = Fenom::factory($tpl_dir, $compile_dir, $options);
// or inline using method setOptions
$fenom->setOptions($options);
```

Options may by associative array like `'option_name' => true` or bitwise mask.

| Option name            | Constant                  | Description  | Affect  |
| ---------------------- | ------------------------- | ------------ | ------- |
| *disable_methods*      | `Fenom::DENY_METHODS`     | disable calling methods of objects in templates.  | |
| *disable_native_funcs* | `Fenom::DENY_NATIVE_FUNCS`| disable calling native function in templates, except allowed. | |
| *auto_reload*          | `Fenom::AUTO_RELOAD`      | reload template if source will be changed | decreases performance |
| *force_compile*        | `Fenom::FORCE_COMPILE`    | recompile template every time when the template renders | very decreases performance |
| *disable_cache*        | `Fenom::DISABLE_CACHE`    | disable compile cache | greatly decreases performance |
| *force_include*        | `Fenom::FORCE_INCLUDE`    | paste template body instead of include-tag | increases performance, increases cache size |
| *auto_escape*          | `Fenom::AUTO_ESCAPE`      | html-escape each variables outputs | decreases performance |
| *force_verify*         | `Fenom::FORCE_VERIFY`     | check existence every used variable | decreases performance |
| *auto_trim*            | `Fenom::AUTO_TRIM`        | remove space-characters before and after tags | |
| *disable_statics*      | `Fenom::DENY_STATICS`     | disable calling static methods in templates. | |
| *strip*                | `Fenom::STRIP`            | strip all whitespaces in templates. | decrease cache size |

```php
$fenom->setOptions(array(
    "compile_check" => true,
    "force_include" => true
));
// same
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::FORCE_INCLUDE);
```

**Note**
By default all options disabled

## Extends

### Template providers [TRANSLATE]

Бывает так что шаблны не хранятся на файловой сиситеме, а хранятся в некотором хранилище, например, в базе данных MySQL.
В этом случае шаблонизатору нужно описать как забирать шаблоны из хранилища, как проверять дату изменения шаблона и где хранить кеш шаблонов (опционально).
Эту задачу берут на себя Providers, это объекты реальзующие интерфейс `Fenom\ProviderInterface`.

### Cache providers [TRANSLATE]

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
2014-05-13 Zend OpCacher не поддерживает протоколы кроме `file://` и `phar://`.

For example,

```php
$this->setCacheDir("redis://hash/compiled/");
```

* `$cache = fopen("redis://hash/compiled/XnsbfeDnrd.php", "w");`
* `fwrite($cache, "... <template content> ...");`
* `fclose($cache);`
* `rename("redis://hash/compiled/XnsbfeDnrd.php", "redis://hash/compiled/main.php");`

### Callbacks and filters

