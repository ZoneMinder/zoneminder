<?php
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
  );

  public function Relative_Path($event) {
    $event_path = '';

    if ( $event['Scheme'] == 'Deep' ) {
      $event_path = $event['MonitorId'] .'/'.strftime('%y/%m/%d/%H/%M/%S', strtotime($event['StartTime']));
    } else if ( $event['Scheme'] == 'Medium' ) {
      $event_path = $event['MonitorId'] .'/'.strftime('%Y-%m-%d', strtotime($event['StartTime'])) . '/'.$event['Id'];
    } else {
      $event_path = $event['MonitorId'] .'/'.$event['Id'];
    }

    return $event_path;
  } // end function Relative_Path()


  public function fileExists($event) {
    //$data = $this->findById($id);
    //return $data['Event']['dataset_filename'];
    $storage = $this->Storage->findById($event['StorageId']);

    if ( $event['DefaultVideo'] ) {
      if ( file_exists($storage['Storage']['Path'].'/'.$this->Relative_Path($event).'/'.$event['DefaultVideo']) ) {
        return 1;
      } else {
        Logger::Debug("FIle does not exist at " . $storage['Storage']['Path'].'/'.$this->Relative_Path($event).'/'.$event['DefaultVideo'] );
      }
    } else {
Logger::Debug("No DefaultVideo in Event" . $this->Event);
      return 0;
    }
  } // end function fileExists($event)

  public function fileSize($event) {
    $storage = $this->Storage->findById($event['StorageId']);
    return filesize($storage['Storage']['Path'].'/'.$this->Relative_Path($event).'/'.$event['DefaultVideo']);
  }
}
