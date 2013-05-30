<?php
class Event extends AppModel {
  public $useTable = 'Events';
  public $primaryKey = 'Id';
  public $belongsTo = array(
	'Monitor' => array(
		'className' => 'Monitor',
		'foreignKey' => 'MonitorId'
	)
  );
  public $hasMany = array(
    'Frame' => array(
      'className' => 'Frame',
      'foreignKey' => 'FrameId',
      'dependent' => true
    )
  );
}
?>
