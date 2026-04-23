Basic usage
==========

## Install Fenom

### Composer

Add package Fenom in your require-list in `composer.json`:
```json
{
    "require": {
        "fenom/fenom": "^3.0"
    }
}
```
and update project's dependencies: `composer update`.

### Manual Installation

Clone Fenom to any directory: `git clone https://github.com/fenom-template/fenom.git`.

Fenom uses [PSR-4](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md) autoloading standard.
Include Composer's autoloader or use any PSR-4 compatible autoloader:

```php
require_once '/path/to/vendor/autoload.php';
```

## Setup Fenom

Create an object via factory method
```php
$fenom = Fenom::factory('/path/to/templates', '/path/to/compiled/template', $options);
```

Create an object via `new` operator
```php
$fenom = new Fenom(new Fenom\Provider('/path/to/templates'));
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
