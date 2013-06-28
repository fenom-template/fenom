Inheritance algorithm
=====================

Variant #1. Sunny.

| level.2.tpl | b1 | add new block              | `$tpl->block['b1'] = $content;`
| level.2.tpl | b1 | rewrite block              | `$tpl->block['b1'] = $content;`
| level.1.tpl | b1 | skip because block exists  | `if(!isset($tpl->block['b1'])) $tpl->block['b1'] = $content;`
| use.tpl     | b1 | skip because block exists  | `if(!isset($tpl->block['b1'])) $tpl->block['b1'] = $content;`
| use.tpl     | b2 | add new block              | `$tpl->block['b2'] = $content;`
| level.1.tpl | b2 | rewrite block              | `$tpl->block['b2'] = $content;`
| parent.tpl  | b1 | get block from stack
| parent.tpl  | b2 | get block from stack
| parent.tpl  | b3 | get own block
------Result--------
| level.2.tpl | b1 |
| level.1.tpl | b2 |

Variant #2. Сloudy.

| level.2.tpl | b1 | add new block
| level.1.tpl | b1 | skip because block exists
| use.tpl     | b1 | skip because block exists
| use.tpl     | b2 | add new block
| level.1.tpl | b2 | rewrite block
| $parent     | b1 | dynamic extend
------Result--------
| level.2.tpl | b1 |
| level.1.tpl | b2 |

Variant #3. Rain.

Variant #4. Tornado.
