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
				'rule' => array('notBlank'),
				'required' => 'create',
				'message' => 'A group needs a name')),
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
		'ParentId' => array(
			'exists' => array(
				'rule' => array('parentExists'),
				'message' => 'ParentId does not match a group',
			),
		),
	);

  // ParentId is a foreign key to Groups.Id, so a missing parent is refused here
  // instead of by MySQL as a 500. Empty and null mean no parent.
  public function parentExists($check) {
    $value = reset($check);
    if ($value === null or $value === '') {
      return true;
    }
    // AppModel::beforeValidate() already refuses an array.
    if (!is_scalar($value)) {
      return true;
    }
    return (bool)$this->find('first', array(
      'conditions' => array('Group.Id' => $value),
      'recursive' => -1,
    ));
  }

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
