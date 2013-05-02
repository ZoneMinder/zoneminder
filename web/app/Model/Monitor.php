<?php
  class Monitor extends AppModel {
    public $useTable = 'Monitors';
    public $hasMany = array(
	'Event' => array(
		'className' => 'Event',
		'foreignKey' => 'MonitorId'
	)
    );
  }
?>
