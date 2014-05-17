Modifier strip
==============

This replaces all repeated spaces and tabs with a single space, or with the supplied string.

```smarty
{"   one    two   "|strip} => 'one two'
```

Optional boolean parameter tell to the modifier strip also newline

```smarty
{"    multi
    line
    text    "|strip:true} => 'multi line text'
```
