{macro pills($title, $items, $active)}
    <div class="header">
        <ul class="nav nav-pills pull-right" role="tablist">
            {foreach $items as $code => $item}
                {if $code == $active}
                    <li role="presentation" class="{$code}"><a href="">{$item.name}</a></li>
                {else}
                    <li role="presentation" class="active {$code}"><a href="{$item.url}">{$item.name}</a></li>
                {/if}
            {/foreach}
        </ul>
        <h3 class="text-muted">{$title}</h3>
    </div>
{/macro}