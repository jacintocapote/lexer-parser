<?php

namespace Drupal\Tests\lexer_parser\Unit;

use Drupal\lexer_parser\LexerParserService;
use Drupal\Tests\UnitTestCase;

/**
 * Test implementation for service LexerParserService.
 *
 * @group lexer_parser
 */
class TestLexerParserTest extends UnitTestCase {
  /**
   * The service to test.
   *
   * @var \Drupal\lexer_parser\LexerParserService
   */
  public $lexerParserService;

  /**
   * Assign service.
   */
  public function setUp() {
    $this->conversionService = new LexerParserService();
  }

  /**
   * A simple test that tests our parserString() function.
   *
   * TODO: Create new test to check Exceptions.
   *
   * @dataProvider providerTokenToOutput
   */
  public function testConversions($token, $expectedOutput) {
    $this->assertEquals($expectedOutput, $this->conversionService->parserString($token));
  }

  /**
   * Provides mathematical operations to eval value.
   *
   * @return array
   *   Return a input/output value.
   */
  public function providerTokenToOutput() {
    return [
      ['10 + 20 - 30+ 15 * 5', 75],
      ['10 + 20', 30],
      ['10 - 10', 0],
      ['10*10', 100],
      ['10/10', 1],
    ];
  }

}
