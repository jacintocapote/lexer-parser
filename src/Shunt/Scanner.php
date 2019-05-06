<?php

namespace Drupal\lexer_parser\Shunt;

/**
 * PHP Shunting Yard Implementation: Scanner class.
 */
class Scanner {

  const PATTERN = '/^([,\+\-\*\/\(\)]|\d*\.\d+|\d+\.\d*|\d+|[a-z_A-Zπ]+[a-z_A-Z0-9]*|[ \t]+)/';

  const ERR_EMPTY = 'nothing found! (endless loop) near: `%s`';
  const ERR_MATCH = 'syntax error near `%s`';

  protected $tokens = [0];

  protected $lookup = [
    '+' => Token::T_PLUS,
    '-' => Token::T_MINUS,
    '/' => Token::T_DIV,
    '*' => Token::T_TIMES,
    '(' => Token::T_POPEN,
    ')' => Token::T_PCLOSE,
  ];

  /**
   * Construct where we process input.
   */
  public function __construct($input) {
    $prev = new Token(Token::T_OPERATOR, 'noop');

    while (trim($input) !== '') {
      if (!preg_match(self::PATTERN, $input, $match)) {
        // Syntax error.
        throw new ShuntException(sprintf(self::ERR_MATCH, substr($input, 0, 10)));
      }

      if (empty($match[1]) && $match[1] !== '0') {
        // Nothing found -> avoid endless loop.
        throw new ShuntException(sprintf(self::ERR_EMPTY, substr($input, 0, 10)));
      }

      // Remove the first matched token from the input, for the next iteration.
      $input = substr($input, strlen($match[1]));

      // Get the value of the matched token.
      $value = trim($match[1]);

      // Ignore whitespace matches.
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

      // Unless token is one of the predefined symbols
      // , consider it an identifier token.
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
              // Allowed 2(2) -> 2 * 2 | (2)(2) -> 2 * 2.
              $this->tokens[] = new Token(Token::T_TIMES, '*');
              break;
          }
          break;

        case Token::T_IDENT:
          if (strcasecmp($value, 'null') == 0) {
            $tokenType = Token::T_NULL;
            $value = NULL;
          }
      }

      $this->tokens[] = $prev = new Token($tokenType, $value);
    }
  }

  /**
   * Reset the queue to process.
   */
  public function reset() {
    reset($this->tokens);
  } // call before reusing Scanner instance

  /**
   * Get current process item.
   */
  public function curr() {
    return current($this->tokens);
  }

  /**
   * Get next item to process.
   */
  public function next() {
    return next($this->tokens);
  }

  /**
   * Get previous item to process.
   */
  public function prev() {
    return prev($this->tokens);
  }

  /**
   * Get info from tokens.
   */
  public function dump() {
    return $this->tokens;
  }

  /**
   * Get peek from the queue.
   */
  public function peek() {
    $v = next($this->tokens);
    prev($this->tokens);

    return $v;
  }

}
