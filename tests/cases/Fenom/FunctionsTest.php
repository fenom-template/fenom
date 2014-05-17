<?php
namespace Fenom;

class FunctionsTest extends TestCase
{

    const FUNCTION_ARGUMENT_CONSTANT = 1;

    public static function functionSum($of = array())
    {
        return array_sum($of);
    }

    public static function functionPow($a, $n = 2)
    {
        return pow($a, $n);
    }

    public static function functionInc($a, $i = self::FUNCTION_ARGUMENT_CONSTANT)
    {
        return $a + $i;
    }

    public function setUp()
    {
        parent::setUp();
        $this->fenom->addFunctionSmart('sum', __CLASS__ . '::functionSum');
        $this->fenom->addFunctionSmart('pow', __CLASS__ . '::functionPow');
        $this->fenom->addFunctionSmart('inc', __CLASS__ . '::functionInc');

        $this->tpl('function_params_scalar.tpl', '{pow a=2 n=3}');
        $this->tpl('function_params_dynamic.tpl', '{pow a=$a n=$n}');
        $this->tpl('function_default_param_scalar.tpl', '{pow a=2}');
        $this->tpl('function_default_param_empty_array.tpl', '{sum}');
        $this->tpl('function_default_param_const.tpl', '{inc a=1}');
        $this->tpl('function_array_param.tpl', '{sum of=[1, 2, 3, 4, 5]}');
        $this->tpl('function_array_param_pos.tpl', '{sum [1, 2, 3, 4, 5]}');
    }

    /**
     * @group sb
     */
    public function testFunctionWithParams()
    {
        $output = $this->fenom->fetch('function_params_scalar.tpl');
        $this->assertEquals('8', $output);
    }

    public function testFunctionWithDynamicParams()
    {
        $output = $this->fenom->fetch('function_params_dynamic.tpl', array('a' => 3, 'n' => 4));
        $this->assertEquals('81', $output);
    }

    public function testFunctionWithDefaultParamScalar()
    {
        $output = $this->fenom->fetch('function_default_param_scalar.tpl');
        $this->assertEquals('4', $output);
    }

    public function testFunctionWithDefaultParamArray()
    {
        $output = $this->fenom->fetch('function_default_param_empty_array.tpl');
        $this->assertEquals('0', $output);
    }

    public function testFunctionWithDefaultParamConst()
    {
        $output = $this->fenom->fetch('function_default_param_const.tpl');
        $this->assertEquals('2', $output);
    }

    public function testFunctionWithArrayNamedParam()
    {
        $output = $this->fenom->fetch('function_array_param.tpl');
        $this->assertEquals('15', $output);
    }

    public function testFunctionWithArrayPositionalParam()
    {
        $output = $this->fenom->fetch('function_array_param_pos.tpl');
        $this->assertEquals('15', $output);
    }

}
