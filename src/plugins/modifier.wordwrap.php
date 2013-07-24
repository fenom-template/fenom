<?php
	function fenom_modifier_wordwrap($string, $length = 80, $break = "\n", $cut = FALSE) {
		return _wordwrap($string, $length, $break, $cut);
	}
	
	if (!function_exists('mb_wordwrap')) {
		function _wordwrap($str, $width, $break) {
			$formatted     = '';
			$position      = -1;
			$prev_position = 0;
			$last_line     = -1;
	
			/// looping the string stop at each space
			while ($position = mb_stripos($str, " ", ++$position, 'utf-8')) {
				if ($position > $last_line + $width + 1) {
					$formatted .= mb_substr($str, $last_line + 1, $prev_position - $last_line - 1, 'utf-8') . $break;
					$last_line = $prev_position;
				}
				$prev_position = $position;
			}
	
			/// adding last line without the break
			$formatted .= mb_substr($str, $last_line + 1, mb_strlen($str), 'utf-8');
			return $formatted;
		}
	
		function strwidth($s) {
			$ret = mb_strwidth($s, 'UTF-8');
			return $ret;
		}
	
		function mb_wordwrap($str, $wid, $tag) {
			$pos = 0;
			$tok = array();
			$l   = mb_strlen($str, 'UTF-8');
			if ($l == 0) {
				return '';
			}
			$flag   = false;
			$tok[0] = mb_substr($str, 0, 1, 'UTF-8');
			for ($i = 1; $i < $l; ++$i) {
				$c = mb_substr($str, $i, 1, 'UTF-8');
				if (!preg_match('/[a-z\'\"]/i', $c)) {
					++$pos;
					$flag = true;
				}
				elseif ($flag) {
					++$pos;
					$flag = false;
				}
				$tok[$pos] .= $c;
			}
	
			$linewidth = 0;
			$pos       = 0;
			$ret       = array();
			$l         = count($tok);
			for ($i = 0; $i < $l; ++$i) {
				if ($linewidth + ($w = strwidth($tok[$i])) > $wid) {
					++$pos;
					$linewidth = 0;
				}
				$ret[$pos] .= $tok[$i];
				$linewidth += $w;
			}
			return implode($tag, $ret);
		}
	}

	
?>