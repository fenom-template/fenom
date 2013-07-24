<?php
	
	function fenom_modifier_escape($string, $esc_type = 'html', $char_set = 'UTF-8') {
		if ($esc_type == 'html') {
			return htmlspecialchars($string, ENT_QUOTES, $char_set);
		}
		if ($esc_type == 'htmlall') {
			return htmlentities($string, ENT_QUOTES, $char_set);
		}
		if ($esc_type == 'url') {
			return rawurlencode($string);
		}
		if ($esc_type == 'urlencode') {
			return urlencode($string);
		}
		if ($esc_type == 'urldecode') {
			return urldecode($string);
		}
		if ($esc_type == 'urlpathinfo') {
			return str_replace('%2F', '/', rawurlencode($string));
		}
		if ($esc_type == 'quotes') {
			// escape unescaped single quotes
			return preg_replace("%(?<!\\\\)'%", "\\'", $string);
		}
		if ($esc_type == 'hex') {
			// escape every character into hex
			$return = '';
			for ($x = 0; $x < strlen($string); $x++) {
				$return .= '%' . bin2hex($string[$x]);
			}
			return $return;
		}
		if ($esc_type == 'hexentity') {
			$return = '';
			for ($x = 0; $x < strlen($string); $x++) {
				$return .= '&#x' . bin2hex($string[$x]) . ';';
			}
			return $return;
		}
		if ($esc_type == 'decentity') {
			$return = '';
			for ($x = 0; $x < strlen($string); $x++) {
				$return .= '&#' . ord($string[$x]) . ';';
			}
			return $return;
		}
		if ($esc_type == 'javascript') {
			// escape quotes and backslashes, newlines, etc.
			return strtr($string, array('\\' => '\\\\', "'" => "\\'", '"' => '\\"', "\r" => '\\r', "\n" => '\\n', '</' => '<\/'));
		}
		if ($esc_type == 'mail') {
			// safe way to display e-mail address on a web page
			return str_replace(array('@', '.'), array(' [AT] ', ' [DOT] '), $string);
		}
		if ($esc_type == 'nonstd') {
			// escape non-standard chars, such as ms document quotes
			$_res = '';
			for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
				$_ord = ord(substr($string, $_i, 1));
				// non-standard char, escape it
				if ($_ord >= 126) {
					$_res .= '&#' . $_ord . ';';
				}
				else {
					$_res .= substr($string, $_i, 1);
				}
			}
			return $_res;
		}
		return $string;
	}

?>	