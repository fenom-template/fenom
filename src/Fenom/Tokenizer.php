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
 * Each token have structure
 *  - Token (constant T_* or text)
 *  - Token name (textual representation of the token)
 *  - Whitespace (whitespace symbols after token)
 *  - Line number of the token
 *
 * @see http://php.net/tokenizer
 *
 * @package    Fenom
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Tokenizer
{
    const TOKEN      = 0;
    const TEXT       = 1;
    const WHITESPACE = 2;
    const LINE       = 3;

    /**
     * Some text value: foo, bar, new, class ...
     */
    const MACRO_STRING = 1000;
    /**
     * Unary operation: ~, !, ^
     */
    const MACRO_UNARY = 1001;
    /**
     * Binary operation (operation between two values): +, -, *, /, &&, or , ||, >=, !=, ...
     */
    const MACRO_BINARY = 1002;
    /**
     * Equal operation
     */
    const MACRO_EQUALS = 1003;
    /**
     * Scalar values (such as int, float, escaped strings): 2, 0.5, "foo", 'bar\'s'
     */
    const MACRO_SCALAR = 1004;
    /**
     * Increment or decrement: ++ --
     */
    const MACRO_INCDEC = 1005;
    /**
     * Boolean operations: &&, ||, or, xor, and
     */
    const MACRO_BOOLEAN = 1006;
    /**
     * Math operation
     */
    const MACRO_MATH = 1007;
    /**
     * Condition operation
     */
    const MACRO_COND = 1008;

    public array $tokens;
    public int $p = 0;
    public int $quotes = 0;
    private int $_max;
    private mixed $_last_no;

    /**
     * @see http://docs.php.net/manual/en/tokens.php
     * @var array groups of tokens
     */
    public static array $macros = [
        self::MACRO_STRING  => [
            \T_ABSTRACT      => 1, \T_ARRAY         => 1, \T_AS            => 1, \T_BREAK         => 1,
            \T_CASE          => 1, \T_CATCH         => 1, \T_CLASS         => 1,
            \T_CLASS_C       => 1, \T_CLONE         => 1, \T_CONST         => 1, \T_CONTINUE      => 1,
            \T_DECLARE       => 1, \T_DEFAULT       => 1, \T_DIR           => 1, \T_DO            => 1,
            \T_ECHO          => 1, \T_ELSE          => 1, \T_ELSEIF        => 1, \T_EMPTY         => 1,
            \T_ENDDECLARE    => 1, \T_ENDFOR        => 1, \T_ENDFOREACH    => 1, \T_ENDIF         => 1,
            \T_ENDSWITCH     => 1, \T_ENDWHILE      => 1, \T_EVAL          => 1, \T_EXIT          => 1,
            \T_EXTENDS       => 1, \T_FILE          => 1, \T_FINAL         => 1, \T_FOR           => 1,
            \T_FOREACH       => 1, \T_FUNCTION      => 1, \T_FUNC_C        => 1, \T_GLOBAL        => 1,
            \T_GOTO          => 1, \T_HALT_COMPILER => 1, \T_IF            => 1, \T_IMPLEMENTS    => 1,
            \T_INCLUDE       => 1, \T_INCLUDE_ONCE  => 1, \T_INSTANCEOF    => 1, \T_INSTEADOF     => 1,
            \T_INTERFACE     => 1, \T_ISSET         => 1, \T_LINE          => 1, \T_LIST          => 1,
            \T_LOGICAL_AND   => 1, \T_LOGICAL_OR    => 1, \T_LOGICAL_XOR   => 1, \T_METHOD_C      => 1,
            \T_MATCH         => 1,
            \T_NAMESPACE     => 1, \T_NS_C          => 1, \T_NEW           => 1, \T_PRINT         => 1,
            \T_PRIVATE       => 1, \T_PUBLIC        => 1, \T_PROTECTED     => 1, \T_REQUIRE       => 1,
            \T_REQUIRE_ONCE  => 1, \T_RETURN        => 1,                        \T_STRING        => 1,
            \T_SWITCH        => 1, \T_THROW         => 1, \T_TRAIT         => 1, \T_TRAIT_C => 1,
            \T_TRY           => 1, \T_UNSET         => 1, \T_USE           => 1, \T_VAR           => 1,
            \T_WHILE         => 1, \T_YIELD         => 1, \T_YIELD_FROM    => 1
        ],
        self::MACRO_INCDEC  => [
            \T_INC => 1, \T_DEC => 1
        ],
        self::MACRO_UNARY   => [
            "!" => 1, "~" => 1, "-" => 1
        ],
        self::MACRO_BINARY  => [
            \T_BOOLEAN_AND         => 1, \T_BOOLEAN_OR          => 1, \T_IS_GREATER_OR_EQUAL => 1,
            \T_IS_EQUAL            => 1, \T_IS_IDENTICAL        => 1, \T_IS_NOT_EQUAL        => 1,
            \T_IS_NOT_IDENTICAL    => 1, \T_IS_SMALLER_OR_EQUAL => 1, \T_LOGICAL_AND         => 1,
            \T_LOGICAL_OR          => 1, \T_LOGICAL_XOR         => 1, \T_SL                  => 1,
            \T_SR                  => 1, "+"                    => 1, "-"                    => 1,
            "*"                    => 1, "/"                    => 1, ">"                    => 1,
            "<"                    => 1, "^"                    => 1, "%"                    => 1,
            "&"                    => 1
        ],
        self::MACRO_BOOLEAN => [
            \T_LOGICAL_OR  => 1, \T_LOGICAL_XOR => 1,
            \T_BOOLEAN_AND => 1, \T_BOOLEAN_OR  => 1,
            \T_LOGICAL_AND => 1
        ],
        self::MACRO_MATH    => [
            "+" => 1, "-" => 1, "*" => 1,
            "/" => 1, "^" => 1, "%" => 1,
            "&" => 1, "|" => 1
        ],
        self::MACRO_COND    => [
            \T_IS_EQUAL            => 1, \T_IS_IDENTICAL        => 1, ">"                    => 1,
            "<"                    => 1, \T_SL                  => 1, \T_SR                  => 1,
            \T_IS_NOT_EQUAL        => 1, \T_IS_NOT_IDENTICAL    => 1, \T_IS_SMALLER_OR_EQUAL => 1,
        ],
        self::MACRO_EQUALS  => [
            \T_AND_EQUAL   => 1, \T_DIV_EQUAL   => 1, \T_MINUS_EQUAL => 1,
            \T_MOD_EQUAL   => 1, \T_MUL_EQUAL   => 1, \T_OR_EQUAL    => 1,
            \T_PLUS_EQUAL  => 1, \T_SL_EQUAL    => 1, \T_SR_EQUAL    => 1,
            \T_XOR_EQUAL   => 1, '='            => 1,
        ],
        self::MACRO_SCALAR  => [
            \T_LNUMBER                  => 1,
            \T_DNUMBER                  => 1,
            \T_CONSTANT_ENCAPSED_STRING => 1
        ]
    ];

    public static array $description = [
        self::MACRO_STRING  => 'string',
        self::MACRO_INCDEC  => 'increment/decrement operator',
        self::MACRO_UNARY   => 'unary operator',
        self::MACRO_BINARY  => 'binary operator',
        self::MACRO_BOOLEAN => 'boolean operator',
        self::MACRO_MATH    => 'math operator',
        self::MACRO_COND    => 'conditional operator',
        self::MACRO_EQUALS  => 'equal operator',
        self::MACRO_SCALAR  => 'scalar value'
    ];

    /**
     * Special tokens
     * @var array
     */
    private static array $spec = [
        'true'  => 1,
        'false' => 1,
        'null'  => 1,
        'TRUE'  => 1,
        'FALSE' => 1,
        'NULL'  => 1
    ];

    private ?array $_next;
    private ?array $_prev;
    private ?array $_curr;

    /**
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->_curr = null;
        $this->_next = null;
        $this->_prev = null;
        $tokens  = [-1 => [\T_WHITESPACE, '', '', 1]];
        $_tokens = token_get_all("<?php " . $query);
        $line    = 1;
        array_shift($_tokens);
        $i = 0;
        foreach ($_tokens as $token) {
            if (is_string($token)) {
                if ($token === '"' || $token === "'" || $token === "`") {
                    $this->quotes++;
                }
                $token = [
                    $token,
                    $token,
                    $line,
                ];
            } elseif ($token[0] === \T_WHITESPACE) {
                $tokens[$i - 1][2] = $token[1];
                continue;
            } elseif ($token[0] === \T_NAME_FULLY_QUALIFIED || $token[0] === \T_NAME_QUALIFIED || $token[0] === \T_NAME_RELATIVE) {
                $parts = explode("\\", $token[1]);
                for ($k = 0; $k < count($parts); $k++) {
                    if ($parts[$k] !== "") {
                        $tokens[] = [
                            T_STRING,
                            $parts[$k],
                            "",
                            $line = $token[2]
                        ];
                        $i++;
                    }
                    if (isset($parts[$k], $parts[$k+1])) {
                        $tokens[] = [
                            "\\",
                            "\\",
                            "",
                            $line = $token[2]
                        ];
                        $i++;
                    }
                }
                continue;
            } elseif ($token[0] === \T_DNUMBER) { // fix .1 and 1.
                if(str_starts_with($token[1], '.')) {
                    $tokens[] = array(
                        '.',
                        '.',
                        "",
                        $line = $token[2]
                    );
                    $token = array(
                        T_LNUMBER,
                        ltrim($token[1], '.'),
                        $line = $token[2]
                    );
                } elseif(strpos($token[1], '.') === strlen($token[1]) - 1) {
                    $tokens[] = array(
                        T_LNUMBER,
                        rtrim($token[1], '.'),
                        "",
                        $line = $token[2]
                    );
                    $token = array(
                        '.',
                        '.',
                        $line = $token[2]
                    );
                }
            }
            $tokens[] = array(
                $token[0],
                $token[1],
                "",
                $line = $token[2]
            );
            $i++;

        }
        unset($tokens[-1]);
        $this->tokens   = $tokens;
        $this->_max     = count($this->tokens) - 1;
        $this->_last_no = $this->tokens[$this->_max][3];
    }

    /**
     * Is incomplete mean some string not closed
     *
     * @return bool
     */
    public function isIncomplete(): bool
    {
        return ($this->quotes % 2) || ($this->tokens[$this->_max][0] === T_ENCAPSED_AND_WHITESPACE);
    }

    /**
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current(): mixed
    {
        $curr = $this->currToken();
        return $curr ? $curr[1] : null;
    }

    /**
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return Tokenizer
     */
    public function next(): static
    {
        if ($this->p > $this->_max) {
            return $this;
        }
        $this->p++;
        $this->cleanTokenCache();
        return $this;
    }

    /**
     * Check token type. If token type is one of expected types return true. Otherwise return false
     *
     * @param array $expects
     * @param int|string $token
     * @return bool
     */
    private function _valid(array $expects, int|string $token): bool
    {
        foreach ($expects as $expect) {
            if (is_string($expect) || $expect < 1000) {
                if ($expect === $token) {
                    return true;
                }
            } else {

                if (isset(self::$macros[$expect][$token])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * If the next token is a valid one, move the position of cursor one step forward. Otherwise throws an exception.
     * @param array $tokens
     * @return mixed
     * @throws UnexpectedTokenException
     */
    public function _next(array $tokens): void
    {
        $this->next();
        if (!$this->currToken()) {
            throw new UnexpectedTokenException($this, $tokens);
        }
        if ($tokens) {
            if ($this->_valid($tokens, $this->key())) {
                return;
            }
        } else {
            return;
        }
        throw new UnexpectedTokenException($this, $tokens);
    }

    /**
     * Fetch next specified token or throw an exception
     * @return mixed
     */
    public function getNext( /*int|string $token1, int|string $token2, ... */): mixed
    {
        $this->_next(func_get_args());
        return $this->current();
    }

    /**
     * @param $token
     * @return bool
     */
    public function isNextToken($token): bool
    {
        $next = $this->nextToken();
        return $next && $next[1] == $token;
    }

    /**
     * Return token and move pointer
     * @return mixed
     * @throws UnexpectedTokenException
     */
    public function getAndNext( /* $token1, ... */): mixed
    {
        $curr = $this->currToken();
        if ($curr) {
            $cur = $curr[1];
            $this->next();
            return $cur;
        } else {
            throw new UnexpectedTokenException($this, func_get_args());
        }
    }

    /**
     * Check if the next token is one of the specified.
     * @param $token1
     * @return bool
     */
    public function isNext($token1 /*, ...*/): bool
    {
        $next = $this->nextToken();
        return $next && $this->_valid(func_get_args(), $next[0]);
    }

    /**
     * Check if the current token is one of the specified.
     * @param $token1
     * @return bool
     */
    public function is($token1 /*, ...*/): bool
    {
        $curr = $this->currToken();
        return $curr && $this->_valid(func_get_args(), $curr[0]);
    }

    /**
     * Check if the previous token is one of the specified.
     * @param $token1
     * @return bool
     */
    public function isPrev($token1 /*, ...*/): bool
    {
        $prev = $this->prevToken();
        return $prev && $this->_valid(func_get_args(), $prev[0]);
    }

    /**
     * Get specified token
     *
     * @param int|string $token1
     * @return mixed
     *@throws UnexpectedTokenException
     */
    public function get(int|string $token1 /*, $token2 ...*/): mixed
    {
        $curr = $this->currToken();
        if ($curr && $this->_valid(func_get_args(), $curr[0])) {
            return $curr[1];
        } else {
            throw new UnexpectedTokenException($this, func_get_args());
        }
    }

    /**
     * Step back
     * @return Tokenizer
     */
    public function back(): static
    {
        if ($this->p === 0) {
            return $this;
        }
        $this->p--;
        $this->cleanTokenCache();
        return $this;
    }

    /**
     * @param int|string $token1
     * @return bool
     */
    public function hasBackList(int|string $token1 /*, $token2 ...*/): bool
    {
        $tokens = func_get_args();
        $c      = $this->p;
        foreach ($tokens as $token) {
            $c--;
            if ($c < 0 || $this->tokens[$c][0] !== $token) {
                return false;
            }
        }
        return true;
    }

    public function prevToken(): mixed
    {
        if ($this->_prev) {
            return $this->_prev;
        }
        return $this->_prev = $this->p ? $this->tokens[$this->p - 1] : null;
    }

    public function currToken(): mixed
    {
        if ($this->_curr !== null) {
            return $this->_curr;
        }
        return $this->_curr = ($this->p <= $this->_max) ? $this->tokens[$this->p] : null;
    }

    public function nextToken(): mixed
    {
        if ($this->_next) {
            return $this->_next;
        }
        return $this->_next = ($this->p + 1 <= $this->_max) ? $this->tokens[$this->p + 1] : null;
    }

    protected function cleanTokenCache(): void
    {
        $this->_prev = null;
        $this->_curr = null;
        $this->_next = null;
    }

    public function count(): int
    {
        return $this->_max;
    }

    /**
     * Return the key of the current element
     * @return mixed scalar on success, or null on failure.
     */
    public function key(): mixed
    {
        $curr = $this->currToken();
        return $curr ? $curr[0] : null;
    }

    /**
     * Checks if current position is valid
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid(): bool
    {
        return (bool)$this->currToken();
    }

    /**
     * Get token name
     * @static
     * @param mixed $token
     * @return string|null
     */
    public static function getName(mixed $token): ?string
    {
        if (is_string($token)) {
            return $token;
        } elseif (is_integer($token)) {
            return token_name($token);
        } elseif (is_array($token)) {
            return token_name($token[0]);
        } else {
            return null;
        }
    }

    /**
     * Skip specific token or throw an exception
     *
     * @throws UnexpectedTokenException
     * @return Tokenizer
     */
    public function skip( /*$token1, $token2, ...*/): static
    {
        if (func_num_args()) {
            $curr = $this->currToken();
            if ($curr && $this->_valid(func_get_args(), $curr[0])) {
                $this->next();
                return $this;
            } else {
                throw new UnexpectedTokenException($this, func_get_args());
            }
        } else {
            $this->next();
            return $this;
        }
    }

    /**
     * Skip specific token or do nothing
     *
     * @param int|string $token1
     * @return Tokenizer
     */
    public function skipIf(int|string $token1 /*, $token2, ...*/): static
    {
        $curr = $this->currToken();
        if ($curr && $this->_valid(func_get_args(), $curr[0])) {
            $this->next();
        }
        return $this;
    }

    /**
     * Check current token's type
     *
     * @param int|string $token1
     * @return Tokenizer
     * @throws UnexpectedTokenException
     */
    public function need(int|string $token1 /*, $token2, ...*/): static
    {
        $curr = $this->currToken();
        if ($curr && $this->_valid(func_get_args(), $curr[0])) {
            return $this;
        } else {
            throw new UnexpectedTokenException($this, func_get_args());
        }
    }

    /**
     * Get tokens near current position
     * @param int $before count tokens before current token
     * @param int $after count tokens after current token
     * @return array
     */
    public function getSnippet(int $before = 0, int $after = 0): array
    {
        $from = 0;
        $to   = $this->p;
        if ($before > 0) {
            if ($before > $this->p) {
                $from = $this->p;
            } else {
                $from = $before;
            }
        } elseif ($before < 0) {
            $from = $this->p + $before;
            if ($from < 0) {
                $from = 0;
            }
        }
        if ($after > 0) {
            $to = $this->p + $after;
            if ($to > $this->_max) {
                $to = $this->_max;
            }
        } elseif ($after < 0) {
            $to = $this->_max + $after;
            if ($to < $this->p) {
                $to = $this->p;
            }
        } elseif ($this->p > $this->_max) {
            $to = $this->_max;
        }
        $code = array();
        for ($i = $from; $i <= $to; $i++) {
            $code[] = $this->tokens[$i];
        }

        return $code;
    }

    /**
     * Return snippet as string
     * @param int $before
     * @param int $after
     * @return string
     */
    public function getSnippetAsString(int $before = 0, int $after = 0): string
    {
        $str = "";
        foreach ($this->getSnippet($before, $after) as $token) {
            $str .= $token[1] . $token[2];
        }
        return trim(str_replace("\n", '↵', $str));
    }

    /**
     * Check if current is special value: true, TRUE, false, FALSE, null, NULL
     * @return bool
     */
    public function isSpecialVal(): bool
    {
        return isset(self::$spec[$this->current()]);
    }

    /**
     * Check if the token is last
     * @return bool
     */
    public function isLast(): bool
    {
        return $this->p === $this->_max;
    }

    /**
     * Move pointer to the end
     */
    public function end(): static
    {
        $this->p = $this->_max;
        $this->cleanTokenCache();
        return $this;
    }

    /**
     * Return line number of the current token
     * @return mixed
     */
    public function getLine(): mixed
    {
        $curr = $this->currToken();
        return $curr ? $curr[3] : $this->_last_no;
    }

    /**
     * Is current token whitespaced, means previous token has whitespace characters
     * @return bool
     */
    public function isWhiteSpaced(): bool
    {
        $prev = $this->prevToken();
        return $prev && $prev[2];
    }

    public function getWhitespace()
    {
        $curr = $this->currToken();
        return $curr ? $curr[2] : false;
    }

    /**
     * Seek to custom element
     * @param int $p
     * @return $this
     */
    public function seek(int $p): static
    {
        $this->p = $p;
        $this->cleanTokenCache();
        return $this;
    }
}
