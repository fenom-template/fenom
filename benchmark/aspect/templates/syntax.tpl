<html>
<head>
    <title>{$title}</title>
</head>
<body>

{* this is a comment *}

Simple manipulations

{$item}
{$data[4]}
{$data.bar}
{*$data[bar]*}
{$data["bar"]}
{$data['bar']}
{$data.$bar_key}
{$data.obj->value}
{$data.obj->method()}

Many other combinations are allowed

{$data.foo.bar}
{$data.foo.$baz_key.$foo_key}
{$data.foo.$bar_key}
{$data.$foo_key.bar}
{$data[5].bar}
{$data[5].$bar_key}
{$data.foo.$baz_key.ls[4]}
{$data.obj->methodArgs($baz_key, 2, 2.3, "some string", $bar_key)} <-- passing parameters
{*"foo"*}


Math and embedding tags:

{$x+$y}                             // will output the sum of x and y.
{$data[$x+3]}
{$item=4+3}                  // tags within tags
{$data[4]=4+3}
{$item="this is message"}  // tags within double quoted strings


Short variable assignment:

{$item=$y+2}
{$item = strlen($bar_key)}               // function in assignment
{$item = intval( ($x+$y)*3 )}       // as function parameter
{$data.bar=1}                        // assign to specific array element
{$data.foo.bar="data.foo.bar: tpl value"}

Smarty "dot" syntax (note: embedded {} are used to address ambiguities):

{$data.foo.baz.foo}
{$data.foo.$baz_key.foo}
{$data[$y+4].foo}
{$data.foo[$data.baz_key].foo}

Object chaining:

{$data.obj->getSelf($x)->method($y)}

Direct PHP function access:

{strlen("hi!")}


{if $logged_in}
Welcome, <span style="color:{$fontColor}">{$name}!</span>
{else}
hi, {$user.name}
{/if}

Embedding Vars in Double Quotes

{mailto address=$user.email text="test $item test"}
{mailto address=$user.email text="test $foo_key test"}
{mailto address=$user.email text="test {$data[4]} test"}
{*mailto address=$user.email text="test {$data[barz]} test"*}
{mailto address=$user.email text="test $item.bar test"}
{mailto address=$user.email text='test {$data.barz} test'}
{mailto address=$user.email text="test {$data.barz} test"}
{mailto address=$user.email text="test {$data.barz} test"|upper}
{mailto address=$user.email text="test {$data.barz|upper} test"}


will replace $tpl_name with value
{include file="subdir/$tpl_name.tpl"}

does NOT replace $tpl_name
{mailto address=$user.email text="one,two"}

{include file="{$data.tpl_name}.tpl"}

Math

some more complicated examples

{$data[2] - $data.obj->num * !$data.foo.baz.ls[4] - 3 * 7 % $data[2]}

{if $data[2] - $data.obj->num * $data.foo.baz.ls[4] - 3 * 7 % $data[2]}
    {$data.barz|truncate:"{$data[2]/2-1}"}
    {$data.barz|truncate:($data[2]/2-1)}
{/if}

Escaping Smarty Parsing

<script>
    // the following braces are ignored by Smarty
    // since they are surrounded by whitespace
    function foobar() {
        alert('foobar!');
    }
    // this one will need literal escapement
    {literal}
    function bazzy() {alert('foobar!');}
    {/literal}
</script>


name:  {$user.name}<br />
email: {$user.email}<br />

Modifier examples

apply modifier to a variable
{$user.name|upper}

modifier with parameters
{$user.name|truncate:40:"..."}

apply modifier to a function parameter
{mailto address=$user.email text=$user.name|upper}

with parameters
{mailto address=$user.email text=$user.name|truncate:40:"..."}

apply modifier to literal string
{*"foobar"|upper*}

using date_format to format the current date
{$smarty.now|date_format:"%Y/%m/%d"}

apply modifier to a custom function
{*mailto|upper address="smarty@example.com"*}

Foreach
{foreach $contacts as $contact}
    {foreach $contact as $key => $value}
        {$key}: {$value}
    {foreachelse}
        no items
    {/foreach}
{/foreach}

If condition
{if isset($user.name) && $user.name == 'yandex'}
do something
{elseif $user.name == $data.foo.bar}
do something2
{/if}

{*switch $item}
    {case 1}
        item 1
        {break}
    {case 2}
        item 2
        {break}
    {default}
        on item
{/switch*}

{if is_array($data.foo) && count($data.foo) > 0}
do a foreach loop
{/if}


</body>
</html>