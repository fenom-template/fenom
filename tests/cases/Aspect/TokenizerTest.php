<?php
namespace Aspect;
use Aspect\Tokenizer;

class TokenizerTest extends \PHPUnit_Framework_TestCase {

	public function testTokens() {
		$code = 'hello, please resolve this example: sin($x)+tan($x*$t) = {U|[0,1]}';
		$tokens = new Tokenizer($code);
		$this->assertSame(T_STRING, $tokens->key());
		$this->assertSame("hello", $tokens->current());

		$this->assertTrue($tokens->isNext(","));
		$this->assertFalse($tokens->isNext("="));
		$this->assertFalse($tokens->isNext(T_STRING));
		$this->assertFalse($tokens->isNext($tokens::MACRO_UNARY));

		$this->assertFalse($tokens->isNext("=", T_STRING, $tokens::MACRO_UNARY));
		$this->assertTrue($tokens->isNext("=", T_STRING, $tokens::MACRO_UNARY, ","));

		$this->assertSame(",", $tokens->getNext());
		$this->assertSame(",", $tokens->key());
		$this->assertSame("please", $tokens->getNext(T_STRING));
		$this->assertSame("resolve", $tokens->getNext($tokens::MACRO_UNARY, T_STRING));

		$tokens->next();
		$tokens->next();
		$tokens->next();

		$this->assertSame(":", $tokens->current());
		$this->assertSame(":", $tokens->key());


		$this->assertSame("sin", $tokens->getNext($tokens::MACRO_STRING));
		$this->assertSame("sin", $tokens->current());
		$this->assertSame(T_STRING, $tokens->key());
		$this->assertTrue($tokens->is(T_STRING));
		$this->assertTrue($tokens->is($tokens::MACRO_STRING));
		$this->assertFalse($tokens->is($tokens::MACRO_EQUALS));
		$this->assertFalse($tokens->is(T_DNUMBER));
		$this->assertFalse($tokens->is(":"));
		$this->assertSame("(", $tokens->getNext("(",")"));

		$tokens->next();
		$tokens->next();
		$this->assertSame("+", $tokens->getNext($tokens::MACRO_BINARY));
	}

    public function testWhitespaceSenseLow() {
        $text = "1 foo  \n   bar\n  \n    double  ";
        $tokens = new Tokenizer($text, Tokenizer::DECODE_NEW_LINES);

        $this->assertTrue($tokens->valid());
        $this->assertSame("1", $tokens->current());
        $this->assertSame(T_LNUMBER, $tokens->key());
        $this->assertSame(" ", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("foo", $tokens->current());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("  ", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("\n", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("   ", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("bar", $tokens->current());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("\n", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("  ", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("\n", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("    ", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("double", $tokens->current());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("  ", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertFalse($tokens->valid());
        $this->assertNull($tokens->key());
    }

    public function testWhitespaceSenseHi() {
        $text = "1 foo  \n   bar\n  \n    double  ";
        $tokens = new Tokenizer($text, Tokenizer::DECODE_WHITESPACES);

        $this->assertTrue($tokens->valid());
        $this->assertSame("1", $tokens->current());
        $this->assertSame(T_LNUMBER, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame(" ", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("foo", $tokens->current());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("  \n   ", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("bar", $tokens->current());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("\n  \n    ", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("double", $tokens->current());
        $this->assertSame(T_STRING, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertTrue($tokens->valid());
        $this->assertSame("  ", $tokens->current());
        $this->assertSame(T_WHITESPACE, $tokens->key());
        $this->assertSame("", $tokens->getWhiteSpace());
        $tokens->next();

        $this->assertFalse($tokens->valid());
        $this->assertNull($tokens->key());
    }

    public function testSkip() {
        $text = "1 foo: bar ( 3 + double ) ";
        $tokens = new Tokenizer($text);

        $tokens->skip()->skip(T_STRING)->skip(':');
        try {
            $tokens->skip(T_STRING)->skip('(')->skip(':');
        } catch(\Exception $e) {
            $this->assertInstanceOf('Aspect\UnexpectedException', $e);
            $this->assertStringStartsWith("Unexpected token '3', expect ':'", $e->getMessage());
        }
        $this->assertTrue($tokens->valid());
        $this->assertSame("3", $tokens->current());
        $this->assertSame(T_LNUMBER, $tokens->key());
        $this->assertSame(" ", $tokens->getWhiteSpace());
        $tokens->next();
    }
}
