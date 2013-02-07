<?php

$a = new ArrayIterator(str_split(str_pad("", 4, "a")));

$_t = null;
$t = microtime(1);
reset($a);
while($v = current($a)) {
	$k = key($a);
	next($a);
	if(key($a) === null) {
		var_dump("last");
	}
	//var_dump($v);
}
print_r("\n\nWhile: ".(microtime(1) - $t)."\n\n");

$t = microtime(1);
$c = count($a);
foreach($a as $k => $v) {
	if(!--$c) var_dump("last");
	//var_dump($v);
}

print_r("\n\nforeach + count: ".(microtime(1) - $t)."\n\n");

$t = microtime(1);
reset($a);
while(list($k, $v) = each($a)) {
	if(key($a) === null) {
		var_dump("last");
	}
	/*next($a);
	if(key($a) === null) {
		var_dump("last");
	}*/
	//var_dump($v);
}
print_r("\neach: ".(microtime(1) - $t)."\n\n");

$t = microtime(1);
foreach($a as $k => $v) {
	//var_dump($v);
}

print_r("\n\nforeach: ".(microtime(1) - $t)."\n\n");
?>
