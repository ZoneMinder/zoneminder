<?php
App::uses('AppModel', 'Model');
/**
 * Storage Model
 *
 * @property Event $Event
 * @property Zone $Zone
 */
class Storage extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Storage';

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

	public $recursive = -1;

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'Id' => array(
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

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Server' => array(
			'className' => 'Server',
			'foreignKey' => 'ServerId',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
		)
	);
}
