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
    ),
    // These codec values mimic acceptable ones for FFMPEG
    // Edit this if the list in ZM changes
    'OutputCodec' => array (
	    'rule' => array('inList', array (0,27,173,167,226)),
	    'message'=>'Invalid value. Should be one of these integer values: 0(auto), 27(h264), 173(h265/hvec), 167(vp9), 226(av1)'
    )

  );

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
      'Type'            => array('Local','Remote','File','Ffmpeg','Libvlc','cURL','WebSite', 'VNC'),
      'Function'        => array('None','Monitor','Modect','Record','Mocord','Nodect'),
      'Orientation'     => array('ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT'),
      // mask OutputCodec as its not an Enum in mysql
      //'OutputCodec'     => array( 'h264' ,'mjpeg','mpeg1','mpeg2'),
      'OutputContainer' => array('auto','mp4','mkv'),
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

  public function daemonControl($monitor, $command, $daemon=null) {
    if ($monitor['Function'] == 'None' and $command != 'stop') {
      ZM\Warning("Calling daemonControl with command $command when Function == None");
      return;
    }
    if (defined('ZM_SERVER_ID') and ($monitor['ServerId']!=ZM_SERVER_ID)) {
      ZM\Error('Calling daemonControl for Monitor assigned to different server. Our server id '.ZM_SERVER_ID.' != '.$monitor['ServerId']);
      return;
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
        $args = '-d ' . $monitor['Device'];
      } else if ($daemon == 'zmcontrol.pl') {
        $args = '--id '.$monitor['Id'];
      } else {
        $args = '-m ' . $monitor['Id'];
      }

      $shellcmd = escapeshellcmd(ZM_PATH_BIN.'/zmdc.pl '.$command.' '.$daemon.' '.$args);
      ZM\Debug("Command $shellcmd");
      $status = exec($shellcmd);
      $status_text .= $status.PHP_EOL;
    } # end foreach daemon
    return $status_text;
  } # end function daemonControl
}
