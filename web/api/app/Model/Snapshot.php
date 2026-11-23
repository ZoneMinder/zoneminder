<?php
App::uses('AppModel', 'Model');
/**
 * Snapshot Model
 *
 * @property Event $Event
 * @property Zone $Zone
 */
class Snapshot extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Snapshots';

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
 * hasAndBelongsToMany associations
 *
 * @var array
 */
  public $hasAndBelongsToMany = array(
    'Event' => array(
      'className' => 'Event',
      'joinTable' =>  'Snapshots_Events',
      'foreignKey' => 'SnapshotId',
      'associationForeignKey' => 'EventId',
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

  public $actsAs = array('Containable');

  // The web action records who took the snapshot and when; the columns have no default.
  public function beforeSave($options = array()) {
    if (!$this->id) {
      global $user;
      if ($user) {
        $this->data[$this->alias]['CreatedBy'] = $user->Id();
      } else {
        unset($this->data[$this->alias]['CreatedBy']);
      }
      $this->data[$this->alias]['CreatedOn'] = date('Y-m-d H:i:s');
    } else {
      // Ownership and creation time are immutable once set.
      unset($this->data[$this->alias]['CreatedBy']);
      unset($this->data[$this->alias]['CreatedOn']);
    }
    return true;
  }
}
