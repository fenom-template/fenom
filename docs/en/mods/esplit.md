Modifier esplit
===============

Split string by a regular expression.
[Read more](http://www.php.net/manual/en/reference.pcre.pattern.syntax.php) about regular expression.

```
{$string|esplit:$pattern = '/,\s*/'}
```

My default modifier split string by comma with spaces.

```smarty
{var $fruits1 = "banana, apple, pear"|esplit}
$fruits1 is array ["banana", "apple", "pear"]

{var $fruits2 = "banana; apple; pear"|esplit:'/;\s/'} is ["banana", "apple", "pear"]
$fruits2 is array ["banana", "apple", "pear"] too
```