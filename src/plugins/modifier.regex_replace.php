<?php

	function fenom_modifier_regex_replace($string, $pattern, $replace) {
		if (preg_match('~^([a-z]*)(.)(.*)\2([a-z]*)$~si', str_replace("\x00", '\x00', $pattern), $q)) {
			return preg_replace($q[2] . $q[3] . $q[2] . str_replace('e', '', $q[1] . $q[4]), $replace, $string);
		}
		else {
			return FALSE;
		}
	}
?>
