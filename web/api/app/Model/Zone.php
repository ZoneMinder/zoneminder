<?php
App::uses('AppModel', 'Model');
/**
 * Zone Model
 *
 * @property Monitor $Monitor
 */
class Zone extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Zones';

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
    'MonitorId' => array(
      'rule' => 'checkMonitorId',
      //array('naturalNumber'),
      'message' => 'Zones must have a valid MonitorId',
      'allowEmpty' => false,
        //'last' => false, // Stop validation after this rule
        //'on' => 'create', // Limit validation to 'create' or 'update' operations
    ),
    'Name' => array(
       'required' => array(
         'rule'       => 'notBlank',
         'message'    => 'Zone Name must be specified for creation',
       ),
     )
  );

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Monitor' => array(
			'className' => 'Monitor',
			'foreignKey' => 'MonitorId',
			//'conditions' => '',
			//'fields' => '',
			//'order' => ''
		)
	);

  public function checkMonitorId($data) {
    if ( !$this->Monitor->find('first', array('conditions'=>array('Id'=>$data['MonitorId']))) ) {
      //$this->invalidate('MonitorId', 'Invalid Monitor Id');
      return false;
    }
    return true;
  }
}
