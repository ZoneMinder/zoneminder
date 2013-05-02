<?php
class Zone extends AppModel {
  public $useTable = 'Zones';
  public $belongsTo = array(
	'Monitor' => array(
		'className' => 'Monitor',
		'foreignKey' => 'MonitorId'
	)
  );
}
?>
