<?php

	function fenom_modifier_strip_tags($string, $replace_with_space = TRUE) {
		if ($replace_with_space) {
			return preg_replace('~<[^>]*?>~', ' ', $string);
		}
		else {
			return strip_tags($string);
		}
	}
?>