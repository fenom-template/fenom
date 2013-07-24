<?php

	function fenom_modifier_count_paragraphs($string) {

		return sizeof(preg_split('~[\r\n]+~', $string));
	}

