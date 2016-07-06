Tag {extends}
=============

`{extends}` tags are used in child templates in template inheritance for extending parent templates.
The `{extends}` tag must be on before any block.
Also if a child template extends a parent template with the `{extends}` tag it may contain only `{block}` tags. Any other template content is ignored.

### {extends}

```smarty
{extends 'parent.tpl'}
```

### {block}

```smarty
{block 'bk2'}content 2{/block}
```

### {use}

Import the blocks defined in another file. Specifying blocks in this template will override those from the other file.

```smarty
{use 'blocks.tpl'} merge blocks from blocks.tpl template

{block 'alpha'} rewrite block alpha from blocks.tpl template, if it exists
   ...
{/block}
```

### {parent}

Uses the code from the block as defined in the parent.

```smarty
{extends 'parent.tpl'}

{block 'header'}
  content ...
  {parent}  pase code from block 'header' from parent.tpl
  content ...
{/block}
```

### {paste}

Pastes the code of any block

```smarty
{block 'b1'}
    ...
{/block}

{block 'b2'}
    ...
    {paste 'b1'} paste code from b1
{/block}

```

### {$.block}

Checks if clock exists

```smarty
{if $.block.header}
    block header exists
{/if}
```
