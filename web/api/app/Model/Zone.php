<?php
App::uses('AppModel', 'Model');
/**
 * Zone Model
 *
 * @property Monitor $Monitor
 */
class Zone extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Zones';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'Id';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'Name';


	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Monitor' => array(
			'className' => 'Monitor',
			'foreignKey' => 'MonitorId',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
