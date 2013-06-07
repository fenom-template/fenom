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



Error Info (x2) :
Exception: You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near '839621621,839622021)' at line 1
Query: SELECT `image_id`, `filename` FROM `s3_image_version` WHERE `format_id`=1 AND `image_id` IN (,839621621,839622021)
#0 /www/oml.ru/s3/lib/class.db.php(480): Db::parseError('SELECT `image_i...')
#1 /www/oml.ru/s3/forms/class.shop2.product.form.php(225): Db::query('SELECT `image_i...')
#2 /www/oml.ru/s3/lib/class.form.php(2390): Shop2ProductForm->fillControls()
#3 /www/oml.ru/s3/lib/class.form.php(1444): Form->execute()
#4 /www/oml.ru/public/my/s3/data/shop2_product/edit.cphp(44): Form->display(Object(Smarty), 'form.ajax.tpl')
#5 {main}

Place: /www/oml.ru/s3/lib/class.db.php:607
Time: 2013-06-05 03:54:51
Url: http://agyumama.ru/my/s3/data/shop2_product/edit.cphp?shop_id=196421&ver_id=636664&access=u%3B270377&popup=1&product_id=89445221&rnd=9296&xhr=1