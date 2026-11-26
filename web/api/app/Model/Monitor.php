<?php
App::uses('AppModel', 'Model');
/**
 * Monitor Model
 *
 * @property Event $Event
 * @property Zone $Zone
 */
class Monitor extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Monitors';

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
    'Name' => array(
       'required' => array(
         'on'         => 'create',
         'rule'       => 'notBlank',
         'message'    => 'Monitor Name must be specified for creation',
         'required'   => true,
       ),
       'notBlank' => array(
         'rule'       => 'notBlank',
         'message'    => 'Monitor Name must not be blank',
       ),
       'characters' => array(
         'rule'       => array('custom', '/^[\w\p{L}\p{M}\p{N}\-\.\(\)\:\/ ]+$/u'),
         'message'    => 'Monitor Name may only contain letters, numbers, spaces and - . ( ) : /',
       ),
    ),
    'Width' => array(
      'rule' => array('comparison', '>', 0),
      'message' => 'Width must be greater than 0',
    ),
    'Height' => array(
      'rule' => array('comparison', '>', 0),
      'message' => 'Height must be greater than 0',
    ),
    'ServerId' => array(
      'rule' => array('rowExists', 'Server'),
      'message' => 'ServerId does not match a server',
    ),
    'StorageId' => array(
      'rule' => array('rowExists', 'Storage'),
      'message' => 'StorageId does not match a storage area',
    ),
    // These codec values mimic acceptable ones for FFMPEG
    // Edit this if the list in ZM changes
    'OutputCodec' => array (
	    'rule' => array('inList', array (0,27,173,167,226)),
	    'message'=>'Invalid value. Should be one of these integer values: 0(auto), 27(h264), 173(h265/hvec), 167(vp9), 226(av1)'
    ),
    'Device' => array(
      'validPath' => array(
        'rule' => array('validDevicePath'),
        'message' => 'Invalid device path. Must be a valid /dev/ path (e.g. /dev/video0).',
        'allowEmpty' => true,
        'required' => false,
      ),
    )

  );

  /**
   * Validate the Device field. Only Local monitors pass Device to a shell,
   * so the /dev/ restriction only applies when Type == 'Local'. Other Types
   * may legitimately hold legacy values (e.g. an RTSP URL) in this column.
   */
  // Empty and 0 mean "none" (the default storage area, any server).
  public function rowExists($check, $model) {
    $value = reset($check);
    if ($value === null or $value === '' or $value == 0) {
      return true;
    }
    return (bool)ClassRegistry::init($model)->find('first', array(
      'conditions' => array($model.'.Id' => $value),
      'recursive' => -1,
    ));
  }

  public function validDevicePath($check) {
    $value = reset($check);
    if ($value === null || $value === '') {
      return true;
    }
    $type = isset($this->data['Monitor']['Type']) ? $this->data['Monitor']['Type'] : ($this->id ? $this->field('Type') : null);
    if ($type !== 'Local') {
      return true;
    }
    return (bool)preg_match('#^/dev/[\w/.\-]+$#', $value);
  }

	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
		'Event' => array(
			'className' => 'Event',
			'foreignKey' => 'MonitorId',
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
		'Zone' => array(
			'className' => 'Zone',
			'foreignKey' => 'MonitorId',
			'dependent' => true,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

  /**
    * hasMany associations
    *
    * @var array
    */
  public $hasAndBelongsToMany = array(
    'Group' => array(
      'className' => 'Group',
      'joinTable' =>  'Groups_Monitors',
      'foreignKey' => 'MonitorId',
      'associationForeignKey' => 'GroupId',
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
      'Type'            => array('Local','Remote','File','Ffmpeg','cURL','WebSite', 'VNC'),
      'Function'        => array('None','Monitor','Modect','Record','Mocord','Nodect'),
      'Orientation'     => array('ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT'),
      // mask OutputCodec as its not an Enum in mysql
      //'OutputCodec'     => array( 'h264' ,'mjpeg','mpeg1','mpeg2'),
      'OutputContainer' => array('auto','mp4','mkv','webm'),
      'DefaultView'     => array('Events','Control'),
      #'Status'          => array('Unknown','NotRunning','Running','NoSignal','Signal'),
    )
  );

  public $hasOne = array(
    'Monitor_Status' => array(
      'className' => 'Monitor_Status',
      'foreignKey' => 'MonitorId',
      'joinTable' =>  'Monitor_Status',
    ),
    'Event_Summary' => array(
      'className' => 'Event_Summary',
      'foreignKey' => 'MonitorId',
      'joinTable' =>  'Event_Summaries',
    )
  );
  
  public $belongsTo = array(
    'Manufacturer' => array(
      'className' => 'Manufacturer',
      'foreignKey' => 'ManufacturerId',
      'joinTable' => 'Manufacturers',
    ),
    'CameraModel' => array(
      'className' => 'CameraModel',
      'foreignKey' => 'ModelId',
      'joinTable' => 'Models',
    )
  );

  // Explanation for the last daemonControl() that returned false.
  public $daemonControlError = null;

  // Returns the daemon output on success and false when the command was not run
  // or zmdc.pl failed. Callers cannot otherwise tell a failed start from a
  // successful one, because zmdc.pl prints its errors on stdout.
  public function daemonControl($monitor, $command, $daemon=null) {
    $this->daemonControlError = null;
    if ($monitor['Capturing'] == 'None' and $command != 'stop') {
      ZM\Warning("Calling daemonControl with command $command when Capturing == None");
      $this->daemonControlError = 'Monitor capturing is set to None';
      return false;
    }
    if (defined('ZM_SERVER_ID') and ($monitor['ServerId']!=ZM_SERVER_ID)) {
      ZM\Error('Calling daemonControl for Monitor assigned to different server. Our server id '.ZM_SERVER_ID.' != '.$monitor['ServerId']);
      $this->daemonControlError = 'Monitor is assigned to a different server';
      return false;
    }

    $daemons = array();
    if (!$daemon) {
      array_push($daemons, 'zmc');
    } else {
      array_push($daemons, $daemon);
    }

    $status_text = '';
    foreach ($daemons as $daemon) {
      $args = '';
      if ($daemon == 'zmc' and $monitor['Type'] == 'Local') {
        $args = '-d ' . escapeshellarg($monitor['Device']);
      } else if ($daemon == 'zmcontrol.pl') {
        $args = '--id '.escapeshellarg($monitor['Id']);
      } else {
        $args = '-m ' . escapeshellarg($monitor['Id']);
      }

      $shellcmd = escapeshellarg(ZM_PATH_BIN.'/zmdc.pl').' '.escapeshellarg($command).' '.escapeshellarg($daemon).' '.$args;
      ZM\Debug("Command $shellcmd");
      $output = array();
      $status = exec($shellcmd, $output, $rc);
      $status_text .= $status.PHP_EOL;
      if ($rc != 0) {
        ZM\Error("Command $shellcmd failed with status $rc: ".implode(PHP_EOL, $output));
        $this->daemonControlError = trim(implode(PHP_EOL, $output));
        if ($this->daemonControlError === '') {
          $this->daemonControlError = "zmdc.pl exited with status $rc";
        }
        return false;
      }
    } # end foreach daemon
    return $status_text;
  } # end function daemonControl
}
