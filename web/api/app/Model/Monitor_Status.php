<?php
App::uses('AppModel', 'Model');
/**
 * Monitor_Status Model
 *
 * @property Event $Event
 * @property Zone $Zone
 */
class Monitor_Status extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Monitor_Status';

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
	public $displayField = 'Status';

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

  public $actsAs = array(
    'CakePHP-Enum-Behavior.Enum' => array(
      'Status'          => array('Unknown','NotRunning','Running','NoSignal','Signal'),
    )
  );

	//The Associations below have been created with all possible keys, those that are not needed can be removed
}
