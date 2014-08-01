Modifier ematch
==============

Perform a regular expression match.
[Read more](http://www.php.net/manual/en/reference.pcre.pattern.syntax.php) about regular expression.


```
{$string|ematch:$pattern}
```

Searches `$string` for a match to the regular expression given in `$pattern`.

```smarty
{if $color|ematch:'/^gr[ae]y$/i'}
  some form of gray ...
{/if}
```