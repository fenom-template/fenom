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
     * @param int|string $date
     * @param string $format
     * @return string
     */
    public static function dateFormat(mixed $date, string $format = "%b %e, %Y"): string
    {
        if (!is_numeric($date)) {
            if ($date instanceof \DateTime) {
                $date = $date->getTimestamp();
            } else {
                $date = strtotime($date);
            }
            if (!$date) {
                $date = time();
            }
        }
        if (str_contains($format, "%")) {
            // fallback mode
            $from = [
                // Day - no strf eq : S (created one called %O)
                '%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
                // Week - no date eq : %U, %W
                '%V',
                // Month - no strf eq : n, t
                '%B', '%m', '%b', '%-m',
                // Year - no strf eq : L; no date eq : %C, %g
                '%G', '%Y', '%y',
                // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
                '%P', '%p', '%l', '%I', '%H', '%M', '%S',
                // Timezone - no strf eq : e, I, P, Z
                '%z', '%Z',
                // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
                '%s'
            ];

            $to = [
                'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
                'W',
                'F', 'm', 'M', 'n',
                'o', 'Y', 'y',
                'a', 'A', 'g', 'h', 'H', 'i', 's',
                'O', 'T',
                'U'
            ];
            $pattern = array_map(
                function ( $s ) {
                    return '/(?<!\\\\|\%)' . $s . '/';
                },
                $from
            );

            $format = preg_replace($pattern, $to, $format);
        }

        return date($format, $date);
    }

    /**
     * @param string $date
     * @param string $format
     * @return string
     */
    public static function date(string $date, string $format = "Y m d"): string
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
     * @param string|null $charset
     * @return string
     */
    public static function escape(string $text, string $type = 'html', string $charset = null): string
    {
        switch (strtolower($type)) {
            case "url":
                return urlencode($text);
            case "html";
                return htmlspecialchars($text, ENT_COMPAT, $charset ?: \Fenom::$charset);
            case "js":
                return json_encode($text, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
    public static function unescape(string $text, string $type = 'html'): string
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
     * @param string $etc placeholder truncated symbols
     * @param bool $by_words
     * @param bool $middle
     * @return string
     */
    public static function truncate(string $string, int $length = 80, string $etc = '...', bool $by_words = false, bool $middle = false): string
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
    public static function strip(string $str, bool $to_line = false): string
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
    public static function length(mixed $item): int
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
    public static function in(mixed $value, mixed $haystack): bool
    {
        if(is_scalar($value)) {
            if (is_array($haystack)) {
                return in_array($value, $haystack) || array_key_exists($value, $haystack);
            } elseif (is_string($haystack)) {
                return str_contains($haystack, $value);
            }
        }
        return false;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isIterable(mixed $value): bool
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
    public static function replace(string $value, string $search, string $replace): string
    {
        return str_replace($search, $replace, $value);
    }

    /**
     * @param string $value
     * @param string $pattern
     * @param string $replacement
     * @return mixed
     */
    public static function ereplace(string $value, string $pattern, string $replacement): string
    {
        return preg_replace($pattern, $replacement, $value);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return bool
     */
    public static function match(string $string, string $pattern): bool
    {
        return fnmatch($pattern, $string);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return int
     */
    public static function ematch(string $string, string $pattern): int
    {
        return preg_match($pattern, $string);
    }

    /**
     * @param string $value
     * @param string $delimiter
     * @return array
     */
    public static function split(mixed $value, string $delimiter = ","): array
    {
        if(is_string($value)) {
            return explode($delimiter, $value);
        } elseif(is_array($value)) {
            return $value;
        } else {
            return [];
        }
    }

    /**
     * @param mixed $value
     * @param string $pattern
     * @return array
     */
    public static function esplit(mixed $value, string $pattern = '/,\s*/S'): array
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
     * @param mixed $value
     * @param string $glue
     * @return string
     */
    public static function join(mixed $value, string $glue = ","): string
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
     * @param int $from
     * @param int $to
     * @param int $step
     * @return RangeIterator
     */
    public static function range(mixed $from, int $to, int $step = 1): RangeIterator
    {
        if($from instanceof RangeIterator) {
            return $from->setStep($to);
        } else {
            return new RangeIterator($from, $to, $step);
        }
    }
}
