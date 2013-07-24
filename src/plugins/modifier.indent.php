<?php


	function fenom_modifier_indent($string, $chars = 4, $char = ' ') {
		return preg_replace('~^~m', str_repeat($char, $chars), $string);
	}

?>
