<?php

namespace Drupal\lexer_parser\Shunt;

/**
 * PHP Shunting Yard Implementation: Parser class.
 */
class Parser {
  // Waiting for operand or unary sign.
  const WAITING_FOR_OPERAND_OR_UNARY_SIGN = 1,
          WAITING_FOR_OPERATOR = 2;

  /**
   * Object Scanner.
   *
   * @var Scanner
   */
  protected $scanner;

  /**
   * Current state.
   *
   * @var array
   */
  protected $state = self::WAITING_FOR_OPERAND_OR_UNARY_SIGN;

  /**
   * Original queue.
   *
   * @var array
   */

  protected $queue;
  /**
   * Items processed.
   *
   * @var array
   */
  protected $stack;

  /**
   * A copy of original queue.
   *
   * @var array
   */
  protected $queueCopy;

  /**
   * Construct function with scanner object.
   */
  public function __construct(Scanner $scanner) {
    $this->scanner = $scanner;

    // Init.
    $this->queue = [];
    $this->stack = [];

    // Create queue.
    while (($t = $this->scanner->next()) !== FALSE) {
      $this->handle($t);
    }

    // When there are no more tokens to read.
    // While there are still operator tokens in the stack.
    while ($t = array_pop($this->stack)) {
      if ($t->type === Token::T_POPEN || $t->type === Token::T_PCLOSE) {
        throw new ShuntError('parser error: incorrect nesting of `(` and `)`');
      }

      $this->queue[] = $t;
    }

    // Maintain copy of queue.
    $this->queueCopy = $this->queue;
  }

  /**
   * Reset the queue.
   */
  private function reset() {
    $this->queue = $this->queueCopy;
    $this->scanner->reset();
  }

  /**
   * Consume a item from the queue.
   */
  public function reduce(Context $ctx) {
    $this->reset();
    $this->stack = [];
    $len = 0;

    // While there are input tokens left
    // Read the next token from input.
    while ($t = array_shift($this->queue)) {
      switch ($t->type) {
        case Token::T_NUMBER:
        case Token::T_NULL:
        case Token::T_IDENT:
          // Determine constant value.
          if ($t->type === Token::T_IDENT) {
            $value = $ctx->cs($t->value);
            if ($value === 'null') {
              $t = new Token(Token::T_NULL, NULL);
            }
            else {
              $t = new Token(Token::T_NUMBER, $value);
            }
          }

          // If the token is a value, null or identifier
          // Push it onto the stack.
          $this->stack[] = $t;
          ++$len;
          break;

        case Token::T_PLUS:
        case Token::T_MINUS:
        case Token::T_UNARY_PLUS:
        case Token::T_UNARY_MINUS:
        case Token::T_TIMES:
        case Token::T_DIV:
          // It is known a priori that the operator takes n arguments.
          $na = $this->argc($t);

          // If there are fewer than n values on the stack.
          if ($len < $na) {
            throw new ShuntError('run-time error: too few parameters for operator "' . $t->value . '" (' . $na . ' -> ' . $len . ')');
          }

          $rhs = array_pop($this->stack);
          $lhs = NULL;

          if ($na > 1) {
            $lhs = array_pop($this->stack);
          }

          // If ($lhs) print "{$lhs->value} {$t->value} {$rhs->value}\n";
          // else print "{$t->value} {$rhs->value}\n";.
          $len -= $na - 1;

          // Push the returned results, if any, back onto the stack.
          $operationResult = $this->op($t->type, $lhs, $rhs, $ctx);
          $this->stack[] = new Token(is_null($operationResult) ? Token::T_NULL : Token::T_NUMBER, $operationResult);
          break;

        default:
          throw new ShuntError('run-time error: unexpected token `' . $t->value . '`');
      }
    }

    // If there is only one value in the stack
    // That value is the result of the calculation.
    if (count($this->stack) == 1) {
      return array_pop($this->stack)->value;
    }
    elseif (count($this->stack) == 0) {
      // Empty formula given.
      return NULL;
    }

    // If there are more values in the stack
    // (Error) The user input has too many values.
    throw new ShuntError('run-time error: too many values in the stack');
  }

