Tag {include} [RU]
==================

`{include}` tags are used for including other templates in the current template. Any variables available in the current template are also available within the included template.

```smarty
{include "about.tpl"}
```

Переменные для подключаемого шаблона можно переопределить, задавая их аргументами тега.

```smarty
{include "about.tpl" page=$item limit=50}
```

Все изменения переменных в подключаемом шаблоне не будут воздействовать на родительский шаблон.

### {insert}

The tag insert template code instead self.

* No dynamic name allowed
* No variables as attribute allowed

For example, main.tpl:

```smarty
a: {$a}
{insert 'b.tpl'}
c: {$c}
```

b.tpl:

```
b: {$b}
```

Во время разбора шаблона код шаблона `b.tpl` будет вставлен в код шаблона `main.tpl` как есть:

```smarty
a: {$a}
b: {$b}
c: {$c}
```
