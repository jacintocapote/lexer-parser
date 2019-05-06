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
    try {
      $output = Parser::parse($input);
    }
    catch (ShuntError $e) {
      $output = t('Error');
    }

    return $output;
  }

}
