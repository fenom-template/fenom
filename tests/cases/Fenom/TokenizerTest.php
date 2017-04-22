<?php
namespace Fenom;

use Fenom\Error\UnexpectedTokenException;
use Fenom\Tokenizer;

class TokenizerTest extends TestCase
{

    public function testGetName()
    {
        $this->assertSame('T_DOUBLE_COLON', Tokenizer::getName(T_DOUBLE_COLON));
        $this->assertSame('++', Tokenizer::getName('++'));
        $this->assertSame('T_STRING', Tokenizer::getName(array(T_STRING, 'all', "", 1, "T_STRING")));
        $this->assertNull(Tokenizer::getName(false));
    }

    public function testTokens()
    {
        $code   = 'hello, please  resolve this example: sin($x)+tan($x*$t) = {U|[0,1]}';
        $tokens = new Tokenizer($code);
        $this->assertSame(27, $tokens->count());
        $this->assertSame($tokens, $tokens->back());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("hello", $tokens->current());
        $this->assertSame(1, $tokens->getLine());


        $this->assertTrue($tokens->isNext(","));

        $this->assertFalse($tokens->isNext("="));
        $this->assertFalse($tokens->isNext(T_STRING));
        $this->assertFalse($tokens->isNext($tokens::MACRO_UNARY));

        $this->assertFalse($tokens->isNext("=", T_STRING, $tokens::MACRO_UNARY));
        $this->assertTrue($tokens->isNext("=", T_STRING, $tokens::MACRO_UNARY, ","));

        $this->assertSame(",", $tokens->getNext());
        $this->assertSame(",", $tokens->key());
        $this->assertSame("please", $tokens->getNext(T_STRING));
        $this->assertSame(
            array(
                T_STRING,
                'please',
                '  ',
                1
            ),
            $tokens->curr
        );
        $this->assertSame("resolve", $tokens->getNext($tokens::MACRO_UNARY, T_STRING));

        $tokens->next();
        $tokens->next();
        $tokens->next();

        $this->assertSame(":", $tokens->current());
        $this->assertSame(":", $tokens->key());


        $this->assertSame("sin", $tokens->getNext($tokens::MACRO_STRING));
        $this->assertSame("sin", $tokens->current());
        $this->assertTrue($tokens->isPrev(":"));
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertTrue($tokens->is(T_STRING));
        $this->assertTrue($tokens->is($tokens::MACRO_STRING));
        $this->assertFalse($tokens->is($tokens::MACRO_EQUALS));
        $this->assertFalse($tokens->is(T_DNUMBER));
        $this->assertFalse($tokens->is(":"));
        $this->assertSame("(", $tokens->getNext("(", ")"));
        $this->assertTrue($tokens->hasBackList(T_STRING, ':'));
        $this->assertFalse($tokens->hasBackList(T_LNUMBER, ':'));

        $tokens->next();
        $tokens->next();
        $this->assertSame("+", $tokens->getNext($tokens::MACRO_BINARY));

        $this->assertSame($code, $tokens->getSnippetAsString(-100, 100));
        $this->assertSame('+', $tokens->getSnippetAsString(100, -100));
        $this->assertSame('sin($x)+tan($x*$t)', $tokens->getSnippetAsString(-4, 6));
        $this->assertSame('}', $tokens->end()->current());
    }

    public function testSkip()
    {
        $text   = "1 foo: bar ( 3 + double ) ";
        $tokens = new Tokenizer($text);

        $tokens->skip()->skip(T_STRING)->skip(':');
        try {
            $tokens->skip(T_STRING)->skip('(')->skip(':');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Fenom\Error\UnexpectedTokenException', $e);
            $this->assertStringStartsWith("Unexpected token '3' in expression, expect ':'", $e->getMessage());
        }
        $this->assertTrue($tokens->valid());
        $this->assertSame("3", $tokens->current());

        $this->assertSame(T_LNUMBER, $tokens->key());
        $this->assertSame($tokens, $tokens->next());
        $tokens->next();
        $this->assertSame("double", $tokens->getAndNext());
        $this->assertSame(")", $tokens->current());
        $this->assertTrue($tokens->isLast());
        $this->assertSame($tokens, $tokens->next());
        $tokens->p = 1000;
        $this->assertSame($tokens, $tokens->next());
        $tokens->p = -1000;
        $this->assertSame($tokens, $tokens->back());
        $this->assertNull($tokens->undef);
    }

    public function testFixFloats() {
        $text   = "1..3";
        $tokens = new Tokenizer($text);
        $this->assertTrue($tokens->is(T_LNUMBER));
        $this->assertTrue($tokens->next()->is('.'));
        $this->assertTrue($tokens->next()->is('.'));
        $this->assertTrue($tokens->next()->is(T_LNUMBER));
    }

}
