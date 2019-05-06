<?php

namespace Drupal\lexer_parser;

use Drupal\lexer_parser\Shunt\Parser;
use Drupal\lexer_parser\Shunt\Token;
use Drupal\lexer_parser\Shunt\ShuntException;

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
    catch (ShuntException $e) {
      $output = $e->getMessage();
    }

    return $output;
  }

  /**
   * Returns a tree associated to an input.
   */
  public function parserTree($input) {
    $strings_parser = [
      Token::T_NUMBER => t('Number'),
      Token::T_IDENT => t('Constant'),
      Token::T_FUNCTION => t('Function'),
      Token::T_POPEN => t('Operator'),
      Token::T_PCLOSE => t('Operator'),
      Token::T_COMMA => t('Operator'),
      Token::T_OPERATOR => t('Operator'),
      Token::T_PLUS => t('Operator'),
      Token::T_MINUS => t('Operator'),
      Token::T_TIMES => t('Operator'),
      Token::T_DIV => t('Operator'),
      Token::T_UNARY_PLUS => t('Operator'),
      Token::T_UNARY_MINUS => t('Operator'),
      Token::T_NULL => t('Operator'),
    ];
    try {
      $tree_raw = Parser::parseDump($input);
    }
    catch (ShuntException $e) {
      $tree = [];
    }

    if (!empty($tree_raw)) {
      // Delete first item.
      unset($tree_raw[0]);
      foreach ($tree_raw as $index => $item) {
        $tree[] = [
          'type' => $strings_parser[$item->type],
          'value' => $item->value,
        ];
      }
    }
    else {
      $tree = [];
    }

    return $tree;
  }

}
