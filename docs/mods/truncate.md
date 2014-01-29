Modifier truncate
=================

Modifier truncates a variable to a character length.

```smarty
{$long_string|truncate:$length:$etc:$by_words:$middle}
```

* `$length`, required. Parameter determines how many characters to truncate to.
* `$etc`, by default `...`. This is a text string that replaces the truncated text.
* `$by_word`, by default **FALSE**. This determines whether or not to truncate at a word boundary with TRUE, or at the exact character with FALSE.
* `$middle`, by default **FALSE**. This determines whether the truncation happens at the end of the string with FALSE, or in the middle of the string with TRUE.

```smarty
{var $str = "very very long string"}

{$str|truncate:10:" read more..."} output: very very read more...
{$str|truncate:5:" ... ":true:true} output: very ... string
```

Modifier do not use `mbstring` when works with UTF8.