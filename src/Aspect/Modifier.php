<?php
/*
 * This file is part of Aspect.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Aspect;

/**
 * Collection of modifiers
 *
 * @package    aspect
 * @author     Ivan Shalganov <owner@bzick.net>
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
        switch($type) {
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
        switch($type) {
            case "url":
                return urldecode($text);
            case "html";
                return htmlspecialchars_decode($text);
            default:
                return $text;
        }
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $etc
     * @param bool $break_words
     * @param bool $middle
     * @return string
     */
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

    /**
     * @param mixed $item
     * @return int
     */
    public static function length($item) {
        if(is_scalar($item)) {
            return strlen($item);
        } elseif (is_array($item)) {
            return count($item);
        } else {
            return count((array)$item);
        }
    }
}
