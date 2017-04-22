<?php

namespace Fenom;


class AccessorTest  extends TestCase
{
    public static function providerGetVar()
    {
        return array(
            array("get"),
            array("post"),
            array("cookie"),
            array("request"),
            array("files"),
            array("globals"),
            array("server"),
            array("session"),
            array("env"),
        );
    }

    /**
     * @dataProvider providerGetVar
     * @backupGlobals
     * @param string $var
     */
    public function testGetVar($var)
    {
        $_GET['one']     = 'get1';
        $_POST['one']    = 'post1';
        $_COOKIE['one']  = 'cookie1';
        $_REQUEST['one'] = 'request1';
        $_FILES['one']   = 'files1';
        $GLOBALS['one']  = 'globals1';
        $_SERVER['one']  = 'server1';
        $_SESSION['one'] = 'session1';
        $_ENV['one']     = 'env1';
        $this->exec('{$.'.$var.'.one}', self::getVars(), "{$var}1");
        $this->exec('{$.'.$var.'.undefined}', self::getVars(), "");
    }

    public static function providerTpl()
    {
        return array(
            array("name"),
            array("scm"),
            array("basename"),
            array("options"),
            array("time"),
        );
    }

    /**
     * @dataProvider providerTpl
     * @param string $name
     */
    public function testTpl($name)
    {
        $this->tpl("accessor.tpl", '{$.tpl.'.$name.'}');
        $tpl = $this->fenom->setOptions(\Fenom::FORCE_VERIFY)->getTemplate('accessor.tpl');
        $this->assertSame(strval($tpl->{"get$name"}()), $tpl->fetch(self::getVars()));
    }

    public function testVersion()
    {
        $this->assertRender('{$.version}', \Fenom::VERSION);
    }

    public static function providerConst()
    {
        return array(
            array("$.const.PHP_VERSION_ID", PHP_VERSION_ID),
            array('$.const.UNDEFINED', ''),
            array("$.const.FENOM_RESOURCES", FENOM_RESOURCES),
            array("$.const.Fenom.HELPER_CONSTANT", HELPER_CONSTANT),
            array("$.const.Fenom.UNDEFINED", ''),
            array("$.const.Fenom::VERSION", \Fenom::VERSION),
            array("$.const.Fenom::UNDEFINED", ''),
            array("$.const.Fenom.Helper::CONSTANT", Helper::CONSTANT),
            array("$.const.Fenom.Helper::UNDEFINED", ''),
        );
    }

    /**
     * @dataProvider providerConst
     * @param $tpl
     * @param $value
     * @group const
     */
    public function testConst($tpl, $value)
    {
        $this->assertRender('{'.$tpl.'}', strval($value));
    }


