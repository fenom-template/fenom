Tag {capture}
=============



```smarty
{capture $var}
This content will be captured into variable $var
{/capture}
```


```smarty
{capture|strip_tags $var}
This content will be captured into variable $var and all tags will be stripped
{/capture}
```