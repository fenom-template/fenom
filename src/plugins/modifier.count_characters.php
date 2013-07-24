<?php

/**
 * Fenom plugin
 * 
 * @package Fenom
 * @subpackage PluginsModifier
 */

/**
 * Fenom count_characters modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     count_characters<br>
 * Purpose:   count the number of characters in a text
 *
 * @link http://www.fenom.ru/docs/language.modifiers.tpl#language.modifier.capitalize
 * @author  
 * 
 * @param string  $string    		string to count characters
 * @param boolean $inckule_spaces  	flag count whitespaces
 * @param boolean $lc_rest   capitalize first letters, lowercase all following letters "aAa" to "Aaa"
 * @return integer count characters in string
 * 
 */




	function fenom_modifier_count_characters($string, $include_spaces = FALSE) {
			
		if ($include_spaces) {
			return strlen($string);
		}
		
		return preg_match_all('~\S~', $string, $match);
	}

?>