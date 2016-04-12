Tags {filter}
=============

Apply modifier to template area.

```smarty
{filter|strip_tags|truncate:20}
Remove all HTML <b>tags</b> and truncate {$text} to 20 symbols
{/filter}
```

**Note**: output buffering used. May be used a lot of memory if you output a lot of data.