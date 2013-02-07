{*
	data.value = "TXT value";
	data.arr.dot.4.retval = "Four";
	data.arr.dot.5 = "FiVe";
	data.arr.i = 4;
	data.arr.retval = "Retval key";
	data.set->get = "Get props";
	data.iam->getName(...) == "return Name";
	data.iam->getFormat(...)== "return Format";
	data.num = 777;
	data.k = 0;
	data.ls = array("a" => "lit A", "c" => "lit C", "d" => "lit D");

*}
Hello <b>world</b>!
My Name is {$data.value}...
Yeah

Hello <b>world</b>!
My Name is {$data.arr.dot|json_encode|lower}...
Yeah

Hello <b>world</b>, {$data.value|upper}!
My Name is {$data.arr[dot].5|upper}...
My Name is {$data.arr.dot[ $data.arr.i|round ]."retval"|upper}...
My Name is {$data.arr."retval"|upper}...
Yeah

Hello <b>world</b>!
My Name is {$data.set->get|upper}...
Yeah

Hello <b>world</b>!
My Name is {$data.iam->getName()|upper}...
Your Name is {$data.you->getFormat(1, 0.4, "%dth", 'grade', $data.arr[dot].5|lower)|upper}?
Yeah


Hello <b>world</b>!
{if isset($data.iam) && !empty($data.set->get)}
My Name is {$data.set->get}...
{/if}
Yeah

Hello <b>world</b>!
{if $data.num >= 5 && $data.k++ || foo($data.value) && !bar($data.num) + 3 || $data.k?}
My Name is {$data->user|upper}...
{/if}
Yeah

Hello <b>world</b>!
{foreach from=$data.ls key=k item=e}
My Name is {$e|upper} ({$k})...
{/foreach}
Yeah

Hello <b>world</b>!
{switch $data.num}
	{case "dotted"}
dotted lines<br>
	{break}
	{case 777}
numeric lines<br>
	{break}
	{case $data[arr]["dot"].4.retval|upper}
lister<br>
	{break}
{/switch}
Yeah

Hello <b>world</b>!
{* if !empty($data.num) *}
{if $data.num?}
	dotted lines<br>
{elseif $data[arr]["dot"].4.retval|lower}
	lister<br>
{/if}
Yeah

Check system variable<br/>
Current timestamp {$aspect.now}...<br/>
$_GET {$aspect.get.item}...<br/>
$_POST {$aspect.post.myval|upper}...<br/>
$_COOKIES {$aspect.cookies["uid"]}...<br/>
$_REQUEST {$aspect.request?}...<br/>
Consts {$data.number|round:$aspect.const.PHP_INT_MAX}...<br/>
Ok


Hello <b>world</b>!
{for from=1 to=$data.ls|count}
<div>Go</div>
{/for}
Yeah