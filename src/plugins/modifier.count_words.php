<?php

	function fenom_modifier_count_words($string) {
		return sizeof(preg_grep('~[a-zA-Z0-9\\x80-\\xff]~', preg_split('~\s+~', $string)));
	}

?>
