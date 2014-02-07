Basic usage
===========

### Initialize Fenom

Creating an object via factory method
```php
$fenom = Fenom::factory('/path/to/templates', '/path/to/compiled/template', $options);
```

Creating an object via `new` operator
```php
$fenom = new Fenom(new Provider('/path/to/templates'));
$fenom->setCompileDir('/path/to/template/cache');
$fenom->setOptions($options);
```

### Rendering template

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
