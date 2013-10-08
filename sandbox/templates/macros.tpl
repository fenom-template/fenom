{macro factorial(num)}
{if $num}
    {$num} {macro.factorial num=$num-1} {$num}
{/if}
{/macro}