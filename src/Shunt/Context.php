<?php

namespace Drupal\lexer_parser\Shunt;

/**
 * PHP Shunting Yard Implementation: Context class.
 */
class Context {
  protected $functions = [];
  protected $constants = ['PI' => M_PI, 'π' => M_PI];
  protected $operatorHandlers = [];

  /**
   * Call a user-defined custom function and returns the result.
   *
   * @param string $name
   *   The name of the function.
   * @param array $args
   *   The arguments to pass to the function.
   *
   * @return float
   *   The result returned from the function.
   *
   * @throws ShuntError
   */
  public function fn($name, array $args) {
    if (!isset($this->functions[$name])) {
      throw new ShuntException('run-time error: undefined function "' . $name . '"');
    }

    return (float) call_user_func_array($this->functions[$name], $args);
  }

  /**
   * Returns the value of a custom-defined constant.
   *
   * @param string $name
   *   Get value of constant.
   *
   * @return mixed
   *   Return value associate or Exception.
   *
   * @throws ShuntError
   */
  public function cs($name) {
    if (!isset($this->constants[$name])) {
      throw new ShuntException('run-time error: undefined constant "' . $name . '"');
    }

    return $this->constants[$name];
  }

  /**
   * Execute operator over two values.
   *
   * @param int $op
   *   Operator integer value (as defined in Token)
   * @param string $lhsValue
   *   The left-hand side operand.
   * @param string $rhsValue
   *   The right-hand side operand.
   *
   * @return float
   *   Value after execute operation.
   *
   * @throws ShuntError
   */
  public function execCustomOperatorHandler($op, $lhsValue, $rhsValue) {
    if (!isset($this->operatorHandlers[$op])) {
      throw new ShuntException('run-time error: undefined operator handler "' . $op . '"');
    }

    return call_user_func_array($this->operatorHandlers[$op], [$lhsValue, $rhsValue]);
  }

  /**
   * Define a custom constant or a function.
   *
   * @param string $name
   *   Name of the constant or function.
   * @param mixed $value
   *   Value of constant or function.
   * @param string $type
   *   Type (float, integer,..)
   *
   * @throws ShuntError
   */
  public function def($name, $value = NULL, $type = 'float') {
    // Wrapper for simple PHP functions.
    if ($value === NULL) {
      $value = $name;
    }

    if (is_callable($value) && $type == 'float') {
      $this->functions[$name] = $value;
    }
    elseif (is_numeric($value) && $type == 'float') {
      $this->constants[$name] = (float) $value;
    }
    elseif (is_string($value) && $type == 'string') {
      $this->constants[$name] = $value;
    }
    else {
      throw new ShuntException('function or number expected');
    }
  }

  /**
   * Register custom handler function for an operator.
   */
  public function defOperator($operator, callable $func) {
    if ($operator & Token::T_OPERATOR == 0) {
      throw new ShuntException('unsupported operator');
    }
    $this->operatorHandlers[$operator] = $func;
  }

  /**
   * Check whether there is a custom handler defined for an operator.
   *
   * @param int $operator
   *   Type of operator.
   *
   * @return bool
   *   TRUE or FALSE if we have a custom handler.
   */
  public function hasCustomOperatorHandler($operator) {
    return isset($this->operatorHandlers[$operator]);
  }

}
