{extends 'extends/75-parent.tpl'}
{block 'child'}
    {macro child_test(v, i)}
        child test - {$v}, i = {$i};<br/>
    {var $i = $i -1}
    {if $i > 0}
        {macro.child_test v=$v i=$i}
    {/if}
    {/macro}

    child call: <br/>
    {macro.child_test v = 'ok' i = 5}
{/block}