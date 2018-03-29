<?php
App::uses('Component', 'Controller');
class FilterComponent extends Component {

	// Build a CakePHP find() condition based on the named parameters
	// that are passed in
	public function buildFilter($namedParams) {
		if ($namedParams) { 
			$conditions = array();

			foreach ($namedParams as $attribute => $value) {
				// If the named param contains an array, we want to turn it into an IN condition
				// Otherwise, we add it right into the $conditions array
				if (is_array($value)) {
					$array = array();

					foreach ($value as $term) {
						array_push($array, $term);
					}

					$query = array($attribute => $array);
					array_push($conditions, $query);
				} else {
					$conditions[$attribute] = $value;
				}
			}

		}

		return $conditions;
	}

}
?>
