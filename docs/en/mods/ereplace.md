Modifier ereplace
=================

Perform a regular expression search and replace.
[Read more](http://www.php.net/manual/en/reference.pcre.pattern.syntax.php) about regular expression.

```
{$string|replace:$pattern:$replacement}
```

Searches `$string` for matches to `$pattern` and replaces them with `$replacement`.

`$replacement` may contain references of the form `\n`, `$n` or `${n}`, with the latter form being the preferred one.
Every such reference will be replaced by the text captured by the n'th parenthesized pattern. n can be from 0 to 99,
and `\0` or `$0` refers to the text matched by the whole pattern.
Opening parentheses are counted from left to right (starting from 1) to obtain the number of the capturing subpattern.
To use backslash in replacement, it must be doubled.

```smarty
{var $string = 'April 15, 2014'}

{$string|ereplace:'/(\w+) (\d+), (\d+)/i':'${1}1, $3'} {* April1, 2014 *}
```