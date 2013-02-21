Tag {extends}
=============

Тег {extends} реализует наследование шаблонов

### {extends}

```smarty
{extends 'parent.tpl'}
```

```smarty
{extends $parent_tpl}
```

### {block}

```smarty
{block bk1}content 1{/block}

{block 'bk2'}content 2{/block}

{block "bk{$number}"}content {$number}{/block}

{if $condition}
    {block "bk-if"}content, then 'if' is true{/block}
{else}
    {block "bk{$fail}"}content, then 'if' is false{/block}
{/if}
```

### {use}


```smarty
{use 'blocks.tpl'}

{if $theme.extended?}
    {use $theme.extended}
{/if}
```


### {parent}

```smarty
{block 'block1'}
  content ...
  {parent}
  content ...
{/block}
```

### Perfomance

