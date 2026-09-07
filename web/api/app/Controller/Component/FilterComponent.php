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
                'IN',
					      );

  public $paramsToFilterOut = array(
    'p', // Used in nginx api rewriting
    'sort',
    'page',
    'limit',
    'token',
    'direction'
  );

	// Build a CakePHP find() condition based on the named parameters
	// that are passed in.
	//
	// $validFields, when given, is the list of field names a caller may filter
	// on. Without it an unknown name is passed straight through into the SQL and
	// surfaces as a PDOException, so a caller asking for a field that does not
	// exist - or one that lives on Monitors rather than the model being queried,
	// such as MonitorName - got an opaque 500 with a stack trace rather than a
	// message naming the field. Callers that have not been given a list keep the
	// old behaviour.
	public function buildFilter($namedParams, $validFields = null) {
		$conditions = array();
		if ($namedParams) {
			foreach ($namedParams as $attribute => $value) {
				// We need to sanitize $attribute to avoid SQL injection.
				$lhs = trim($attribute);
        if ($lhs and in_array($lhs, $this->paramsToFilterOut)) {
          continue;
        }
				$matches = NULL;
				if (preg_match('/^(?P<field>[a-z0-9\.]+)(?P<operator>.+)?$/i', $lhs, $matches) !== 1) {
					throw new BadRequestException('Invalid argument before `:`: ' . $lhs);
				}
				$operator = isset($matches['operator']) ? trim($matches['operator']) : '';

				// Only allow operators on our allow list. No operator
				// specified defaults to `=` by cakePHP.
				if ($operator != '' && !in_array($operator, $this->twoOperandSQLOperands)) {
					throw new BadRequestException('Invalid operator: ' . $operator);
				}

				if ($validFields !== null) {
					// A field may be written Model.Field; only the column is checked.
					$field = $matches['field'];
					$bare = (false !== strpos($field, '.'))
						? substr($field, strrpos($field, '.') + 1)
						: $field;
					if (!in_array($bare, $validFields)) {
						throw new BadRequestException('Unknown filter field: ' . $bare);
					}
				}
        if (($operator == 'LIKE') and (false === strpos($value, '%'))) {
          $value = '%'.$value.'%';
        } else if ($operator == 'IN') {
          $value = explode(',', $value);
        }

				$lhs = $matches['field'] . ($operator?' ' . $operator:'');
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
		}  // end if namedparams

		return $conditions;
	}
}
?>
