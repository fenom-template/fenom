Быстрый старт
=============

## Установка Fenom

### Composer

Fenom зарегистрирован на [packagist.org](https://packagist.org/) как пакет [fenom/fenom](https://packagist.org/packages/fenom/fenom).
Что бы установить Fenom через composer пропишите в `composer.json` списке пакетов:
```json
{
    "require": {
        "fenom/fenom": "2.*"
    }
}
```
и обновите зависимости: `composer update`.

### Произвольная подгрузка

Клонируйте Fenom в любую директорию Вашего проекта: `git clone https://github.com/bzick/fenom.git`. Рекомендуется использовать последнюю версию.
Для загрузки классов Fenom использует [psr-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md#autoloading-standard) стандарт.
Таким образом вы можете:
* использовать Ваш автозагрузчик, который понимает `psr-0` формат для загрузки классов Fenom из директории `src/` с пространством имен `Fenom`.
* или использовать встроенный автозагрузчик Fenom: `Fenom::registerAutoload();` для загрузки самого себя.

Так же вы можете использовать встроенный в Fenom автозагрузчик для загрузки других классов в `psr-0` формате:
```php
Fenom::registerAutoload(PROJECT_DIR."/classes");
```

## Настройка Fenom

Есть два варианта инициировать объект шаблонизатора: через `new` оператор и фабрику.
Пример создания Fenom через фабрику:
```php
$fenom = Fenom::factory('/path/to/templates', '/path/to/compiled/template', $options);
```
Пример создания Fenom через оператор `new`:
```php
$fenom = new Fenom(new Fenom\Provider('/path/to/templates'));
$fenom->setCompileDir('/path/to/template/cache');
$fenom->setOptions($options);
```

* `/path/to/templates` — директория в которой хранятся шаблоны.
* `/path/to/template/cache` — директория в которую Fenom будет сохранять PHP-кеш шаблонов
* `$options` - битовая маска или массив [параметров](./configuration.md).

### Использование

Что бы отобразить шаблон на экран используйте метод `display`:

```php
// $fenom->display(string $template, array $variables) : void

$fenom->display("template/name.tpl", $vars);
```

Метод найдет шаблон `template/name.tpl` отрисует его в `stdout`, подставляя переменные из массива `$vars`.

Метод `fetch` возвращает вывод шаблона вместо его отображения на экран.
```php
// $fenom->fetch(string $template, array $variables) : string

$result = $fenom->fetch("template/name.tpl", $vars);
```

Для вывода большого количества данных можно использовать поток

```php
// $fenom->pipe(string $template, array $variables, callable $callback, int $chunk_size) : void

$fenom->pipe(
    "template/sitemap.tpl",
    $vars,
    $callback = [new SplFileObject("compress.zlib:///tmp/sitemap.xml.gz", "w"), "fwrite"], // поток с архивацией в файл /tmp/sitemap.xml.gz
    1e6 // размер куска данных в байтах
);
```

Поток позволяет обрабатывать большой результат по кускам, размер куска указывается в байтах аргументом `$chunk_size`.
Каждый кусок передается в `$callback` для обработки или вывода.

<!--
## Пример простого приложения

```
App/ (ROOT_DIR)
 ┠─ configs/ (файлы конфигурации приложения)
 ┠─ src/ (классы приложения)
 ┠─ templates/ (шаблоны приложения)
 ┠─ public/ (DOCUMENT_ROOT)
 ┃   ┠─ static/ (папка со статикой) 
 ┃   ┖─ index.php (скрипт обработки всех динамических запросов)
 ┠─ tmp/ (папка доступная для записи web-серверу для хранения временных файлов)
 ┃   ┖─ compiled/ (кеша шаблонов)
 ┠─ vendor/ (строронние бибилиотеки)
 ┖─ composer.json (описание зависимостей для composer) 
```

`index.php`:
```php
define('ROOT_DIR', dirname(__DIR__));

$fenom = Fenom::factory(ROOT_DIR.'/templates', ROOT_DIR.'/cache', Fenom::FORCE_VERIFY | Fenom::AUTO_RELOAD);


```
-->
