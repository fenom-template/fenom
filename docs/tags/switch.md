Tag {switch}
============

```smarty
{switch <condition>}
{case <value1>}
    ...
{case <value2>, <value3>, ...}
    ...
{case <value3>}
    ...
{default}
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
{case 'new'}
    It is new item, again
{default}
    I don't know the type {$type}
{/switch}
```

If `$type = 'new'` template outputs

```
It is new item
It is new or current item
It is new item, again
```