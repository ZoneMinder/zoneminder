<?php
App::uses('AppModel', 'Model');
/**
 * Frame Model
 *
 * @property Event $Event
 */
class Frame extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Frames';

/**
 * Primary key field
 *
 * The real key is (EventId, FrameId), which CakePHP 2 cannot express. EventId
 * is its leftmost column, and is what Event's dependent hasMany cascade
 * resolves Frames by, so deleting an event deletes exactly its frames.
 * FramesController addresses single frames by both columns and never relies
 * on save(), exists() or delete() by this key.
 *
 * @var string
 */
	public $primaryKey = 'EventId';

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array(
		'EventId' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
			'notEmpty' => array(
				'rule' => array('notBlank'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'FrameId' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
			'notEmpty' => array(
				'rule' => array('notBlank'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'Type' => array(
			'multiple' => array(
				'rule' => array('typeGiven'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'TimeStamp' => array(
			'time' => array(
				'rule' => array('datetime'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'Delta' => array(
			'numeric' => array(
				'rule' => array('numeric'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'Score' => array(
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

	// Cake's multiple rule throws a TypeError for a nested list; AppModel::beforeValidate() refuses a list.
	public function typeGiven($check) {
		$value = reset($check);
		return is_array($value) ? count($value) > 0 : strlen((string)$value) > 0;
	}

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Event' => array(
			'className' => 'Event',
			'foreignKey' => 'EventId',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	public $recursive = -1;
  public $virtualFields = array('TimeStampSecs' => 'UNIX_TIMESTAMP(Frame.TimeStamp)');
}
