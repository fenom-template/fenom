<?php
	
	function fenom_modifier_count_sentences($string) {
		return preg_match_all('~\S\.(?!\w)~', $string, $match);
	}

?>
