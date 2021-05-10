<?php
App::uses('AppModel', 'Model');
/**
 * Group Model
 *
 * @property Event $Event
 * @property Zone $Zone
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
		'Name' => array(
			'notBlank' => array(
				'rule' => array('notBlank'))),
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
	public $hasAndBelongsToMany = array(
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
  var $actsAs = array( 'Containable' );
}
