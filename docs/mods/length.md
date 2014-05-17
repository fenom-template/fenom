Modifier length
===============

The modifier returns the number of items of a sequence or mapping, or the length of a string (works with UTF8 without `mbstring`)

```smarty
{if $images|length > 5}
 to many images
{/if}
```