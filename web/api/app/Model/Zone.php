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
    $units = isset($data['Units']) ? $data['Units'] : ($this->id ? $this->field('Units') : 'Percent');
    if (isset($data['Coords']) and $units == 'Percent'
        and is_string($data['Coords']) and strpos($data['Coords'], '.') === false and $this->validCoords(array($data['Coords']))) {
      $data['Coords'] = preg_replace_callback('/-?\d+/', function($m) { return $m[0].'.00'; }, $data['Coords']);
    }
    // The web editor (includes/actions/zone.php) keeps NumCoords and Area in step with Coords
    // and stores thresholds as a percentage of the zone, so do the same here.
    if (isset($data['Coords']) and is_string($data['Coords']) and $this->validCoords(array($data['Coords']))
        and preg_match_all('/(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $data['Coords'], $m, PREG_SET_ORDER) >= 3) {
      $n = count($m);
      $sum = 0.0;
      for ($i = 0; $i < $n; $i++) {
        $next = $m[($i + 1) % $n];
        $sum += (float)$m[$i][1] * (float)$next[2] - (float)$next[1] * (float)$m[$i][2];
      }
      $data['NumCoords'] = $n;
      $data['Area'] = round(abs($sum) / 2.0);
    }
    // zmc reads thresholds as a percentage of the zone when Coords have decimals. A value
    // over 100 is a pixel count, so convert it with the zone's size in pixels. An edit that
    // sends no Coords is measured against the zone's stored Coords and Area.
    $fields = array('MinAlarmPixels', 'MaxAlarmPixels', 'MinFilterPixels', 'MaxFilterPixels', 'MinBlobPixels', 'MaxBlobPixels');
    $pixelCounts = array_filter($fields, function($field) use ($data) {
      return isset($data[$field]) and is_numeric($data[$field]) and floatval($data[$field]) > 100;
    });
    if (count($pixelCounts)) {
      if (isset($data['Coords'])) {
        $coords = $data['Coords'];
        $area = isset($data['Area']) ? $data['Area'] : 0;
      } else {
        $coords = $this->id ? $this->field('Coords') : null;
        $area = $this->id ? $this->field('Area') : 0;
      }
      if (is_string($coords) and strpos($coords, '.') !== false and $area > 0) {
        $monitorId = isset($data['MonitorId']) ? $data['MonitorId'] : ($this->id ? $this->field('MonitorId') : null);
        $monitor = $monitorId ? $this->Monitor->find('first', array('conditions' => array('Id' => $monitorId))) : null;
        if ($monitor and !empty($monitor['Monitor']['Width']) and !empty($monitor['Monitor']['Height'])) {
          $pixels = $area / 10000.0 * $monitor['Monitor']['Width'] * $monitor['Monitor']['Height'];
          foreach ($pixelCounts as $field) {
            $data[$field] = min(100, round(floatval($data[$field]) / $pixels * 100, 2));
          }
        }
      }
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
