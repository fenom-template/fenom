Settings [RU]
=============

### Engine settings

Что бы установить папку для хранения кеша собранных шаблонов

```php
$aspect->setCompileDir($dir);
```

### Template settings

```php
// set options using factory
$aspect = Aspect::factory($tpl_dir, $compile_dir, $options);
// or inline using method setOptions
$aspect->setOptions($options);
```

Параметры могут быть массивом `'option_name' => true` (если ключ не указан автоматически задаётся false) или битовой маской.

* **disable_methods**, `Aspect::DENY_METHODS`, запретить вызов методов у объектов
* **disable_native_funcs**, `Aspect::DENY_INLINE_FUNCS`, запретить использование PHP функций, кроме разрешенных
* **auto_reload**, `Aspect::AUTO_RELOAD`, пересобирать шаблон если его оригинал был изменён (замедляет работу шаблонизатора).
* **force_compile**, `Aspect::FORCE_COMPILE`, пересобирать шаблон при каждом вызове (сильно замедляет работу шаблонизатора).
* **force_include**, `Aspect::FORCE_INCLUDE`, оптимизировать вставку шаблона в шаблон. Это увеличит производительность и размер собранного шаблона.

```php
$aspect->setOptions(array(
    "compile_check" => true,
    "force_include" => true
));
// same
$aspect->setOptions(Aspect::AUTO_RELOAD | Aspect::FORCE_INCLUDE);
```