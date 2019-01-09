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
| *disable_methods*      | `Fenom::DENY_METHODS`     | disable calling methods of objects in templates.  |  |
| *disable_native_funcs* | `Fenom::DENY_NATIVE_FUNCS`| disable calling native function in templates, except allowed. |  |
| *auto_reload*          | `Fenom::AUTO_RELOAD`      | reload template if source will be changed | decreases performance |
| *force_compile*        | `Fenom::FORCE_COMPILE`    | recompile template every time when the template renders | very decreases performance |
| *disable_cache*        | `Fenom::DISABLE_CACHE`    | disable compile cache | greatly decreases performance |
| *force_include*        | `Fenom::FORCE_INCLUDE`    | paste template body instead of include-tag | increases performance, increases cache size |
| *auto_escape*          | `Fenom::AUTO_ESCAPE`      | html-escape each variables outputs | decreases performance |
| *force_verify*         | `Fenom::FORCE_VERIFY`     | check existence every used variable | decreases performance |
| *disable_statics*      | `Fenom::DENY_STATICS`     | disable calling static methods in templates. |  |
| *strip*                | `Fenom::AUTO_STRIP`       | strip all whitespaces in templates. | decrease cache size |
<!-- 
| *auto_trim*            | `Fenom::AUTO_TRIM`        | remove space-characters before and after tags |  |
-->

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

### Template providers

Sometimes templates are stored not in the file system, but in some repository, for example, in the MySQL database. In this case, the template needs to describe how to take templates from the repository, how to check the date the template was modified, and where to store the template cache (optional). This task is taken by the `Provider` classes, these are objects realizing the interface `Fenom\ProviderInterface`.

### Callbacks and filters

#### Before compile callback

```php
$fenom->addPreFilter(function () { /* ... */ });
```

#### Tag filter callback

#### Filter callback

#### After compile callback
