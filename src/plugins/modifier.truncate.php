<?php
/**
 * Fenom plugin
 * 
 * @package Fenom
 * @subpackage PluginsModifier
 */

/**
 * Fenom truncate modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *               optionally splitting in the middle of a word, and
 *               appending the $etc string or inserting $etc into the middle.

 * @param string  $string      input string
 * @param integer $length      length of truncated text
 * @param string  $etc         end string
 * @param boolean $by_words    truncate at word boundary
 * @param boolean $middle      truncate in the middle of text
 * @return string truncated string
 * 
 * @link http://www.fenom.ru/docs/language.modifier.truncate.tpl truncate
 * @author   
 */

    function fenom_modifier_truncate($string, $length = 80, $etc = '...', $by_words = false, $middle = false) {
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
?>

