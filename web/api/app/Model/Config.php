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
