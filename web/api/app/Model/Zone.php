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
    // The column has no default, so a zone added without it is refused by MySQL.
    'Coords' => array(
      'required' => array(
        'rule'       => 'notBlank',
        'required'   => 'create',
        'message'    => 'Zone Coords must be specified for creation',
      ),
      'format' => array(
        'rule'       => 'validCoords',
        'message'    => 'Zone Coords must be at least 3 points written x,y x,y x,y',
      ),
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

  public function validCoords($check) {
    $value = reset($check);
    if ($value === null or $value === '') {
      return true;
    }
    $number = '-?\d+(?:\.\d+)?';
    return (bool)preg_match('/^\s*'.$number.','.$number.'(?:\s+'.$number.','.$number.'){2,}\s*$/', $value);
  }

  // zmc reads coordinates with a decimal point as percentages and the rest as pixels, whatever
  // Units says, so percentages are written with decimals.
  public function beforeSave($options = array()) {
    $data =& $this->data[$this->alias];
    $units = isset($data['Units']) ? $data['Units'] : ($this->id ? null : 'Percent');
    if (isset($data['Coords']) and $units == 'Percent'
        and is_string($data['Coords']) and strpos($data['Coords'], '.') === false and $this->validCoords(array($data['Coords']))) {
      $data['Coords'] = preg_replace_callback('/-?\d+/', function($m) { return $m[0].'.00'; }, $data['Coords']);
    }
    return true;
  }

  public function checkMonitorId($data) {
    if ( !$this->Monitor->find('first', array('conditions'=>array('Id'=>$data['MonitorId']))) ) {
      //$this->invalidate('MonitorId', 'Invalid Monitor Id');
      return false;
    }
    return true;
  }
}