  /**
   * Apply an operation to two items.
   */
  protected function op($op, $lhs, $rhs, Context $ctx) {
    // If there is a custom operator handler
    // function defined in the context, call it instead.
    if ($ctx->hasCustomOperatorHandler($op)) {
      $lhsValue = is_object($lhs) ? $lhs->value : NULL;
      $rhsValue = is_object($rhs) ? $rhs->value : NULL;
      return $ctx->execCustomOperatorHandler($op, $lhsValue, $rhsValue);
    }

    if ($lhs !== NULL) {
      $lhs = $lhs->value;
      $rhs = $rhs->value;

      switch ($op) {

        case Token::T_PLUS:
          return $lhs + $rhs;

        case Token::T_MINUS:
          return $lhs - $rhs;

        case Token::T_TIMES:
          return $lhs * $rhs;

        case Token::T_DIV:
          if ($rhs === 0.) {
            throw new ShuntError('run-time error: division by zero');
          }

          return $lhs / $rhs;
      }

      return 0;
    }

    switch ($op) {
      case Token::T_UNARY_MINUS:
        return is_null($rhs->value) ? NULL : -$rhs->value;

      case Token::T_UNARY_PLUS:
        return is_null($rhs->value) ? NULL : +$rhs->value;
    }
  }

  /**
   * Get type of argument.
   */
  protected function argc(Token $t) {
    switch ($t->type) {
      case Token::T_PLUS:
      case Token::T_MINUS:
      case Token::T_TIMES:
      case Token::T_DIV:
        return 2;
    }

    return 1;
  }

  /**
   * Get a string dump from an input.
   */
  public function dump($str = FALSE) {
    if ($str === FALSE) {
      print_r($this->queue);

      return;
    }

    $res = [];

    foreach ($this->queue as $t) {
      $val = $t->value;

      switch ($t->type) {
        case Token::T_UNARY_MINUS:
        case Token::T_UNARY_PLUS:
          $val = 'unary' . $val;
          break;
      }

      $res[] = $val;
    }

    print implode(' ', $res);
  }

  /**
   * Get arguments for a function.
   */
  protected function fargs($fn) {
    $argc = $parenthesis = 0;
    // '('.
    $this->handle($this->scanner->next());

    // More tokens?
    if ($this->scanner->peek()) {
      while ($t = $this->scanner->next()) {
        $this->handle($t);

        // Nested parenthesis inside function calls.
        if ($t->type === Token::T_POPEN) {
          $parenthesis++;
        }
        elseif ($t->type === Token::T_PCLOSE && $parenthesis-- === 0) {
          break;
        }

        // At least 1 arg if bracket not closed immediately.
        $argc = max($argc, 1);

        if ($t->type === Token::T_COMMA) {
          ++$argc;
        }
      }
    }

    $fn->argc = $argc;
  }

