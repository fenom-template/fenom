Settings [RU]
=============

### Engine settings

Что бы установить папку для хранения кеша собранных шаблонов

```php
$fenom->setCompileDir($dir);
```

### Template settings

```php
// set options using factory
$fenom = Fenom::factory($tpl_dir, $compile_dir, $options);
// or inline using method setOptions
$fenom->setOptions($options);
```

Параметры могут быть массивом `'option_name' => true` (если ключ не указан автоматически задаётся false) или битовой маской.

* **disable_methods**, `Fenom::DENY_METHODS`, запретить вызов методов у объектов
* **disable_native_funcs**, `Fenom::DENY_INLINE_FUNCS`, запретить использование PHP функций, кроме разрешенных.
* **auto_reload**, `Fenom::AUTO_RELOAD`, пересобирать шаблон если его оригинал был изменён (замедляет работу шаблонизатора).
* **force_compile**, `Fenom::FORCE_COMPILE`, пересобирать шаблон при каждом вызове (сильно замедляет работу шаблонизатора).
* **disable_cache**, `Fenom::DISABLE_CACHE`, не сохранять собранный шаблон на диск (сильно замедляет работу шаблонизатора).
* **force_include**, `Fenom::FORCE_INCLUDE`, оптимизировать вставку шаблона в шаблон. Это увеличит производительность и размер собранного шаблона.
Опция активируется если имя шаблона задано явно и скалярно.
* **auto_escape**, `Fenom::AUTO_ESCAPE`, все выводящие переменные и результаты функций будут экранироваться
* **auto_trim**, `Fenom::AUTO_TRIM`, при компиляции, все пробельные символы между тегами будут удлаены.
* **force_verify**, `Fenom::FORCE_VERIFY`, проверять обращение каждой переменной и возвращать NULL если переменной не существует.
* **deny_static_methods**, `Fenom::DENY_STATIC_METHODS`, отключает возможность вызова статичных методов в шаблоне.

```php
$fenom->setOptions(array(
    "compile_check" => true,
    "force_include" => true
));
// same
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::FORCE_INCLUDE);
```

По умолчанию, все опции отключены.

### Tag options

## :raw

## :trim, :ltrim, :rtrim