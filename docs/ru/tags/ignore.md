Tag {ignore}
============

{ignore} tags allow a block of data to be taken literally.
This is typically used around Javascript or stylesheet blocks where {curly braces} would interfere with the template delimiter syntax.
Anything within {ignore}{/ignore} tags is not interpreted, but displayed as-is.

```smarty
{ignore}
    var data = {"time": obj.ts};
{/ignore}
```

{ignore} tags are normally not necessary, as Fenom ignores delimiters that are surrounded by whitespace.
Be sure your javascript and CSS curly braces are surrounded by whitespace:

```smarty
var data = { "time": obj.ts };
```