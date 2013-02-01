Aspect Template Engine
================

Templates looks like Smarty:

```smarty
<html>
    <head>
        <title>Aspect</title>
    </head>
    <body>
    {if $user?} {* or {if !empty($user)} *}
        <div>User name: {$user.name}</div>
        <ul>
        {foreach $user.actions as $action}
            <li>{$action.name} ({$action.timestamp|gmdate:"Y-m-d H:i:s"})</li>
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

Fetch template's result^

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
$content = $tempate->fetch($data);
```
