<?php
	
	function fenom_modifier_native_json_encode($a = NULL) {
		return native_json_encode($a);
	}

	if (!function_exists('native_json_encode')) {
		function native_json_encode($a = NULL) {
			if (is_null($a)) {
				return 'null';
			}
			if ($a === false) {
				return 'false';
			}
			if ($a === true) {
				return 'true';
			}
			if (is_scalar($a)) {
				if (is_float($a)) {
					// Always use "." for floats.
					return floatval(str_replace(",", ".", strval($a)));
				}
	
				if (is_string($a)) {
					static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
					return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
				}
				else {
					return $a;
				}
			}
			$isList = true;
			for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
				if (key($a) !== $i) {
					$isList = false;
					break;
				}
			}
			$result = array();
			if ($isList) {
				foreach ($a as $v) {
					$result[] = native_json_encode($v);
				}
				return '[' . join(',', $result) . ']';
			}
			else {
				foreach ($a as $k => $v) {
					$result[] = native_json_encode($k) . ':' . native_json_encode($v);
				}
				return '{' . join(',', $result) . '}';
			}
		}
	}
?>	