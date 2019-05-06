<?php

/**
 * PHP Shunting Yard Implementation.
 * Scanner class.
 *
 */

namespace Drupal\lexer_parser\Shunt;

use Drupal\lexer_parser\Shunt\Token;

class Scanner {
    //              operator_________________________________|number_______________|word____________________|space_
    const PATTERN = '/^([,\+\-\*\/\(\)]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Zπ]+[a-z_A-Z0-9]*|[ \t]+)/';

    const ERR_EMPTY = 'nothing found! (endless loop) near: `%s`';
    const ERR_MATCH = 'syntax error near `%s`';

    protected $tokens = [ 0 ];

    protected $lookup = [
        '+' => Token::T_PLUS,
        '-' => Token::T_MINUS,
        '/' => Token::T_DIV,
        '*' => Token::T_TIMES,
        '(' => Token::T_POPEN,
        ')' => Token::T_PCLOSE
    ];

    public function __construct($input) {
        $prev = new Token(Token::T_OPERATOR, 'noop');

        while (trim($input) !== '') {
            if (!preg_match(self::PATTERN, $input, $match)) {
                // syntax error
                return sprintf(self::ERR_MATCH, substr($input, 0, 10));
            }

            if (empty($match[1]) && $match[1] !== '0') {
                // nothing found -> avoid endless loop
                return sprintf(self::ERR_EMPTY, substr($input, 0, 10));
            }

            // Remove the first matched token from the input, for the next iteration
            $input = substr($input, strlen($match[1]));

            // Get the value of the matched token
            $value = trim($match[1]);

            // Ignore whitespace matches
            if ($value === '') {
                continue;
            }

            if (is_numeric($value)) {
                if ($prev->type === Token::T_PCLOSE) {
                    $this->tokens[] = new Token(Token::T_TIMES, '*');
                }

                $this->tokens[] = $prev = new Token(Token::T_NUMBER, (float) $value);
                continue;
            }

            // Unless token is one of the predefined symbols, consider it an identifier token
            $tokenType = isset($this->lookup[$value]) ? $this->lookup[$value] : Token::T_IDENT;

            switch ($tokenType) {
                case Token::T_PLUS:
                    if ($prev->type & Token::T_OPERATOR || $prev->type == Token::T_POPEN || $prev->type == Token::T_COMMA) {
                        $tokenType = Token::T_UNARY_PLUS;
                    }
                    break;

                case Token::T_MINUS:
                    if ($prev->type & Token::T_OPERATOR || $prev->type == Token::T_POPEN || $prev->type == Token::T_COMMA) {
                        $tokenType = Token::T_UNARY_MINUS;
                    }
                    break;

                case Token::T_POPEN:
                    switch ($prev->type) {
                        case Token::T_IDENT:
                            $prev->type = Token::T_FUNCTION;
                            break;

                        case Token::T_NUMBER:
                        case Token::T_PCLOSE:
                            // allowed 2(2) -> 2 * 2 | (2)(2) -> 2 * 2
                            $this->tokens[] = new Token(Token::T_TIMES, '*');
                            break;
                    }
                    break;

                case Token::T_IDENT:
                    if (strcasecmp($value, 'null') == 0) {
                        $tokenType = Token::T_NULL;
                        $value = null;
                    }
            }

            $this->tokens[] = $prev = new Token($tokenType, $value);
        }
    }

    public function reset() {
        reset($this->tokens);
    } // call before reusing Scanner instance

    public function curr() {
        return current($this->tokens);
    }

    public function next() {
        return next($this->tokens);
    }

    public function prev() {
        return prev($this->tokens);
    }

    public function dump() {
        print_r($this->tokens);
    }

    public function peek() {
        $v = next($this->tokens);
        prev($this->tokens);

        return $v;
    }

}
