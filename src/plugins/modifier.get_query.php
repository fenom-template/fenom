<?php
	
	function fenom_modifier_get_query($params, $mode = '') {
		$query           = '';
		$all             = strpos($mode, 'a') !== FALSE;
		$plain           = strpos($mode, 'p') !== FALSE;
		$separator       = $plain ? '&' : '&amp;';
		$params_from_get = array();
		$params_sended   = array();
		foreach ($params as $k => $v) {
			if (is_int($k) and isset($_GET[$k])) {
				$params_from_get[$k] = $_GET[$k];
			}
			else {
				$params_sended[$k] = $v;
			}
		}
		if ($all) {
			foreach ($_GET as $k => $v) {
				if (isset($_REQUEST[$k])) {
					$params_from_get[$k] = $v;
				}
			}
		}
		$params = array_merge($params_from_get, $params_sended);
		foreach ($params as $k => $v) {
			if ($v === NULL) {
				continue;
			}
			$query .= ($query !== '' ? $separator : '?') . (is_array($v) ?
					http_build_query(array($k => $v), '', $separator) :
					$k . '=' . urlencode($v)
			);
		}
		return $query;
	}
?>