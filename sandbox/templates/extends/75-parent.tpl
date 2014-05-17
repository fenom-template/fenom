{macro parent_test(v, i)}
    parent test - {$v}, i = {$i};<br/>
{var $i = $i -1}
{if $i > 0}
    {macro.parent_test v=$v i=$i}
{/if}
{/macro}

{block 'child'}{/block}

parent call:<br/>
{macro.parent_test v = 'ok' i = 5} <br/>