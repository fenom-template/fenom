Basic usage
===========

## Install Fenom

### Composer

Add package Fenom in your require-list in `composer.json`:
```json
{
    "require": {
        "fenom/fenom": "2.*"
    }
}
```
and update project's dependencies: `composer update`.

### Custom loader

Clone Fenom to any directory: `git clone https://github.com/bzick/fenom.git`. Recommended use latest tag.
Fenom use [psr-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md#autoloading-standard) autoloading standard. Therefore you can
* use `psr-0` format in your project loader for loading Fenom's classes
* or register Fenom's autoloader: `Fenom::registerAutoload();` for loading itself.

Also you can use this autoloader for loading any library with `psr-0` file naming:
```php
Fenom::registerAutoload(PROJECT_DIR."/src");
```

## Setup Fenom

Create an object via factory method
```php
$fenom = Fenom::factory('/path/to/templates', '/path/to/compiled/template', $options);
```

Create an object via `new` operator
```php
$fenom = new Fenom(new Provider('/path/to/templates'));
$fenom->setCompileDir('/path/to/template/cache');
$fenom->setOptions($options);
```

* `/path/to/templates` — directory, where stores your templates.
* `/path/to/template/cache` — directory, where stores compiled templates in PHP files.
* `$options` - bit-mask or array of [Fenom settings](./configuration.md#template-settings).

### Use Fenom

Output template
```php
$fenom->display("template/name.tpl", $vars);
```

Get the result of rendering the template
```php
$result = $fenom->fetch("template/name.tpl", $vars);
```

Create the pipeline of rendering into callback
```php
$fenom->pipe(
    "template/sitemap.tpl",
    $vars,
    $callback = [new SplFileObject("/tmp/sitemap.xml", "w"), "fwrite"], // pipe to file /tmp/sitemap.xml
    $chunk_size = 1e6 // chunk size for callback
);
```
