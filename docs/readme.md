Documentation
=============

**Please, help translate documentation to english or fix typos. [Read more](./helpme.md).**

### Fenom

* [Quick start](./start.md)
* [Usage](./start.md#install-fenom)
* [Framework adapters](./adapters.md)
* [For developers](./dev/readme.md)
* [Configuration](./configuration.md)
* [Syntax](./syntax.md)
* [Operators](./operators.md)

***

### Tags

[Usage](./syntax.md#tags)

* [var](./tags/var.md) — define variable
* [if](./tags/if.md), `elseif` and `else` — conditional statement
* [foreach](./tags/foreach.md), `foreaelse`, `break` and `continue` — traversing items in an array or object
* [for](./tags/for.md), `forelse`, `break` and `continue` — loop statement
* [switch](./tags/switch.md), `case`, `default` —
* [cycle](./tags/cycle.md) — cycles on an array of values
* [include](./tags/include.md), `insert` —  includes and evaluates the specified template
* [extends](./tags/extends.md), `use`, `block` and `parent` — template inheritance
* [filter](./tags/filter.md) — apply modifier on a block of template data
* [ignore](./tags/ignore.md) — ignore Fenom syntax
* [macro](./tags/macro.md) and `import` — template functions
* [autoescape](./tags/autoescape.md) — escape template fragment
* [raw](./tags/raw.md) — unescape template fragment
* or [add](./ext/extend.md#add-tags) yours


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
* [String concatenation operator](./operators.md#string-operator) — `$str1 ~ $str2`
* [Ternary operators](./operators.md#ternary-operators) — `$a ? $b : $c`, `$a ! $b : $c`, `$a ?: $c`, `$a !: $c`
* [Check operators](./operators.md#check-operators) — `$var?`, `$var!`
* [Test operator](./operators.md#test-operators) — `is`, `is not`
* [Containment operator](./operators.md#containment-operator) — `in`, `not in`

***

### Extends

* [Extend Fenom](./ext/extend.md)
* [Add-ons](./ext/extensions.md)
