<?php
use MF\Misc\Tokenizer;
require_once __DIR__ . "/../../lib/Autoload.php";

$tokens = new \MF\Misc\Tokenizer('$data.value[5].dot|json_decode|lower');


$tpl[0] = 'Hello <b>world</b>!
My Name is {$data.value}...
Yeah';

$tpl[1] = 'Hello <b>world</b>!
My Name is {$data.value[label].dot|json_decode|lower|truncate:80:"...":true:null:0.22:$a.keyz|upper}...
Yeah';

$tpl[2] = 'Hello <b>world</b>, {$user.name|upper}!
My Name is {$data.user1.5.$i."return"[ $hello.world|lower ]|upper}...
Yeah';

$tpl[3] = 'Hello <b>world</b>!
My Name is {$data->set->get|upper}...
Yeah';

$tpl[4] = 'Hello <b>world</b>!
My Name is {$iam->getName()|upper}...
Your Name is {$you->getFromat(1, 0.4, "%dth", \'grade\', $global->name.k|lower)|upper}?
Yeah';

$tpl[5] = 'Hello <b>world</b>!
{if isset($data.user) && !empty($data->value)}
My Name is {$data->user|upper}...
{/if}
Yeah';

$tpl[6] = 'Hello <b>world</b>!
{if $data.user >= 5 && $k++ || foo($data->value) && !bar($d) + 3 || $show.go?}
My Name is {$data->user|upper}...
{/if}
Yeah';

$tpl[7] = 'Hello <b>world</b>!
{foreach from=$users."list" key=k item=e}
My Name is {$e|upper} ({$k})...
{/foreach}
Yeah';

$tpl[8] = 'Hello <b>world</b>!
{switch $data->enum}
	{case "dotted"}
		dotted lines<br>
		{break}
	{case 7}
		numeric lines<br>
		{break}
	{case $list|truncate}
		lister<br>
	{break}
{/switch}
Yeah';

$tpl[9] = 'Hello <b>world</b>!
{if $data->enum?}
	dotted lines<br>
{elseif $list|truncate}
	lister<br>
{/if}
Yeah';

$tpl[10] = 'Check system variable<br/>
Current timestamp {$aspect.now}...<br/>
$_GET {$aspect.get.item}...<br/>
$_POST {$aspect.post.myval|upper}...<br/>
$_COOKIES {$aspect.cookies["uid"]}...<br/>
$_REQUEST {$aspect.request?}...<br/>
Consts {$number|round:$aspect.const.PHP_INT_MAX|}...<br/>
Ok';

$tpl[11] = 'Hello <b>world</b>!
{for from=1 to=$e|count}
<div>Go</div>
{/for}
Yeah';

$tpl_ = '
Hello <b>world</b>!
My Name is {$data|upper}...
<script>$.dotted({
	items: "sorted"
});</script> {* comment *}
I have
<ul>
{foreach $items as $key => $item}
	<li>{$item}</li>
{/foreach}
</ul>
';

$template = new MF\Aspect\Template($tpl[7], 'my.tpl');
//var_dump(Tokenizer::decode('case 6:'));
//exit;
echo "\n".$template->getBody()."\n";
//var_dump($template->fetch(array("data" => array("value" => "Yohoho"))));
?>