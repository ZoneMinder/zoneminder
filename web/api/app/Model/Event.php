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

  public function Relative_Path() {
    $event_path = '';

    if ( $this->data['Scheme'] == 'Deep' ) {
      $event_path = $this->data['MonitorId'] .'/'.strftime('%y/%m/%d/%H/%M/%S', strtotime($this->data['StartTime']));
    } else if ( $event['Scheme'] == 'Medium' ) {
      $event_path = $this->data['MonitorId'] .'/'.strftime('%Y-%m-%d', strtotime($this->data['StartTime'])) . '/'.$this->data['Id'];
    } else {
      $event_path = $this->data['MonitorId'] .'/'.$this->data['Id'];
    }

    return $event_path;
  } // end function Relative_Path()

   public function Path() {
     Warning("Loading storage for " . $this->data['StorageId']);
     $storage = $this->Storage->findById($this->data['StorageId']);
     if ( $storage ) {
     Warning("Loading storage for " . $storage);
     $Storage = $storage['Storage'];
    return $Storage->Path().'/'.$this->Relative_Path();
     } else {
       Error("No storage found for " . $this->data['StorageId']);
     }
  }

  public function Link_Path() {
    if ( $this->data['Scheme'] == 'Deep' ) {
      return $this->data['MonitorId'] .'/'.strftime('%y/%m/%d/.', $this->Time()).$this->data['Id'];
    }
    Error('Calling Link_Path when not using deep storage');
    return '';
  }

  public function fileExists($event) {
    //$data = $this->findById($id);
    //return $data['Event']['dataset_filename'];
    $storage = $this->Storage->findById($event['StorageId']);

    if ( $event['DefaultVideo'] ) {
      if ( file_exists($storage['Storage']['Path'].'/'.$this->Relative_Path().'/'.$event['DefaultVideo']) ) {
        return 1;
      } else {
        Logger::Debug("FIle does not exist at " . $storage['Storage']['Path'].'/'.$this->Relative_Path().'/'.$event['DefaultVideo'] );
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
  public function beforeDelete() {
    if ( !ZM_OPT_FAST_DELETE ) {
      if ( $this->data['Scheme'] == 'Deep' ) {

# Assumption: All events have a start time
        $start_date = date_parse($this->data['StartTime']);
        if ( ! $start_date ) {
          Error('Unable to parse start time for event ' . $this->{'Id'} . ' not deleting files.' );
          return;
        }
        $start_date['year'] = $start_date['year'] % 100;

# So this is because ZM creates a link under the day pointing to the time that the event happened.
        $link_path = $this->Link_Path();
        if ( ! $link_path ) {
          Error('Unable to determine link path for event ' . $this->data['Id'] . ' not deleting files.' );
          return;
        }

        $storage = $this->Storage->findById($this->data['StorageId']);
        $Storage = $storage['Storage'];
        $eventlink_path = $Storage->Path().'/'.$link_path;

        if ( $id_files = glob( $eventlink_path ) ) {
          if ( ! $eventPath = readlink($id_files[0]) ) {
            Error("Unable to read link at $id_files[0]");
            return;
          }
# I know we are using arrays here, but really there can only ever be 1 in the array
          $eventPath = preg_replace( '/\.'.$this->{'Id'}.'$/', $eventPath, $id_files[0] );
          deletePath( $eventPath );
          deletePath( $id_files[0] );
          $pathParts = explode(  '/', $eventPath );
          for ( $i = count($pathParts)-1; $i >= 2; $i-- ) {
            $deletePath = join( '/', array_slice( $pathParts, 0, $i ) );
            if ( !glob( $deletePath."/*" ) ) {
              deletePath( $deletePath );
            }
          }
        } else {
          Warning( "Found no event files under $eventlink_path" );
        } # end if found files
      } else {
        $eventPath = $this->Path();
        deletePath( $eventPath );
      } # USE_DEEP_STORAGE OR NOT
    } # ! ZM_OPT_FAST_DELETE
  } // end function afterDelete
}
