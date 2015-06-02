{* template:test.tpl *}
{macro test($break = false)}
    Test macro recursive
{if $break?}
    {macro.test break = true}
{/if}
{/macro}