<?php

namespace Drupal\lexer_parser\Shunt;

/**
 * PHP Shunting Yard Implementation: Token class.
 */
class Token {
  // A number (integer / double)
  const T_NUMBER = 1,
  // Constant.
        T_IDENT = 2,
  // function.
        T_FUNCTION = 4,
  // (.
        T_POPEN = 8,
  // )
        T_PCLOSE = 16,
  // ,.
        T_COMMA = 32,
  // .
        T_OPERATOR = 64,
  // +.
        T_PLUS = 65,
  // -.
        T_MINUS = 66,
  // *.
        T_TIMES = 67,
  // /.
        T_DIV = 68,
  // + unsigned number (determined during parsing)
        T_UNARY_PLUS = 71,
  // - signed number (determined during parsing)
        T_UNARY_MINUS = 72,
  // NULL.
        T_NULL = 128;

  /**
   * Value type.
   *
   * @var int
   */
  public $type;

  /**
   * Value for a type.
   *
   * @var int
   */
  public $value;

  /**
   * Default value for argument.
   *
   * @var int
   */
  public $argc = 0;

  /**
   * Construct to assign a type and value.
   */
  public function __construct($type, $value) {
    $this->type = $type;
    $this->value = $value;
  }

}
