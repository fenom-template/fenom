Tag {switch}
============

The `{switch}` tag is similar to a series of `{if}` statements on the same expression.
In many occasions, you may want to compare the same variable (or expression) with many different values,
and execute a different piece of code depending on which value it equals to. This is exactly what the `{switch}` tag is for.

Tag `{switch}` accepts any expression. But `{case}` accepts only static scalar values or constants.

```smarty
{switch <condition>}
{case <value1>}
    ...
{case <value2>, <value3>, ...}
    ...
{case <value3>}
    ...
{case default, <value1>}
    ...
{/switch}
```

For example,

```smarty
{switch $type}
{case 'new'}
    It is new item
{case 'current', 'new'}
    It is new or current item
{case 'current'}
    It is current item
{case 'new', 'newer'}
    It is new item, again
{case default}
    I don't know the type {$type}
{/switch}
```

if `$type = 'new'` then template output

```
It is new item
It is new or current item
It is new item, again
```