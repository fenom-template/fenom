Tag {if}
========

Tag {if}  have much the same flexibility as PHP [if](http://docs.php.net/if) statements,
with a few added features for the template engine.
All operators, allowed functions and variables are recognized in conditions.

```smarty
{if <expression>}
   {* ...code... *}
{elseif <expression>}
   {* ...code... *}
{else}
   {* ...code... *}
{/if}
```

### {if}

```smarty
{if <expression>}
    {*...some code...*}
{/if}
```

### {elseif}

```smarty
{if <expression1>}
    {*...some code...*}
{elseif <expression2>}
    {*...some code...*}
{/if}
```

### {else}

```smarty
{if <expression>}
    {*...some code...*}
{else}
    {*...some code...*}
{/if}
```