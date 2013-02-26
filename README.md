Aspect - awesome template engine for PHP
==========================

> Composer package: `{"bzick/aspect": "dev-master"}` 

[![Build Status](https://travis-ci.org/bzick/aspect.png?branch=master)](https://travis-ci.org/bzick/aspect)
## [About](./docs/about.md) :: [Documentation](./docs/main.md) :: [Benchmark](./docs/benchmark.md) :: [Articles](./docs/articles.md)

Features:

* Simple Smarty-like [syntax](./docs/syntax.md)
* [Fast](./docs/benchmark.md)
* [Secure](./docs/settings.md)
* Without regexp
* [Flexible](./docs/main.md#extends)
* [Lightweight](./docs/benchmark.md#satistic)
* Easy to use

Primitive template

```smarty
<html>
    <head>
        <title>Aspect</title>
    </head>
    <body>
    {if $templaters.aspect?}
        {var $tpl = $templaters.aspect}
        <div>Name: {$tpl.name}</div>
        <div>Description: {$tpl.name|truncate:80}</div>
        <ul>
        {foreach $tpl.features as $feature}
            <li>{$feature.name} (from {$feature.timestamp|gmdate:"Y-m-d H:i:s"})</li>
        {/foreach}
        </ul>
    {/if}
    </body>
</html>
```

Display template

```php
<?php
$aspect = Aspect::factory('./templates', './compiled', Aspect::CHECK_MTIME);
$aspect->display("pages/about.tpl", $data);
```

Get content

```php
<?php
$aspect = Aspect::factory('./templates', './compiled', Aspect::CHECK_MTIME);
$content = $aspect->fetch("pages/about.tpl", $data);
```

Runtime compilation

```php
<?php
$aspect = new Aspect();
$tempate = $aspect->compileCode('Hello {$user.name}! {if $user.email?} Your email: {$user.email} {/if}');
$tempate->display($data);
// or
$content = $tempate->fetch($data);
```
