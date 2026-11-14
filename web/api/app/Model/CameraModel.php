<?php
App::uses('AppModel', 'Model');
/**
 * Model CameraModel
 *
 * @property Name $Name
 * @property ManufacturerId $ManufacturerId
 */
class CameraModel extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Models';

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
				'last' => true),
			'unique' => array(
				'rule' => array('isUniquePerManufacturer'),
				'message' => 'That manufacturer already has a model with that name')),
		'ManufacturerId' => array(
			'unique' => array(
				'rule' => array('isUniquePerManufacturer'),
				'message' => 'That manufacturer already has a model with that name')),
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

	public function isUniquePerManufacturer($check) {
		$row = array();
		foreach (array('Name', 'ManufacturerId') as $field) {
			$row[$field] = array_key_exists($field, $this->data[$this->alias])
				? $this->data[$this->alias][$field]
				: ($this->id ? $this->field($field) : null);
		}
		if ($row['ManufacturerId'] === null or $row['ManufacturerId'] === '') return true;
		$conditions = array('CameraModel.Name' => $row['Name'], 'CameraModel.ManufacturerId' => $row['ManufacturerId']);
		if ($this->id) $conditions['CameraModel.Id !='] = $this->id;
		return $this->find('count', array('conditions' => $conditions)) === 0;
	}

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasOne = array(
		'Manufacturer' => array(
			'className' => 'Manufacturer',
      'joinTable' =>  'Manufacturers',
			'foreignKey' => 'Id',
		),
  );
  //var $actsAs = array( 'Containable' );
}
