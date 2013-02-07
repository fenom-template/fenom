Модификаторы
============

## Оператор if

Реализация "оператора if":http://php.net/if из PHP

```smarty
{if <expresion>}
   {*...some code...*}
{/if}
```

Код, расположенный в теге `if` будет выполнен/выведен если выражение @<expression>@ возвращает значение приводимое к `TRUE`
Использование блока `elseif`

```smarty
{if <expresion1>}
   {*...some code...*}
{elseif <expresion2>}
   {*...some code...*}
{/if}
```

Код, расположенный после тега `elseif` будет выполнен/выведен, если выражение @<expression1>@ вернуло значение приводимое к `FALSE`, **<expression2>** - приводимое к `TRUE`

Использование блока `else`

```smarty
{if <expresion>}
   {*...some code...*}
{else}
   {*...some code...*}
{/if}
```

Код, расположенный после тега `else` будет выполнен/выведен, если выражение **<expression>** вернуло значение приводимое к `FALSE`

В тестируемых выражениях могут быть использованы "логические операторы":http://www.php.net/manual/en/language.operators.logical.php , что позволяет обрабатывать сочетания нескольких условий.

## Оператор foreach

Реализация оператора [foreach в PHP](http://docs.php.net/foreach)
Общий синтаксис:

```smarty
{foreach <array> as <key_var> => <value_var> index=<index_var> first=<first_flag> last=<last_flag>}
    {*...some code...*}
    {if <expression1>}
        {break}
    {/if}
    {*...some code...*}
    {if <expression2>}
        {continue}
    {/if}
    {*...some code...*}
{foreachelse}
    {*...some code...*}
{/foreach}
```

(!) <index_var>, <first_flag>, <last_flag>, <key_var> и <value_var> могут быть только переменные (допускаются вложенности на подобие $a.b.c, но массив $a.b должен быть объявлен).

Перебор значений массива $list

```smarty
{foreach $list as $value}
 <div>{$value}</div>
{/foreach}
```

Перебор ключей и значений массива $list

```smarty
{foreach $list as $key => $value}
 <div>{$key}: {$value}</div>
{/foreach}
```

Получение номера (индекса) итерации

```smarty
{foreach $list as $value index=$index}
 <div>№{$index}: {$value}</div>
{/foreach}
```

Определение первого элемента

```smarty
{foreach $list as $value first=$first}
 <div>{if $first} first item {/if} {$value}</div>
{/foreach}
```

Переменная `$first` будет иметь значение `TRUE`, если текущая итерация является первой.

Определение последнего элемента

```smarty
{foreach $list as $value last=$last}
 <div>{if $last} last item {/if} {$value}</div>
{/foreach}
```

Переменная `$last` будет иметь значение `TRUE`, если текущая итерация является последней.
Использование @last@ замедляет работу цикла и требует от `$list` быть *countable*. Если есть возможность используйте `first` параметр.

### Вложенные теги

* `{break}` используется для выхода из цикла до достижения последней итерации. Если в цикле встречается тег `{break}`, цикл завершает свою работу, и далее выполняется код, следующий сразу за блоком цикла
* `{continue}` используется для прерывания текущей итерации. Если в цикле встречается тег `{continue}`, часть цикла, следующая после тега, не выполняется, и начинается следующая итерация. Если текущая итерация была последней, цикл завершается.
* `{foreachelse}` ограничивает код, который должен быть выполнен, если итерируемый объект пуст.