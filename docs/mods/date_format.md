Modifier date_format [RU]
=========================

Модификатор позволят вывести дату в произвольном формате, согласно форматированию [strftime()](http://docs.php.net/strftime).
Модификатор принимает timestamp или строку, которую можно преобразовать через [strtotime()](http://docs.php.net/strtotime).
Формат по умолчанию: `%b %e, %Y`.


**[Допустимые квантификаторы](http://docs.php.net/strftime#refsect1-function.strftime-parameters) в формате даты**


```smarty
{var $ts = time()}

{$ts|date_format:"%Y/%m/%d %H:%M:%s"} output like 2013/02/08 21:01:43
{$ts|date_format:"-1 day"} output like 2013/02/07 21:01:43

{var $date = "2008-12-08"}

{$ts|date_format:"%Y/%m/%d %H:%M:%s"} output like 2008/12/08 00:00:00
```