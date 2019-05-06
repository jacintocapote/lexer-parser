<?php

/**
 * PHP Shunting Yard Implementation.
 * Token class.
 *
 */

namespace Drupal\lexer_parser\Shunt;

class Token {
  const T_NUMBER          = 1,  // a number (integer / double)
        T_IDENT           = 2,  // constant
        T_FUNCTION        = 4,  // function
        T_POPEN           = 8,  // (
        T_PCLOSE          = 16, // )
        T_COMMA           = 32, // ,
        T_OPERATOR        = 64, //
        T_PLUS            = 65, // +
        T_MINUS           = 66, // -
        T_TIMES           = 67, // *
        T_DIV             = 68, // /
        T_UNARY_PLUS      = 71, // + unsigned number (determined during parsing)
        T_UNARY_MINUS     = 72, // - signed number (determined during parsing)
        T_NULL            = 128; // null

  public $type;
  public $value;
  public $argc = 0;

  public function __construct($type, $value) {
    $this->type  = $type;
    $this->value = $value;
  }

}
