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

use Fenom\Error\UnexpectedTokenException;

/**
 * Class Accessor
 * @package Fenom
 */
class Accessor {
    public static $vars = array(
        'get'     => '$_GET',
        'post'    => '$_POST',
        'session' => '$_SESSION',
        'cookie'  => '$_COOKIE',
        'request' => '$_REQUEST',
        'files'   => '$_FILES',
        'globals' => '$GLOBALS',
        'server'  => '$_SERVER',
        'env'     => '$_ENV'
    );

    public static function parserVar($var, Tokenizer $tokens, Template $tpl, &$is_var) {
        $is_var = true;
        return $tpl->parseVariable($tokens, $var);
    }


    public static function parserCall($call, Tokenizer $tokens, Template $tpl) {
        return $call.$tpl->parseArgs($tokens);
    }

    /**
     * Accessor for global variables
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function getVar(Tokenizer $tokens, Template $tpl)
    {
        $name = $tokens->prev[Tokenizer::TEXT];
        if(isset(self::$vars[$name])) {
            $var = $tpl->parseVariable($tokens, self::$vars[$name]);
            return "(isset($var) ? $var : null)";
        } else {
            throw new UnexpectedTokenException($tokens->back());
        }
    }

    /**
     * Accessor for template information
     * @param Tokenizer $tokens
     * @return string
     */
    public static function tpl(Tokenizer $tokens)
    {
        $method = $tokens->skip('.')->need(T_STRING)->getAndNext();
        if(method_exists('Fenom\Render', 'get'.$method)) {
            return '$tpl->get'.ucfirst($method).'()';
        } else {
            throw new UnexpectedTokenException($tokens->back());
        }
    }

    public static function version()
    {
        return 'Fenom::VERSION';
    }

    /**
     * @param Tokenizer $tokens
     * @return string
     */
    public static function constant(Tokenizer $tokens)
    {
        $const = array($tokens->skip('.')->need(Tokenizer::MACRO_STRING)->getAndNext());
        while($tokens->is('.')) {
            $const[] = $tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        $const = implode('\\', $const);
        if($tokens->is(T_DOUBLE_COLON)) {
            $const .= '::'.$tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        return '@constant('.var_export($const, true).')';

    }

    /**
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function php(Tokenizer $tokens, Template $tpl)
    {
        $callable = array($tokens->skip('.')->need(Tokenizer::MACRO_STRING)->getAndNext());
        while($tokens->is('.')) {
            $callable[] = $tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        $callable = implode('\\', $callable);
        if($tokens->is(T_DOUBLE_COLON)) {
            $callable .= '::'.$tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        $call_filter = $tpl->getStorage()->call_filters;
        if($call_filter) {
            foreach($call_filter as $filter) {
                if(!fnmatch(addslashes($filter), $callable)) {
                    throw new \LogicException("Callback ".str_replace('\\', '.', $callable)." is not available by settings");
                }
            }
        }
        if(!is_callable($callable)) {
            throw new \RuntimeException("PHP method ".str_replace('\\', '.', $callable).' does not exists.');
        }
        if($tokens->is('(')) {
            $arguments = 'array'.$tpl->parseArgs($tokens).'';
        } else {
            $arguments = 'array()';
        }
        return 'call_user_func_array('.var_export($callable, true).', '.$arguments.')';

    }

    /**
     * Accessor {$.fetch(...)}
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function fetch(Tokenizer $tokens, Template $tpl)
    {
        $tokens->skip('(');
        $name = $tpl->parsePlainArg($tokens, $static);
        if($static) {
            if(!$tpl->getStorage()->templateExists($static)) {
                throw new \RuntimeException("Template $static not found");
            }
        }
        if($tokens->is(',')) {
            $tokens->skip()->need('[');
            $vars = $tpl->parseArray($tokens) . ' + $var';
        } else {
            $vars = '$var';
        }
        $tokens->skip(')');
        return '$tpl->getStorage()->fetch('.$name.', '.$vars.')';
    }
} 