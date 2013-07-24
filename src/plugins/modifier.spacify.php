<?php

	function fenom_modifier_spacify($string, $spacify_char = ' ') {
		return implode($spacify_char, preg_split('//', $string, -1, PREG_SPLIT_NO_EMPTY));
	}

?>