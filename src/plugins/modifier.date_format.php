<?php
	
	require_once __DIR__.'shared.make_timestamp';
	function fenom_modifier_date_format($string, $format = "%b %e, %Y", $default_date = null) {
		if (substr(PHP_OS, 0, 3) == 'WIN') {
			$hours       = strftime('%I', $string);
			$short_hours = ($hours < 10) ? substr($hours, -1) : $hours;
			$_win_from   = array('%e', '%T', '%D', '%l');
			$_win_to     = array('%#d', '%H:%M:%S', '%m/%d/%y', $short_hours);
			$format      = str_replace($_win_from, $_win_to, $format);
		}
		if ($string != '') {
			return strftime($format, fenom_make_timestamp($string));
		}
		elseif (isset($default_date) && $default_date != '') {
			return strftime($format, fenom_make_timestamp($default_date));
		}
		else {
			return;
		}
	}
