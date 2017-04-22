{*<div class='bread'>*}
    {*<ul>*}
        {*<li><a href='/'>Главная</a><li>*}
            {*{foreach 1..$arr|length as $counter index=$i first=$first last=$last}*}
            {*{if !$first}*}
        {*<li class='delim'>/</li>*}
        {*<li>{$last}*}
            {*{if !$last}*}
                {*<a href='{$arr[$i]['hidden_url']}'>{else}<span>*}
            {*{/if}*}
            {*{$arr[$i]['name']}{if !$last}</a>{else}</span>{/if}</li>{/if}*}
        {*{/foreach}*}
    {*</ul>*}
{*</div>*}

=== {(1..3)|length} ===

{foreach 1..3 as $c index=$i first=$first last=$last}
    {$i}: {$last}
{/foreach}

<div class='bread'>
    <ul>
        <li><a href='/'>Главная</a><li>
        {foreach $arr as $item first=$first last=$last}
            {if !$first}
                <li class='delim'>/</li>
                <li>{$last}
                    {if $last}
                    <span>{$item.name}</span>
                    {else}
                    <a href='{$item.hidden_url}'>{$item.name}</a>
                    {/if}
                </li>
            {/if}
        {/foreach}
    </ul>
</div>