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
class Modifier
{

    /**
     * Date format
     *
     * @param string|int $date
     * @param string $format
     * @return string
     */
    public static function dateFormat($date, $format = "%b %e, %Y")
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
            if (!$date) {
                $date = time();
            }
        }
        return strftime($format, $date);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function date($date, $format = "Y m d")
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
            if (!$date) {
                $date = time();
            }
        }
        return date($format, $date);
    }

    /**
     * Escape string
     *
     * @param string $text
     * @param string $type
     * @param string $charset
     * @return string
     */
    public static function escape($text, $type = 'html', $charset = null)
    {
        switch (strtolower($type)) {
            case "url":
                return urlencode($text);
            case "html";
                return htmlspecialchars($text, ENT_COMPAT, $charset ? $charset : \Fenom::$charset);
            case "js":
                return json_encode($text, 64 | 256); // JSON_UNESCAPED_SLASHES = 64, JSON_UNESCAPED_UNICODE = 256
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
    public static function unescape($text, $type = 'html')
    {
        switch (strtolower($type)) {
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
    public static function truncate($string, $length = 80, $etc = '...', $by_words = false, $middle = false)
    {
        if ($middle) {
            if (preg_match('#^(.{' . $length . '}).*?(.{' . $length . '})?$#usS', $string, $match)) {
                if (count($match) == 3) {
                    if ($by_words) {
                        return preg_replace('#\s\S*$#usS', "", $match[1]) .
                               $etc .
                               preg_replace('#\S*\s#usS', "", $match[2]);
                    } else {
                        return $match[1] . $etc . $match[2];
                    }
                }
            }
        } else {
            if (preg_match('#^(.{' . $length . '})#usS', $string, $match)) {
                if ($by_words) {
                    return preg_replace('#\s\S*$#usS', "", $match[1]) . $etc;
                } else {
                    return $match[1] . $etc;
                }
            }
        }
        return $string;
    }

    /**
     * Strip spaces symbols on edge of string end multiple spaces in the string
     *
     * @param string $str
     * @param bool $to_line strip line ends
     * @return string
     */
    public static function strip($str, $to_line = false)
    {
        $str = trim($str);
        if ($to_line) {
            return preg_replace('#\s+#ms', ' ', $str);
        } else {
            return preg_replace('#[ \t]{2,}#', ' ', $str);
        }
    }

    /**
     * Return length of UTF8 string, array, countable object
     * @param mixed $item
     * @return int
     */
    public static function length($item)
    {
        if (is_string($item)) {
            return strlen(preg_replace('#[\x00-\x7F]|[\x80-\xDF][\x00-\xBF]|[\xE0-\xEF][\x00-\xBF]{2}#s', ' ', $item));
        } elseif (is_array($item)) {
            return count($item);
        } elseif ($item instanceof \Countable) {
            return $item->count();
        } else {
            return 0;
        }
    }

    /**
     *
     * @param mixed $value
     * @param mixed $haystack
     * @return bool
     */
    public static function in($value, $haystack)
    {
        if(is_scalar($value)) {
            if (is_array($haystack)) {
                return in_array($value, $haystack) || array_key_exists($value, $haystack);
            } elseif (is_string($haystack)) {
                return strpos($haystack, $value) !== false;
            }
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isIterable($value)
    {
        return is_array($value) || ($value instanceof \Iterator);
    }

    /**
     * Replace all occurrences of the search string with the replacement string
     * @param string $value The string being searched and replaced on, otherwise known as the haystack.
     * @param string $search The value being searched for, otherwise known as the needle.
     * @param string $replace The replacement value that replaces found search
     * @return mixed
     */
    public static function replace($value, $search, $replace)
    {
        return str_replace($search, $replace, $value);
    }

    /**
     * @param string $value
     * @param string $pattern
     * @param string $replacement
     * @return mixed
     */
    public static function ereplace($value, $pattern, $replacement)
    {
        return preg_replace($pattern, $replacement, $value);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return bool
     */
    public static function match($string, $pattern)
    {
        return fnmatch($pattern, $string);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return int
     */
    public static function ematch($string, $pattern)
    {
        return preg_match($pattern, $string);
    }

    /**
     * @param string $value
     * @param string $delimiter
     * @return array
     */
    public static function split($value, $delimiter = ",")
    {
        if(is_string($value)) {
            return explode($delimiter, $value);
        } elseif(is_array($value)) {
            return $value;
        } else {
            return array();
        }
    }

    /**
     * @param $value
     * @param string $pattern
     * @return array
     */
    public static function esplit($value, $pattern = '/,\s*/S')
    {
        if(is_string($value)) {
            return preg_split($pattern, $value);
        } elseif(is_array($value)) {
            return $value;
        } else {
            return array();
        }
    }

    /**
     * @param $value
     * @param string $glue
     * @return string
     */
    public static function join($value, $glue = ",")
    {
        if(is_array($value)) {
            return implode($glue, $value);
        } elseif(is_string($value)) {
            return $value;
        } else {
            return "";
        }
    }

    /**
     * @param string|int $from
     * @param string|int $to
     * @param int $step
     * @return RangeIterator
     */
    public static function range($from, $to, $step = 1) {
        if($from instanceof RangeIterator) {
            return $from->setStep($to);
        } else {
            return new RangeIterator($from, $to, $step);
        }
    }
}
