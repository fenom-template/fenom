Basic usage
===========

### Creating template engine

```php
$fenom = Fenom::factory('/path/to/templates', '/path/to/template/cache', $options);

//or

$fenom = new Fenom(new FSProvider('/path/to/templates'));
$fenom->setCompileDir('/path/to/template/cache');
$fenom->setOptions($options);
```

### Output template result

```php
$fenom->display("template/name.tpl", $vars);
```