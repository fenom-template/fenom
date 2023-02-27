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

use Fenom\Error\CompileException;
use Fenom\Error\UnexpectedTokenException;

/**
 * Class Accessor
 * @package Fenom
 */
class Accessor {
    public static array $vars = array(
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

    /**
     * @param string $var variable expression on PHP ('App::get("storage")->user')
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @param bool $is_var
     * @return string
     * @throws CompileException
     */
    public static function parserVar(string $var, Tokenizer $tokens, Template $tpl, bool &$is_var): string
    {
        $is_var = true;
        return $tpl->parseVariable($tokens, $var);
    }

    /**
     * @param string $call method name expression on PHP ('App::get("storage")->getUser')
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function parserCall(string $call, Tokenizer $tokens, Template $tpl): string
    {
        return $call.$tpl->parseArgs($tokens);
    }

    /**
     * @param string $prop fenom's property name
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @param bool $is_var
     * @return string
     * @throws CompileException
     */
    public static function parserProperty(string $prop, Tokenizer $tokens, Template $tpl, bool &$is_var): string
    {
        $is_var = true;
        return self::parserVar('$tpl->getStorage()->'.$prop, $tokens, $tpl, $is_var);
    }

    /**
     * @param string $method fenom's method name
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function parserMethod(string $method, Tokenizer $tokens, Template $tpl): string
    {
        return self::parserCall('$tpl->getStorage()->'.$method, $tokens, $tpl);
    }

    /**
     * Accessor for global variables
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     * @throws CompileException
     */
    public static function getVar(Tokenizer $tokens, Template $tpl): string
    {
        $name = $tokens->prevToken()[Tokenizer::TEXT];
        if(isset(self::$vars[$name])) {
            $var = $tpl->parseVariable($tokens, self::$vars[$name]);
            return "(($var) ?? null)";
        } else {
            throw new UnexpectedTokenException($tokens->back());
        }
    }

    /**
     * Accessor for template information
     * @param Tokenizer $tokens
     * @return string
     */
    public static function tpl(Tokenizer $tokens): string
    {
        $method = $tokens->skip('.')->need(T_STRING)->getAndNext();
        if(method_exists('Fenom\Render', 'get'.$method)) {
            return '$tpl->get'.ucfirst($method).'()';
        } else {
            throw new UnexpectedTokenException($tokens->back());
        }
    }

    /**
     * @return string
     */
    public static function version(): string
    {
        return 'Fenom::VERSION';
    }

    /**
     * @param Tokenizer $tokens
     * @return string
     */
    public static function constant(Tokenizer $tokens): string
    {
        $const = [$tokens->skip('.')->need(Tokenizer::MACRO_STRING)->getAndNext()];
        while($tokens->is('.')) {
            $const[] = $tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        $const = implode('\\', $const);
        if($tokens->is(T_DOUBLE_COLON)) {
            $const .= '::'.$tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        return '(defined('.var_export($const, true).') ? constant('.var_export($const, true).') : "")';

    }

    /**
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function call(Tokenizer $tokens, Template $tpl): string
    {
        $callable = [$tokens->skip('.')->need(Tokenizer::MACRO_STRING)->getAndNext()];
        while($tokens->is('.')) {
            $callable[] = $tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        $callable = implode('\\', $callable);
        if($tokens->is(T_DOUBLE_COLON)) {
            $callable .= '::'.$tokens->next()->need(Tokenizer::MACRO_STRING)->getAndNext();
        }
        $call_filter = $tpl->getStorage()->getCallFilters();
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
    public static function fetch(Tokenizer $tokens, Template $tpl): string
    {
        $tokens->skip('(');
        $name = $tpl->parsePlainArg($tokens, $static);
        if($static) {
            if(!$tpl->getStorage()->templateExists($static)) {
                throw new \RuntimeException("Template $static not found");
            }
        }
        if($tokens->is(',')) {
            $tokens->next();
            if($tokens->is('[')){
                $vars = $tpl->parseArray($tokens) . ' + $var';
            } elseif($tokens->is(T_VARIABLE)){
                $vars = $tpl->parseExpr($tokens) . ' + $var';
            }
        } else {
            $vars = '$var';
        }
        $tokens->skip(')');
        return '$tpl->getStorage()->fetch('.$name.', '.$vars.')';
    }

    /**
     * Accessor {$.block.NAME}
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function block(Tokenizer $tokens, Template $tpl): string
    {
        if($tokens->is('.')) {
            $name = $tokens->next()->get(Tokenizer::MACRO_STRING);
            $tokens->next();
            return isset($tpl->blocks[$name]) ? 'true' : 'false';
        } else {
            return "array(".implode(",", array_keys($tpl->blocks)).")";
        }
    }
} 