  /**
   * Process and handle a token.
   */
  protected function handle(Token $t) {
    switch ($t->type) {
      case Token::T_NUMBER:
      case Token::T_NULL:
      case Token::T_IDENT:
        // If the token is a number, NULL or identifier,
        // then add it to the output queue.
        $this->queue[] = $t;
        $this->state = self::WAITING_FOR_OPERATOR;
        break;

      case Token::T_FUNCTION:
        // If the token is a function token, then push it onto the stack.
        $this->stack[] = $t;
        $this->fargs($t);
        break;

      case Token::T_COMMA:
        // If the token is a function argument separator (e.g., a comma):
        $pe = FALSE;

        while ($t = end($this->stack)) {
          if ($t->type === Token::T_POPEN) {
            $pe = TRUE;
            break;
          }

          // Until the token at the top of the stack is a left parenthesis,
          // pop operators off the stack onto the output queue.
          $this->queue[] = array_pop($this->stack);
        }

        // If no left parentheses are encountered,
        // either the separator was misplaced
        // or parentheses were mismatched.
        if ($pe !== TRUE) {
          throw new ShuntError('parser error: missing token `(` or misplaced token `,`');
        }

        break;

      // If the token is an operator, op1, then:
      case Token::T_PLUS:
      case Token::T_MINUS:
      case Token::T_UNARY_PLUS:
      case Token::T_UNARY_MINUS:
      case Token::T_TIMES:
      case Token::T_DIV:
        while (!empty($this->stack)) {
          $s = end($this->stack);

          // While there is an operator token, o2, at the top of the stack
          // op1 is left-associative and its precedence is less
          // than or equal to that of op2,
          // or op1 has precedence less than that of op2,
          // Let + and ^ be right associative.
          // Correct transformation from 1^2+3 is 12^3+
          // The differing operator priority decides pop / push
          // If 2 operators have equal priority then associativity decides.
          switch ($s->type) {
            default:
              break 2;
            case Token::T_PLUS:
            case Token::T_MINUS:
            case Token::T_UNARY_PLUS:
            case Token::T_UNARY_MINUS:
            case Token::T_TIMES:
            case Token::T_DIV:
              $p1 = $this->preced($t);
              $p2 = $this->preced($s);

              if (!(($this->assoc($t) === 1 && ($p1 <= $p2)) || ($p1 < $p2))) {
                break 2;
              }

              // Pop o2 off the stack, onto the output queue;.
              $this->queue[] = array_pop($this->stack);
          }
        }

        // Push op1 onto the stack.
        $this->stack[] = $t;
        $this->state = self::WAITING_FOR_OPERAND_OR_UNARY_SIGN;
        break;

      case Token::T_POPEN:
        // If the token is a left parenthesis, then push it onto the stack.
        $this->stack[] = $t;
        $this->state = self::WAITING_FOR_OPERAND_OR_UNARY_SIGN;
        break;

      // If the token is a right parenthesis:
      case Token::T_PCLOSE:
        $pe = FALSE;

        // Until the token at the top of the stack is a left parenthesis,
        // pop operators off the stack onto the output queue.
        while ($t = array_pop($this->stack)) {
          if ($t->type === Token::T_POPEN) {
            // Pop the left parenthesis from the stack,
            // but not onto the output queue.
            $pe = TRUE;
            break;
          }

          $this->queue[] = $t;
        }

        // If the stack runs out without finding a left parenthesis,
        // then there are mismatched parentheses.
        if ($pe !== TRUE) {
          throw new ShuntError('parser error: unexpected token `)`');
        }

        // If the token at the top of the stack
        // is a function token, pop it onto the output queue.
        if (($t = end($this->stack)) && $t->type === Token::T_FUNCTION) {
          $this->queue[] = array_pop($this->stack);
        }

        $this->state = self::WAITING_FOR_OPERATOR;
        break;

      default:
        throw new ShuntError('parser error: unknown token "' . $t->value . '"');
    }
  }

  /**
   * Get a associate item to the token.
   */
  protected function assoc(Token $t) {
    switch ($t->type) {

      case Token::T_TIMES:
      case Token::T_DIV:
      case Token::T_PLUS:
      case Token::T_MINUS:
        // Ltr.
        return 1;

      case Token::T_UNARY_PLUS:
      case Token::T_UNARY_MINUS:
        // Rtl.
        return 2;

    }

    return 0;
  }

  /**
   * Get preced token.
   */
  protected function preced(Token $t) {
    switch ($t->type) {
      case Token::T_UNARY_PLUS:
      case Token::T_UNARY_MINUS:
        return 9;

      case Token::T_TIMES:
      case Token::T_DIV:
        return 7;

      case Token::T_PLUS:
      case Token::T_MINUS:
        return 6;
    }

    return 0;
  }

  /**
   * Parse a term with a Context.
   */
  public static function parse($term, Context $ctx = NULL) {
    $obj = new self(new Scanner($term));

    return $obj
      ->reduce($ctx ?: new Context());
  }

}
