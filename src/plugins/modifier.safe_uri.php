<?php

	function fenom_modifier_safe_uri($uri) {
		$uri = trim($uri);
		if (preg_match('~^(?:java|vb)script:~i', preg_replace('~\s+~', '', $uri))) {
			return '/';
		}
		return $uri;
	}
?>