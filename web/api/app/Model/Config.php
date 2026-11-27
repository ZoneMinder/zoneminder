<?php
App::uses('AppModel', 'Model');
/**
 * Config Model
 *
 */
class Config extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Config';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'Name';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'Value';


	// The Options page refuses a value that is not a number for a numeric option, so an API edit
	// must too.
	public $validate = array(
		'Value' => array(
			'validForType' => array('rule' => array('validForType')),
		),
	);

	public function validForType($check) {
		require_once __DIR__ .'/../../../includes/config_values.php';
		$value = reset($check);
		if (!is_scalar($value)) return true;
		$value = (string)$value;
		$row = $this->find('first', array(
			'conditions' => array('Config.Name' => $this->id),
			'fields' => array('Config.Name', 'Config.Value', 'Config.Type', 'Config.DefaultValue'),
			'recursive' => -1,
		));
		// An unchanged value is not checked again, as on the Options page.
		if (!$row or $value === (string)$row['Config']['Value']) return true;
		$name = $row['Config']['Name'];
		if (!configValueIsValid($value, $row['Config']['Type'], (string)$row['Config']['DefaultValue'])) {
			return $name.' must be a valid '.$row['Config']['Type'];
		}
		return true;
	}

	// Add a find method for returning a hash of the Config table.
	// This is used for the Options view.
	public $findMethods = array('hash' =>  true);
	protected function _findHash($state, $query, $results = array()) {
		if ($state === 'before') {
			return $query;
		}
		$results = Set::combine($results, '{n}.Config.Name', '{n}.Config');
		return $results;
	}

}
