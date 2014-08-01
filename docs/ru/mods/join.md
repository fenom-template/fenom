Modifier split
==============

Join array elements with a string.

```
{$string|join:$delimiter = ","}
```

Returns an array of strings, each of which is a substring of `$string` formed by splitting it on boundaries formed by the string `$delimiter`.

```smarty
{var $fruits1 = ["banana", "apple", "pear"]}
{$fruits1|join} output banana, apple, pear
{$fruits1|join:" is not "} output banana is not apple is not pear
```