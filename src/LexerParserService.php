<?php


namespace Drupal\lexer_parser;

use Drupal\lexer_parser\Shunt\Parser;

/**
 * Drupal 8 service for lexer parser a string.
 */
class LexerParserService {

  /**
   * Returns a parser string.
   */
  public function parserString($input) {
    $trm = '3 + 4 * 2 / ( 1 - 5 ) ^ 2 ^ 3';
    return Parser::parse($input);
  }

}
