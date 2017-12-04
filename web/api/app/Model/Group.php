<?php
App::uses('AppModel', 'Model');
/**
 * Group Model
 *
 * @property Event $Event
 */
class Group extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Groups';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'Id';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'Name' => array(
			'notEmpty' => array(
				'rule' => array('notEmpty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);

	public $recursive = -1;
	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Monitor' => array(
			'className' => 'Monitor',
      'joinTable' =>  'Groups_Monitors',
			'foreignKey' => 'GroupId',
			'associationForeignKey' => 'MonitorId',
      'unique'=>true,
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),
  );
}
