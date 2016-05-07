Documentation
=============

### Fenom

* [Quick start](./start.md)
* [Usage](./start.md#install-fenom)
* [Framework adapters](./adapters.md)
* [For developers](./dev/readme.md)
* [Configuration](./configuration.md)
* [Syntax](./syntax.md)
    * [Variables](./syntax.md#Variables)
    * [Values](./syntax.md#Values)
    * [Arrays](./syntax.md#Arrays)
    * [Operators](./operators.md)
    * [Modificators](./syntax.md#Modificators)
    * [Tags](./syntax.md#Tags)
    * [Tag configuration](./syntax.md#Tag-configuration)

***

### Tags

[Usage](./syntax.md#tags)

* [set](./tags/set.md), [add](./tags/set.md#add) and `var` — define variables
* [if](./tags/if.md), [elseif](./tags/if.md#elseif) and [else](./tags/if.md#else) — conditional statement
* [foreach](./tags/foreach.md), [foreaelse](./tags/foreach.md#foreaelse),
  [break](./tags/foreach.md#break) and [continue](./tags/foreach.md#continue) — traversing items in an array or object
* [switch](./tags/switch.md), [case](./tags/switch.md#case) — complex condition statement
* [cycle](./tags/cycle.md) — cycles on an array of values
* [include](./tags/include.md), [insert](./tags/include.md#insert) —  includes and evaluates the specified template
* [extends](./tags/extends.md), [use](./tags/extends.md#use),
  [block](./tags/extends.md#block), [parent](./tags/extends.md#parent) and [paste](./tags/extends.md#paste) — template inheritance
* [filter](./tags/filter.md) — apply modifier on a block of template data
* [ignore](./tags/ignore.md) — ignore Fenom syntax
* [macro](./tags/macro.md) and [import](./tags/macro.md#import) — template functions
* [autoescape](./tags/autoescape.md) — escape template fragment
* [raw](./tags/raw.md) — unescape template fragment
* [unset](./tags/unset.md) — unset a given variables
* or [add](./ext/extend.md#add-tags) yours

Deprecated tags

* [for](./tags/for.md), `forelse`, `break` and `continue` — loop statement

***

### Modifiers

[Usage](./syntax.md#modifiers)

* [upper](./mods/upper.md) aka `up` — convert to uppercase a string
* [lower](./mods/lower.md) aka `low` — convert to lowercase a string
* [date_format](./mods/date_format.md) - format date, timestamp via strftime() function
* [date](./mods/date.md) - format date, timestamp via date() function
* [truncate](./mods/truncate.md) — truncate thee string to specified length
* [escape](./mods/escape.md) aka `e` — escape the string
* [unescape](./mods/unescape.md) — unescape the string
* [strip](./mods/strip.md) — remove extra whitespaces
* [length](./mods/length.md) — calculate length of string, array, object
* [in](./mods/in.md) — find value in string or array
* [match](./mods/match.md) — match string against a pattern.
* [ematch](./mods/ematch.md) — perform a regular expression match.
* [replace](./mods/replace.md) — replace all occurrences of the search string with the replacement string.
* [ereplace](./mods/ereplace.md) — perform a regular expression search and replace.
* [split](./mods/split.md) — split a string by string.
* [esplit](./mods/esplit.md) — split string by a regular expression.
* [join](./mods/join.md) — join array elements with a string.
* allowed functions: `json_encode`, `json_decode`, `count`, `is_string`, `is_array`, `is_numeric`, `is_int`, `is_object`,
`strtotime`, `gettype`, `is_double`, `ip2long`, `long2ip`, `strip_tags`, `nl2br`
* or [add](./ext/extend.md#add-modifiers) yours

***

### Operators

* [Arithmetic operators](./operators.md#arithmetic-operators) — `+`, `-`, `*`, `/`, `%`
* [Logical operators](./operators.md#logical-operators) — `||`, `&&`, `!$var`, `and`, `or`, `xor`
* [Comparison operators](./operators.md#comparison-operators) — `>`, `>=`, `<`, `<=`, `==`, `!=`, `!==`, `<>`
* [Bitwise operators](./operators.md#bitwise-operators) — `|`, `&`, `^`, `~$var`, `>>`, `<<`
* [Assignment operators](./operators.md#assignment-operators) — `=`, `+=`, `-=`, `*=`, `/=`, `%=`, `&=`, `|=`, `^=`, `>>=`, `<<=`
* [String concatenation operators](./operators.md#string-operators) — `$str1 ~ $str2`, `$str1 ~~ $str2`, `$str1 ~= $str2`
* [Ternary operators](./operators.md#ternary-operators) — `$a ? $b : $c`, `$a ! $b : $c`, `$a ?: $c`, `$a !: $c`
* [Check operators](./operators.md#check-operators) — `$var?`, `$var!`
* [Test operator](./operators.md#test-operator) — `is`, `is not`
* [Containment operator](./operators.md#containment-operator) — `in`, `not in`

***

### Extends

* [Extend Fenom](./ext/extend.md)
* [Add-ons](./ext/extensions.md)
