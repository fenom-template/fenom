Modifier replace
================

Replace all occurrences of the search string with the replacement string

```
{$string|replace:$search:$replace}
```

This modifier returns a string with all occurrences of `$search` in subject replaced with the given `$replace` value.

```smarty
{$fruits|replace:"pear":"orange"}
```