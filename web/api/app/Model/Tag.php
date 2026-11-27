<?php
App::uses('AppModel', 'Model');
/**
 * Tag Model
 *
 */
class Tag extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Tags';

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
				'rule' => array('notBlank')),
			'unique' => array(
				'rule' => array('uniqueIgnoringCase'),
				'message' => 'A tag with that name already exists')),
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

	// Tag names differing only in case look the same in the tag list, and the web UI reuses the
	// existing tag instead of making another. The column is case sensitive, so isUnique and the
	// unique index let them through. A tag keeps its own name as it is, even if it already has a
	// twin from before this check.
	public function uniqueIgnoringCase($check) {
		$name = reset($check);
		if ($this->id) {
			$own = $this->query('SELECT Name FROM Tags WHERE Id = ?', array($this->id));
			if ($own and $own[0]['Tags']['Name'] === $name) return true;
		}
		$rows = $this->query('SELECT Id FROM Tags WHERE LOWER(Name) = LOWER(?) AND Id != ? LIMIT 1',
			array($name, $this->id ? $this->id : 0));
		return !$rows;
	}

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasAndBelongsToMany = array(
		'Event' => array(
			'className' => 'Event',
      'joinTable' => 'Events_Tags',
			'foreignKey' => 'TagId',
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
  var $actsAs = array( 'Containable' );
}
