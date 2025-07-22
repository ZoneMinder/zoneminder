<?php
App::uses('AppModel', 'Model');
/**
 * UserPreference Model
 *
 * @property User $User
 */
class UserPreference extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'User_Preferences';

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
		'UserId' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
			//'notEmpty' => array(
				//'rule' => array('notEmpty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
		),
		'Name' => array(
			'text' => array(
				'rule' => array('text'),
			),
		),
		'Value' => array(
			'text' => array(
				'rule' => array('text'),
			),
		),
	);

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'UserId',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	public $recursive = -1;
}
