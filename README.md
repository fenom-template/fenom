Fenom - Template Engine for PHP
===============================

> Composer package: `{"fenom/fenom": "dev-master"}`. See on [Packagist.org](https://packagist.org/packages/fenom/fenom)

[![Build Status](https://travis-ci.org/bzick/fenom.png?branch=master)](https://travis-ci.org/bzick/fenom)
## [Usage](./docs/usage.md) :: [Documentation](./docs/readme.md) :: [Benchmark](./docs/benchmark.md) :: [Articles](./docs/articles.md)

* Simple [syntax](./docs/syntax.md)
* [Fast](./docs/benchmark.md)
* [Secure](./docs/settings.md)
* Simple
* [Flexible](./docs/ext/extensions.md)
* [Lightweight](./docs/benchmark.md#stats)
* [Powerful](./docs/readme.md)
* Easy to use:

Simple template

```smarty
<html>
    <head>
        <title>Fenom</title>
    </head>
    <body>
    {if $templaters.fenom?}
        {var $tpl = $templaters.fenom}
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
$fenom = Fenom::factory('./templates', './compiled', Fenom::AUTO_RELOAD);
$fenom->display("pages/about.tpl", $data);
```

Get content

```php
<?php
$fenom = Fenom::factory('./templates', './compiled', Fenom::AUTO_RELOAD);
$content = $fenom->fetch("pages/about.tpl", $data);
```

Runtime compilation

```php
<?php
$fenom = new Fenom();
$template = $fenom->compileCode('Hello {$user.name}! {if $user.email?} Your email: {$user.email} {/if}');
$template->display($data);
// or
$content = $template->fetch($data);
```
