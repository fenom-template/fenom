Modifier match
==============

Match string against a pattern.
The average user may be used to shell patterns or at least in their simplest form to `?` and `*` wildcards so using `match`
instead of `ematch` for frontend search expression input may be way more convenient for non-programming users.

```
{$string|match:$pattern}
```

Special pattern symbols:

* `?` — match one or zero unknown characters. `?at` matches `Cat`, `cat`, `Bat` or `bat` but not `at`.
* `*` — match any number of unknown characters. `Law*` matches `Law`, `Laws`, or `Lawyer`.
* `[characters]` — Match a character as part of a group of characters. `[CB]at` matches `Cat` or `Bat` but not `cat`, `rat` or `bat`.
* `\` - Escape character. `Law\*` will only match `Law*`


```smarty
{if $color|match:"*gr[ae]y"}
  some form of gray ...
{/if}
```