Tag {ignore}
===========

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

## Tag option `:ignore`

You can use the `:ignore` option on any block tag to ignore Fenom syntax inside the block:

```smarty
{if:ignore $cdn}
    var item = {cdn: "//example.com/"};
{/if}
```

This is useful when the content contains text that looks like Fenom tags but should not be parsed.

### Nested tags with `:ignore`

When using `:ignore` on nested tags (like `foreach:ignore`), the ignore mode is properly restored when the inner tag closes:

```smarty
{foreach $items as $item}
    {foreach:ignore $item.nested as $n}
        {$n.name} {* Will output literally *}
        {literal}{$n.name}{/literal} {* Will output the value *}
    {/foreach}
{/foreach}
```

The `:ignore` option works correctly with all block tags including `{foreach}`, `{if}`, `{for}`, etc.

{ignore} tags are normally not necessary, as Fenom ignores delimiters that are surrounded by whitespace.
Be sure your javascript and CSS curly braces are surrounded by whitespace:

```smarty
var data = { "time": obj.ts };
```