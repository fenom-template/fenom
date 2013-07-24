<?php

	function fenom_modifier_natint($string) {
		$int = intval($string);
		return $int > 0 ? $int : 0;
	}
