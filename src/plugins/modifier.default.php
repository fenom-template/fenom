<?php
	function fenom_modifier_default($string, $default = '') {
		if ($string === FALSE || $string === NULL || $string === '') {
			return $default;
		}
		return $string;
	}
?>
