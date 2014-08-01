Tag {include}
=============

`{include}` tags are used for including other templates in the current template. Any variables available in the current template are also available within the included template.

```smarty
{include "about.tpl"}
```

If you need to set yours variables for template list them in attributes.

```smarty
{include "about.tpl" page=$item limit=50}
```

All variables changed in child template has no affect to variables in parent template.

### {insert}

The tag insert template code instead self.

* No dynamic name allowed.
* No variables as attribute allowed.
* Increase performance because insert code as is in compilation time.

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

Code of `b.tpl` will be inserted into `main.tpl` as is:

```smarty
a: {$a}
b: {$b}
c: {$c}
```
