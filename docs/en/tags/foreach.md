Tag {foreach}
=============

The tag `{foreach}` construct provides an easy way to iterate over arrays and ranges.

```smarty
{foreach $list as [$key =>] $value [index=$index] [first=$first] [last=$last]}
   {* ...code... *}
   {break}
   {* ...code... *}
   {continue}
   {* ...code... *}
{foreachelse}
   {* ...code... *}
{/foreach}
```

### {foreach}

On each iteration, the value of the current element is assigned to `$value` and the internal array pointer is
advanced by one (so on the next iteration, you'll be looking at the next element).

```smarty
{foreach $list as $value}
 <div>{$value}</div>
{/foreach}
```

The next form will additionally assign the current element's key to the `$key` variable on each iteration.

```smarty
{foreach $list as $key => $value}
 <div>{$key}: {$value}</div>
{/foreach}
```

Gets the current array index, starting with zero.

```smarty
{foreach $list as $value}
 <div>№{$value@index}: {$value}</div>
{/foreach}

or

{foreach $list as $value index=$index}
 <div>№{$index}: {$value}</div>
{/foreach}
```

Detect first iteration:

```smarty
{foreach $list as $value}
 <div>{if $value@first} first item {/if} {$value}</div>
{/foreach}

or

{foreach $list as $value first=$first}
 <div>{if $first} first item {/if} {$value}</div>
{/foreach}
```

`$first` is `TRUE` if the current `{foreach}` iteration is first iteration.

Detect last iteration:

```smarty
{foreach $list as $value}
 <div>{if $value@last} last item {/if} {$value}</div>
{/foreach}

or

{foreach $list as $value last=$last}
 <div>{if $last} last item {/if} {$value}</div>
{/foreach}
```

`$last` is set to `TRUE` if the current `{foreach}` iteration is last iteration.

### {break}

Tag `{break}` aborts the iteration.

### {continue}

Tag `{continue}` leaves the current iteration and begins with the next iteration.

### {foreachelse}

`{foreachelse}` is executed when there are no values in the array variable.

```smarty
{set $list = []}
{foreach $list as $value}
 <div>{if $last} last item {/if} {$value}</div>
{foreachelse}
 <div>empty</div>
{/foreach}
```

`{foreachelse}` does not support tags `{break}` and `{continue}`.