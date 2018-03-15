Modifier date_format
====================

This formats a date and time into the given [strftime()](http://docs.php.net/strftime) format.
Dates can be passed to Fenom as unix timestamps, DateTime objects or any string made up of month day year, parsable by [strftime()](http://docs.php.net/strftime).
By default format is: `%b %e, %Y`.

```smarty
{var $ts = time()}

{$ts|date_format:"%Y/%m/%d %H:%M:%S"} outputs 2013/02/08 21:01:43
{$ts|date_format:"-1 day"} outputs 2013/02/07 21:01:43

{var $date = "2008-12-08"}

{$ts|date_format:"%Y/%m/%d %H:%M:%S"} outputs 2008/12/08 00:00:00
```

[Allowed quantificators](http://docs.php.net/strftime#refsect1-function.strftime-parameters) in **date_format**
