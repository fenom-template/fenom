Tag {import}
============

Import [macro](./macro.md) from another template

```smarty
{import 'page.macros.tpl'}
```

```smarty
{import 'listing.tpl' as listing}
...
{listing.paginator current=5 count=100}
```

