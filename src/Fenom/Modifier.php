<?php
/*
 * This file is part of Fenom.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
namespace Fenom;

/**
 * Collection of modifiers
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Modifier {

    /**
     * Date format
     *
     * @param string|int $date
     * @param string $format
     * @return string
     */
    public static function dateFormat($date, $format = "%b %e, %Y") {
        if(is_string($date) && !is_numeric($date)) {
            $date = strtotime($date);
            if(!$date) $date = time();
        }
        return strftime($format, $date);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function date($date, $format = "Y m d") {
        if(is_string($date) && !is_numeric($date)) {
            $date = strtotime($date);
            if(!$date) $date = time();
        }
        return date($format, $date);
    }

    /**
     * Escape string
     *
     * @param string $text
     * @param string $type
     * @return string
     */
    public static function escape($text, $type = 'html') {
        switch(strtolower($type)) {
            case "url":
                return urlencode($text);
            case "html";
                return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
            default:
                return $text;
        }
    }

    /**
     * Unescape escaped string
     *
     * @param string $text
     * @param string $type
     * @return string
     */
    public static function unescape($text, $type = 'html') {
        switch(strtolower($type)) {
            case "url":
                return urldecode($text);
            case "html";
                return htmlspecialchars_decode($text);
            default:
                return $text;
        }
    }

    /**
     * Crop string to specific length (support unicode)
     *
     * @param string $string text witch will be truncate
     * @param int $length maximum symbols of result string
     * @param string $etc place holder truncated symbols
     * @param bool $by_words
     * @param bool $middle
     * @return string
     */
    public static function truncate($string, $length = 80, $etc = '...', $by_words = false, $middle = false) {
        if($middle) {
            if(preg_match('#^(.{'.$length.'}).*?(.{'.$length.'})?$#usS', $string, $match)) {
                if(count($match) == 3) {
                    if($by_words) {
                        return preg_replace('#\s.*$#usS', "", $match[1]).$etc.preg_replace('#^.*\s#usS', "", $match[2]);
                    } else {
                        return $match[1].$etc.$match[2];
                    }
                }
            }
        } else {
            if(preg_match('#^(.{'.$length.'})#usS', $string, $match)) {
                if($by_words) {
                    return preg_replace('#\s.*$#usS', "", $match[1]).$etc;
                } else {
                    return $match[1].$etc;
                }
            }
        }
        return $string;
    }

    /**
     * Strip spaces symbols on edge of string end multiple spaces in string
     *
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

    /**
     * Return length of UTF8 string, array, countable object
     * @param mixed $item
     * @return int
     */
    public static function length($item) {
        if(is_string($item)) {
            return strlen(preg_replace('#[\x00-\x7F]|[\x80-\xDF][\x00-\xBF]|[\xE0-\xEF][\x00-\xBF]{2}#s', ' ', $item));
        } elseif (is_array($item)) {
            return count($item);
        } elseif($item instanceof \Countable) {
            return count($item);
        } else {
            return 0;
        }
    }

    /**
     *
     * @param $value
     * @param $list
     * @return bool
     */
    public static function in($value, $list) {
        if(is_array($list)) {
            return in_array($value, $list);
        }
        return false;
    }
}
