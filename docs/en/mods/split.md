Modifier split
==============

Split a string by string

```
{$string|split:$delimiter = ","}
```

Returns an array of strings, each of which is a substring of `$string` formed by splitting it on boundaries formed by the string `$delimiter`.

```smarty
{var $fruits1 = "banana,apple,pear"|split}
$fruits1 is array ["banana", "apple", "pear"]

{var $fruits2 = "banana,apple,pear"|split:',apple,'}
$fruits2 is array ["banana", "pear"]
```