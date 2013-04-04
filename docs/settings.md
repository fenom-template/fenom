Settings [RU]
=============

### Engine settings

Что бы установить папку для хранения кеша собранных шаблонов

```php
$cytro->setCompileDir($dir);
```

### Template settings

```php
// set options using factory
$cytro = Cytro::factory($tpl_dir, $compile_dir, $options);
// or inline using method setOptions
$cytro->setOptions($options);
```

Параметры могут быть массивом `'option_name' => true` (если ключ не указан автоматически задаётся false) или битовой маской.

* **disable_methods**, `Cytro::DENY_METHODS`, запретить вызов методов у объектов
* **disable_native_funcs**, `Cytro::DENY_INLINE_FUNCS`, запретить использование PHP функций, кроме разрешенных
* **auto_reload**, `Cytro::AUTO_RELOAD`, пересобирать шаблон если его оригинал был изменён (замедляет работу шаблонизатора).
* **force_compile**, `Cytro::FORCE_COMPILE`, пересобирать шаблон при каждом вызове (сильно замедляет работу шаблонизатора).
* **force_include**, `Cytro::FORCE_INCLUDE`, оптимизировать вставку шаблона в шаблон. Это увеличит производительность и размер собранного шаблона.

```php
$cytro->setOptions(array(
    "compile_check" => true,
    "force_include" => true
));
// same
$cytro->setOptions(Cytro::AUTO_RELOAD | Cytro::FORCE_INCLUDE);
```