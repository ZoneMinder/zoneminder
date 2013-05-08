<?php
  class Monitor extends AppModel {
    public $useTable = 'Monitors';
    public $primaryKey = 'Id';
    public $hasMany = array(
	'Event' => array(
		'className' => 'Event',
		'foreignKey' => 'MonitorId',
		'fields' => 'Event.Id'
	),
	'Zone' => array(
		'className' => 'Zone',
		'foreignKey' => 'MonitorId',
		'fields' => 'Zone.Id'
	)
    );
  }
?>
