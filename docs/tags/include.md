Tag {include}
=============

`{include}` tags are used for including other templates in the current template. Any variables available in the current template are also available within the included template.

```smarty
{include "about.tpl"}
```

Переменные для подключаемого шаблона можно переопределить

```smarty
{include "about.tpl" page=$item limit=50}
```