    public static function providerCall() {
        return array(
            array('$.call.strrev("string")', strrev("string")),
            array('$.call.strrev("string")', strrev("string"), 'str*'),
            array('$.call.strrev("string")', strrev("string"), 'strrev'),
            array('$.call.get_current_user', get_current_user()),
            array('$.call.Fenom.helper_func("string", 12)', helper_func("string", 12)),
            array('$.call.Fenom.helper_func("string", 12)', helper_func("string", 12), 'Fenom\\*'),
            array('$.call.Fenom.helper_func("string", 12)', helper_func("string", 12), 'Fenom\helper_func'),
            array('$.call.Fenom.helper_func("string", 12)', helper_func("string", 12), '*helper_func'),
            array('$.call.Fenom.helper_func("string", 12)', helper_func("string", 12), '*'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string")),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), 'Fenom\*'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), 'Fenom\TestCase*'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), 'Fenom\TestCase::*'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), 'Fenom\*::dots'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), 'Fenom\*::*'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), 'Fenom\TestCase::dots'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), '*::dots'),
            array('$.call.Fenom.TestCase::dots("string")', TestCase::dots("string"), '*'),
        );
    }

    /**
     * @dataProvider providerCall
     * @group php
     */
    public function testCall($tpl, $result, $mask = null) {
        if($mask) {
            $this->fenom->addCallFilter($mask);
        }
        $this->assertRender('{'.$tpl.'}', $result);
    }

    /**
     * @group issue260
     */
    public function testBug260() {
        $this->fenom->compileCode('{$.php.Fenom::factory()->addModifier("intval", "intval")}');
    }


    public static function providerPHPInvalid() {
        return array(
            array('$.call.aaa("string")', 'Fenom\Error\CompileException', 'PHP method aaa does not exists'),
            array('$.call.strrev("string")', 'Fenom\Error\SecurityException', 'Callback strrev is not available by settings', 'strrevZ'),
            array('$.call.strrev("string")', 'Fenom\Error\SecurityException', 'Callback strrev is not available by settings', 'str*Z'),
            array('$.call.strrev("string")', 'Fenom\Error\SecurityException', 'Callback strrev is not available by settings', '*Z'),
            array('$.call.Fenom.aaa("string")', 'Fenom\Error\CompileException', 'PHP method Fenom.aaa does not exists'),
            array('$.call.Fenom.helper_func("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.helper_func is not available by settings', 'Reflection\*'),
            array('$.call.Fenom.helper_func("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.helper_func is not available by settings', 'Fenom\*Z'),
            array('$.call.Fenom.helper_func("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.helper_func is not available by settings', 'Fenom\*::*'),
            array('$.call.TestCase::aaa("string")', 'Fenom\Error\CompileException', 'PHP method TestCase::aaa does not exists'),
            array('$.call.Fenom.TestCase::aaa("string")', 'Fenom\Error\CompileException', 'PHP method Fenom.TestCase::aaa does not exists'),
            array('$.call.Fenom.TestCase::dots("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.TestCase::dots is not available by settings', 'Reflection\*'),
            array('$.call.Fenom.TestCase::dots("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.TestCase::dots is not available by settings', 'Fenom\*Z'),
            array('$.call.Fenom.TestCase::dots("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.TestCase::dots is not available by settings', 'Fenom\*::get*'),
            array('$.call.Fenom.TestCase::dots("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.TestCase::dots is not available by settings', 'Fenom\TestCase::get*'),
            array('$.call.Fenom.TestCase::dots("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.TestCase::dots is not available by settings', 'Fenom\TestCase::*Z'),
            array('$.call.Fenom.TestCase::dots("string")', 'Fenom\Error\SecurityException', 'Callback Fenom.TestCase::dots is not available by settings', '*::*Z'),
        );
    }

    /**
     * @dataProvider providerPHPInvalid
     * @group php
     */
    public function testPHPInvalid($tpl, $exception, $message, $methods = null) {
        if($methods) {
            $this->fenom->addCallFilter($methods);
        }
        $this->execError('{'.$tpl.'}', $exception, $message);
    }


    public static function providerAccessor()
    {
        return array(
            array('{$.get.one}', 'get1'),
            array('{$.post.one}', 'post1'),
            array('{$.request.one}', 'request1'),
            array('{$.session.one}', 'session1'),
            array('{$.files.one}', 'files1'),
            array('{$.globals.one}', 'globals1'),
            array('{$.cookie.one}', 'cookie1'),
            array('{$.server.one}', 'server1'),
            array('{"string"|append:"_":$.get.one}', 'string_get1'),
            array('{$.get.one?}', '1'),
            array('{$.get.one is set}', '1'),
            array('{$.get.two is empty}', '1'),
            array('{$.version}', \Fenom::VERSION),
            array('{$.tpl.name}', 'runtime.tpl'),
            array('{$.tpl.time}', '0'),
            array('{$.tpl.schema}', ''),
        );
    }

    public static function providerAccessorInvalid()
    {
        return array(
            array('{$.nope.one}', 'Fenom\Error\CompileException', "Unexpected token 'nope'"),
            array('{$.get.one}', 'Fenom\Error\SecurityException', 'Accessor are disabled', \Fenom::DENY_ACCESSOR),
        );
    }

    public static function providerFetch()
    {
        return array(
            array('{$.fetch("welcome.tpl")}'),
            array('{set $tpl = "welcome.tpl"}{$.fetch($tpl)}'),
            array('{$.fetch("welcome.tpl", ["username" => "Bzick", "email" => "bzick@dev.null"])}'),
            array('{set $tpl = "welcome.tpl"}{$.fetch($tpl, ["username" => "Bzick", "email" => "bzick@dev.null"])}'),
        );
    }

    /**
     * @group fetch
     * @dataProvider providerFetch
     */
    public function testFetch($code)
    {
        $this->tpl('welcome.tpl', '<b>Welcome, {$username} ({$email})</b>');
        $values = array('username' => 'Bzick', 'email' => 'bzick@dev.null');
        $this->assertRender($code, $this->fenom->fetch('welcome.tpl', $values), $values);
    }

    public static function providerFetchInvalid()
    {
        return array(
            array('{$.fetch("welcome_.tpl")}', 'Fenom\Error\CompileException', "Template welcome_.tpl not found"),
            array('{$.fetch("welcome_.tpl", [])}', 'Fenom\Error\CompileException', "Template welcome_.tpl not found"),
        );
    }

    /**
     * @group fetchInvalid
     * @dataProvider providerFetchInvalid
     */
    public function testFetchInvalidTpl($tpl, $exception, $message) {
        $this->execError($tpl, $exception, $message);
    }

    public function getThree() {
        return 3;
    }

    public static function providerSmartAccessor() {
        return array(
            array('acc', '$tpl->getStorage()->test->values', \Fenom::ACCESSOR_VAR, '{$.acc.three}', '3'),
            array('acc', '$tpl->getStorage()->test->getThree', \Fenom::ACCESSOR_CALL, '{$.acc()}', '3'),
            array('acc', 'three', \Fenom::ACCESSOR_PROPERTY, '{$.acc}', '3'),
            array('acc', 'templateExists', \Fenom::ACCESSOR_METHOD, '{$.acc("persist:pipe.tpl")}', '1')
        );
    }

    /**
     * @group testSmartAccessor
     * @dataProvider providerSmartAccessor
     * @param $name
     * @param $accessor
     * @param $type
     * @param $code
     * @param $result
     */
    public function testSmartAccessor($name, $accessor, $type, $code, $result) {
        $this->fenom->test = $this;
        $this->fenom->three = 3;
        $this->fenom->addAccessorSmart($name, $accessor, $type);
        $this->assertRender($code, $result, $this->getVars());
    }

    /**
     *
     */
    public function testCallbackAccessor() {
        $index = 1;
        $test = $this;
        $this->fenom->addAccessorCallback('index', function($name, $template, $vars) use (&$index, $test) {
            $test->assertInstanceOf('Fenom\Render', $template);
            $test->assertSame(1, $vars['one']);
            $test->assertSame('index', $name);

            return $index++;
        });

        $this->assertRender('{$.index}, {$.index}, {$.index}', '1, 2, 3', $this->getVars());
    }
} 