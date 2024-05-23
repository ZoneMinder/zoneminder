<?php
require_once __DIR__ .'/../../../includes/Event.php';

App::uses('AppModel', 'Model');
/**
 * Event Model
 *
 * @property Monitor $Monitor
 * @property Frame $Frame
 */
class Event extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Events';

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

  public $virtualFields = array(
    'StartTimeSecs' => 'UNIX_TIMESTAMP(StartDateTime)',
    'EndTimeSecs' => 'UNIX_TIMESTAMP(EndDateTime)',
    'StartTime' => 'StartDateTime',
    'EndTime' => 'EndDateTime'
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
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
    'Storage' =>  array(
      'className' => 'Storage',
      'joinTable' => 'Storage',
      'foreignKey' => 'StorageId',
      'conditions' => '',
      'fields' => '',
      'order' => ''
      )
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Frame' => array(
			'className' => 'Frame',
			'foreignKey' => 'EventId',
			'dependent' => true,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => 'true',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

   /**
    *     *  * hasMany associations
    *         *   *
    *             *    * @var array
    *                 *     */
  public $hasAndBelongsToMany = array(
    'Group' => array(
      'className' => 'Group',
      'joinTable' =>  'Groups_Monitors',
      'foreignKey' => 'MonitorId',
      'associationForeignKey' => 'MonitorId',
      'unique'      =>  true,
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
    'Tag' => array(
      'className' => 'Tag',
      'joinTable' =>  'Events_Tags',
      'foreignKey' => 'EventId',
      'associationForeignKey' => 'TagId',
      'unique'      =>  true,
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

  public $actsAs = array(
    'CakePHP-Enum-Behavior.Enum' => array(
      'Orientation'     => array('ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT'),
      'Scheme'          => array('Deep','Medium','Shallow')
    )
  );

  public function Relative_Path() {
    $Event = ZM\Event::find_one(['Id'=>$this->id]);
    return $Event ? $Event->Relative_Path() : '';
  } // end function Relative_Path()

  public function Path() {
    $Event = ZM\Event::find_one(['Id'=>$this->id]);
    return $Event ? $Event->Path() : '';
  }

  public function Link_Path() {
    $Event = ZM\Event::find_one(['Id'=>$this->id]);
    return $Event ? $Event->Link_Path() : '';
  }

  public function fileExists($event) {
    if ($event['DefaultVideo']) {
      if (file_exists($this->Path().'/'.$event['DefaultVideo'])) {
        return 1;
      } else {
        ZM\Warning('File does not exist at ' . $this->Path().'/'.$event['DefaultVideo'] );
        ZM\Warning(print_r($this, true));
      }
    } else {
      return 0;
    }
  } // end function fileExists($event)

  public function fileSize($event) {
    return filesize($this->Path().'/'.$event['DefaultVideo']);
  }

  public function beforeDelete($cascade=true) {
    $Event = ZM\Event::find_one(['Id'=>$this->id]);
    if ($Event) $Event->delete();
    // Event->delete() will do it all, so cake doesn't have to do anything.
    return false;
  } // end function afterDelete
}
