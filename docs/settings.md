Settings
========

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

Параметры могут быть массивом `'option_name' => true` (если ключ не указан автоматически задаётся false) или битовой маской.

| Code                 | Constant                  | Description  | Affect  |
| -------------------- | ------------------------- | ------------ | ------- |
| disable_methods      | `Fenom::DENY_METHODS`     | disable calling methods of objects in templates.  | |
| disable_native_funcs | `Fenom::DENY_INLINE_FUNCS`| disable calling native function in templates, except allowed. | |
| auto_reload          | `Fenom::AUTO_RELOAD`      | reload template if source will be changed | decreases the performance |
| force_compile        | `Fenom::FORCE_COMPILE`    | recompile template every time when the template renders | greatly decreases performance |
| disable_cache        | `Fenom::DISABLE_CACHE`    | disable compile cache | greatly decreases performance |
| force_include        | `Fenom::FORCE_INCLUDE`    | paste template body instead of include-tag | increases performance, increases cache size |
| auto_escape          | `Fenom::AUTO_ESCAPE`      | html-escape each variables outputs | decreases performance |
| force_verify         | `Fenom::FORCE_VERIFY`     | check existence every used variable | decreases performance |
| auto_trim            | `Fenom::AUTO_TRIM`        | remove space-characters before and after tags | |

```php
$fenom->setOptions(array(
    "compile_check" => true,
    "force_include" => true
));
// same
$fenom->setOptions(Fenom::AUTO_RELOAD | Fenom::FORCE_INCLUDE);
```

**By default all options disabled**

### Tag options

