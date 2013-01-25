<?php
namespace Aspect;


class Modifier {

	public static function dateFormat($date, $format = "%b %e, %Y") {
		if(is_string($date) && !is_numeric($date)) {
			$date = strtotime($date);
			if(!$date) $date = time();
		}
		//dump($format, $date);
		return strftime($format, $date);
	}

	public static function date($date, $format = "Y m d") {
		if(is_string($date) && !is_numeric($date)) {
			$date = strtotime($date);
			if(!$date) $date = time();
		}
		return date($format, $date);
	}

    public static function escape($text, $type = 'html') {
	    switch($type) {
		    case "url":
                return urlencode($text);
		    case "html";
			    return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
		    default:
			    return $text;
	    }
    }

	public static function unescape($text, $type = 'html') {
		switch($type) {
			case "url":
				return urldecode($text);
			case "html";
				return htmlspecialchars_decode($text);
			default:
				return $text;
		}
	}

    public static function defaultValue(&$value, $default = null) {
        return ($value === null) ? $default : $value;
    }

    public static function truncate($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
        $length -= min($length, strlen($etc));
        if (!$break_words && !$middle) {
            $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
        }
        if (!$middle) {
            return substr($string, 0, $length) . $etc;
        }
        return substr($string, 0, $length / 2) . $etc . substr($string, - $length / 2);
    }

    /**
     * Strip spaces symbols on edge of string end multiple spaces into string
     * @static
     * @param string $str
     * @param bool $to_line strip line ends
     * @return string
     */
    public static function strip($str, $to_line = false) {
        $str = trim($str);
        if($to_line) {
            return preg_replace('#[\s]+#ms', ' ', $str);
        } else {
            return preg_replace('#[ \t]{2,}#', ' ', $str);
        }
    }
}
