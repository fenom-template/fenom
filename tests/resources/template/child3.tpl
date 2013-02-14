{extends "child2.tpl"}

{block blk1}
<b>blk1.{$a} (overwritten)</b>
{/block}

{block blk3}
<b>blk3.{$a}</b>
{/block}