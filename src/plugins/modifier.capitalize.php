<?php

/**
 * Fenom plugin
 * 
 * @package Fenom
 * @subpackage PluginsModifier
 */

/**
 * Fenom capitalize modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     capitalize<br>
 * Purpose:  capitalize words in the string
 *
 * @link http://www.fenom.ru/docs/language.modifiers.tpl#language.modifier.capitalize
 * @author  
 * 
 * @param string  $string    string to capitalize
 * @param boolean $uc_digits also capitalize "x123" to "X123"
 * @param boolean $lc_rest   capitalize first letters, lowercase all following letters "aAa" to "Aaa"
 * @return string capitalized string
 * 
 */

function fenom_modifier_capitalize($string, $uc_digits = false) {
	fenom_modifier_capitalize_ucfirst(NULL, $uc_digits);
	return preg_replace_callback('!\'?\b\w(\w|\')*\b!', 'fenome_modifier_capitalize_ucfirst', $string);
}

function fenom_modifier_capitalize_ucfirst($string, $uc_digits = null) {
	static $_uc_digits = FALSE;
	if (isset($uc_digits)) {
		$_uc_digits = $uc_digits;
		return;
	}
	if (substr($string[0], 0, 1) != '\'' && !preg_match('!\d!', $string[0]) || $_uc_digits) {
		return ucfirst($string[0]);
	}
	else {
		return $string[0];
	}
}

?>
