<?php

	function fenom_modifier_view_size($size) {
		$size = intval($size);
		if ($size >= 1073741824) {
			$size = round($size / 1073741824 * 100) / 100 . ' GB';
		}
		elseif ($size >= 1048576) {
			$size = round($size / 1048576 * 100) / 100 . ' MB';
		}
		elseif ($size >= 1024) {
			$size = round($size / 1024 * 100) / 100 . ' KB';
		}
		else {
			$size = $size . ' B';
		}
		return $size;
	}
