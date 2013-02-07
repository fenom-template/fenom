Настройка
=========

### Параметры

#### Исходные шаблоны

Добавить папку с шаблонами:

```php
$aspect->addTemplateDir($dir);
```

Шаблонизатор последовательно будет перебирать папки и искать указанный шаблон.

#### Сборки шаблонов

Задаёт папку в которую будут сохранятся преобразованные в PHP шаблоны

```php
$aspect->setCompileDir($dir);
```

#### Опции

```php
$aspect->setOptions($options);
```

Массив `'option_name' => boolean` (если ключ не указан автоматически задаётся false)

* **disable_methods**, `boolean`, запретить вызов методов у объектов
* **disable_native_funcs**, `boolean`, запретить использование PHP функций, кроме разрешенных
* **disable_set_vars**, `boolean`, запретить изменять или задавать переменные
* **include_sources**, `boolean`, вставлять исходный код шаблона в его сборку
* **compile_check**, `boolean`, сравнивать mtime у исходного шаблона и его сборки. При изменении исходного шаблона будет производится его пересборка (замедляет работу шаблонизатора).
* **force_compile**, `boolean`, пересобирать шаблон при каждом вызове (сильно замедляет работу шаблонизатора).
* **force_include**, `boolean`.

или битовая маска из флагов:

* `Aspect::DENY_METHODS` то же что и **disable_methods**
* `Aspect::DENY_INLINE_FUNCS` то же что и **disable_native_funcs**
* `Aspect::DENY_SET_VARS` то же что и **disable_set_vars**
* `Aspect::INCLUDE_SOURCES` то же что и **include_sources**
* `Aspect::CHECK_MTIME` то же что и **compile_check**
* `Aspect::FORCE_COMPILE` то же что и **force_compile**
* `Aspect::FORCE_INCLUDE` то же что и **force_include**
