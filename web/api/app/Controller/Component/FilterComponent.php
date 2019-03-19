<?php
App::uses('Component', 'Controller');

class FilterComponent extends Component {

	/**
	 * Valid MySQL operands that can be used from namedParams with two operands
	 * where the right-hand side (RHS) is a literal.
	 * These came from https://dev.mysql.com/doc/refman/8.0/en/non-typed-operators.html
	 */
	public $twoOperandSQLOperands = array(
					      'AND',
					      '&&',
					      '=',
					      //':=',
					      //'BETWEEN ... AND ...',
					      //'BINARY',
					      '&',
					      '~',
					      //'|',
					      '^',
					      //'CASE',
					      'DIV',
					      '/',
					      '=',
					      '<=>',
					      '>',
					      '>=',
					      'IS',
					      'IS NOT',
					      //'IS NOT NULL',
					      //'IS NULL',
					      //'->',
					      //'->>',
					      '<<',
					      '<',
					      '<=',
					      'LIKE',
					      '-',
					      '%',
					      'MOD',
					      //'NOT',
					      //'!',
					      //'NOT BETWEEN ... AND ...',
					      '!=',
					      '<>',
					      'NOT LIKE',
					      'NOT REGEXP',
					      // `or` operators aren't safe as they can
					      // be used to skip an existing condition
					      // enforcing access to only certain
					      // monitors/events.
					      //'||',
					      //'OR',
					      '+',
					      'REGEXP',
					      '>>',
					      'RLIKE',
					      'SOUNDS LIKE',
					      //'*',
					      '-',
					      //'XOR',
					      );

	// Build a CakePHP find() condition based on the named parameters
	// that are passed in
	public function buildFilter($namedParams) {
		$conditions = array();
		if ($namedParams) {
			foreach ($namedParams as $attribute => $value) {
				// We need to sanitize $attribute to avoid SQL injection.
				$lhs = trim($attribute);
				$matches = NULL;
				if (preg_match('/^(?P<field>[a-z0-9]+)(?P<operator>.+)?$/i', $lhs, $matches) !== 1) {
					throw new Exception('Invalid argument before `:`: ' . $lhs);
				}
				$operator = trim($matches['operator']);

				// Only allow operators on our allow list. No operator
				// specified defaults to `=` by cakePHP.
				if ($operator != '' && !in_array($operator, $this->twoOperandSQLOperands)) {
					throw new Exception('Invalid operator: ' . $operator);
				}

				$lhs = '`' . $matches['field'] . '` ' . $operator;
				// If the named param contains an array, we want to turn it into an IN condition
				// Otherwise, we add it right into the $conditions array
				if (is_array($value)) {
					$array = array();

					foreach ($value as $term) {
						array_push($array, $term);
					}

					$query = array($lhs => $array);
					array_push($conditions, $query);
				} else {
					$conditions[$lhs] = $value;
				}
			}

		}

		return $conditions;
	}

}
?>
