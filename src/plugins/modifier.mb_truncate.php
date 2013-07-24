<?php

	function fenom_modifier_mb_truncate($string, $length = 80, $etc = '...', $break_words = FALSE, $middle = FALSE, $encoding = 'UTF-8') {
		if ($length == 0) {
			return '';
		}
		$old = mb_internal_encoding();
		if ($old != $encoding) {
			mb_internal_encoding($encoding);
		}
		if (mb_strlen($string) > $length) {
			$length -= mb_strlen($etc);
			if (!$break_words && !$middle) {
				$string = preg_replace('/\s+?(\S+)?$/', '', mb_substr($string, 0, $length + 1));
			}
			if (!$middle) {
				$r = mb_substr($string, 0, $length) . $etc;
			}
			else {
				$r = mb_substr($string, 0, $length / 2) . $etc . mb_substr($string, -$length / 2);
			}
		}
		else {
			$r = $string;
		}
		if ($old != $encoding) {
			mb_internal_encoding($old);
		}
		return $r;
	}
?>
