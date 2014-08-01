Модификаторы [RU]
============


```
$fenom->addModifier(string $modifier, callable $callback);
```

* `$modifier` - название модификатора, которое будет использоваться в шаблоне
* `$callback` - коллбек, который будет вызван для изменения данных

For example:

```smarty
{$variable|my_modifier:$param1:$param2}
```

```php
$fenom->addModifier('my_modifier', function ($variable, $param1, $param2) {
    // ...
});
```
