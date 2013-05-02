<?php
class Event extends AppModel {
  public $useTable = 'Events';
  public $belongsTo = array(
	'Monitor' => array(
		'className' => 'Monitor',
		'foreignKey' => 'MonitorId'
	)
  );
}
?>
