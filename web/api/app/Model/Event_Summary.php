<?php
App::uses('AppModel', 'Model');
/**
 * Event_Summary Model
 *
 */
class Event_Summary extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Event_Summaries';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'MonitorId';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'MonitorId';

	public $recursive = -1;

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'MonitorId' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);
}
