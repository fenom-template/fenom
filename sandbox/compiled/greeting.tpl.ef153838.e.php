<?php 
/** Fenom template 'greeting.tpl' compiled at 2013-09-02 17:37:39 */
return new Fenom\Render($fenom, function ($tpl) {
?>

A1
<?php
/* greeting.tpl:4: {mc.factorial num=10} */
 $_tpl4154309674_1 = $tpl->exchangeArray(array("num" => 10));
?><?php
/* macros.tpl:2: {if $num} */
 if($tpl["num"]) { ?>
    <?php
/* macros.tpl:3: {$num} */
 echo $tpl["num"]; ?> <?php
/* macros.tpl:3: {macro.factorial num=$num-1} */
 $_tpl2531688351_1 = $tpl->exchangeArray(array("num" => $tpl["num"] - 1));
$tpl->getMacro("factorial")->__invoke($tpl);
$tpl->exchangeArray($_tpl2531688351_1); /* X */ unset($_tpl2531688351_1); ?> <?php
/* macros.tpl:3: {$num} */
 echo $tpl["num"]; ?>
<?php
/* macros.tpl:4: {/if} */
 } ?>
<?php
$tpl->exchangeArray($_tpl4154309674_1); /* X */ unset($_tpl4154309674_1); ?>
A2<?php
}, array(
	'options' => 0,
	'provider' => false,
	'name' => 'greeting.tpl',
	'base_name' => 'greeting.tpl',
	'time' => 1378125225,
	'depends' => array (
  0 => 
  array (
    'macros.tpl' => 1378129033,
    'greeting.tpl' => 1378125225,
  ),
),
	'macros' => array(
		'factorial' => function ($tpl) {
?><?php
/* macros.tpl:2: {if $num} */
 if($tpl["num"]) { ?>
    <?php
/* macros.tpl:3: {$num} */
 echo $tpl["num"]; ?> <?php
/* macros.tpl:3: {macro.factorial num=$num-1} */
 $_tpl2531688351_1 = $tpl->exchangeArray(array("num" => $tpl["num"] - 1));
$tpl->getMacro("factorial")->__invoke($tpl);
$tpl->exchangeArray($_tpl2531688351_1); /* X */ unset($_tpl2531688351_1); ?> <?php
/* macros.tpl:3: {$num} */
 echo $tpl["num"]; ?>
<?php
/* macros.tpl:4: {/if} */
 } ?>
<?php
}),

        ));
