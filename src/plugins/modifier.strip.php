<?php

	function fenom_modifier_strip($text, $replace = ' ') {
		return preg_replace('~\s+~', $replace, $text);
	}

?>
