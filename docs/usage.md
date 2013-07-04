Basic usage
===========

### Initialize Fenom

Use factory method
```php
$fenom = Fenom::factory('/path/to/templates', '/path/to/compiled/template', $options);
```

Use `new` operator
```php
$fenom = new Fenom(new Provider('/path/to/templates'));
$fenom->setCompileDir('/path/to/template/cache');
$fenom->setOptions($options);
```

### Render template

Output template
```php
$fenom->display("template/name.tpl", $vars);
```

Get template into the variable
```php
$result = $fenom->fetch("template/name.tpl", $vars);
```

Create pipe-line into callback
```php
$fenom->pipe(
    "template/sitemap.tpl",
    $vars,
    $callback = [new SplFileObject("/tmp/sitemap.xml", "w"), "fwrite"], // pipe to file /tmp/sitemap.xml
    $chunk_size = 1e6 // chunk size for callback
);
```
