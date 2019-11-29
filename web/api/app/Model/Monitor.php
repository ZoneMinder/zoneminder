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
      'Type'            => array(1=>'Local',2=>'Remote',3=>'File',4=>'Ffmpeg',5=>'Libvlc',6=>'cURL',7=>'WebSite'),
      'Function'        => array(1=>'None',2=>'Monitor',3=>'Modect',4=>'Record',5=>'Mocord',6=>'Nodect'),
      'Orientation'     => array('ROTATE_0','ROTATE_90','ROTATE_180','ROTATE_270','FLIP_HORI','FLIP_VERT'),
      'OutputCodec'     => array(1=>'h264',2=>'mjpeg',3=>'mpeg1',4=>'mpeg2'),
      'OutputContainer' => array(1=>'auto',2=>'mp4',3=>'mkv'),
      'DefaultView'     => array(1=>'Events',2=>'Control'),
      #'Status'          => array('Unknown','NotRunning','Running','NoSignal','Signal'),
    )
  );

  public $hasOne = array(
    'Monitor_Status' => array(
      'className' => 'Monitor_Status',
      'foreignKey' => 'MonitorId',
      'joinTable' =>  'Monitor_Status',
    )
  );
}
