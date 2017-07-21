Тег {foreach}
=============

Тег `foreach` предоставляет простой способ перебора массивов. 
`Foreach` работает только с массивами, объектами и интервалами.

```smarty
{foreach $list [as [$key =>] $value] [index=$index] [first=$first] [last=$last]}
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

Перебор значений массива $list:

```smarty
{foreach $list as $value}
 <div>{$value}</div>
{/foreach}

{foreach 1..7 as $value} {* так же хорошо работает и с интервалами *}
 <div>№{$value}</div>
{/foreach}
```

Перебор ключей и значений массива $list:

```smarty
{foreach $list as $key => $value}
 <div>{$key}: {$value}</div>
{/foreach}
```


Получение номера (индекса) итерации, начиная с 0

```smarty
{foreach $list as $value}
 <div>№{$value@index}: {$value}</div>
{/foreach}

или

{foreach $list as $value index=$index}
 <div>№{$index}: {$value}</div>
{/foreach}
```

Определение первой итерации:

```smarty
{foreach $list as $value}
 <div>{if $value@first} first item {/if} {$value}</div>
{/foreach}

или

{foreach $list as $value first=$first}
 <div>{if $first} first item {/if} {$value}</div>
{/foreach}
```

Переменная `$value@first` будет иметь значение **TRUE**, если текущая итерация является первой.
Определение последней интерации:

```smarty
{foreach $list as $value}
 <div>{if $value@last} last item {/if} {$value}</div>
{/foreach}

или

{foreach $list as $value last=$last}
 <div>{if $last} last item {/if} {$value}</div>
{/foreach}
```

Переменная `$value:last` будет иметь значение **TRUE**, если текущая итерация является последней.

**Замечание:**
Использование `last` требует от `$list` быть **countable**.

### {break}

Тег `{break}` используется для выхода из цикла до достижения последней итерации. 
Если в цикле встречается тег `{break}`, цикл завершает свою работу, и далее, выполняется код, следующий сразу за блоком цикла

### {continue}

Тег `{continue}` используется для прерывания текущей итерации. 
Если в цикле встречается тег `{continue}`, часть цикла, следующая после тега, не выполняется, и начинается следующая итерация. 
Если текущая итерация была последней, цикл завершается.

### {foreachelse}

Тег {foreachelse} ограничивает код, который должен быть выполнен, если итерируемый объект пуст.

```smarty
{var $list = []}
{foreach $list as $value}
 <div>{if $last} last item {/if} {$value}</div>
{foreachelse}
 <div>empty</div>
{/foreach}
```

В блоке `{foreachelse}...{/foreach}` использование `{break}`, `{continue}` выбросит исключение `Fenom\CompileException` при компиляции
