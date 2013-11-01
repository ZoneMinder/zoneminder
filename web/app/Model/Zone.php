<?php
class Zone extends AppModel {
	public $useTable = 'Zones';
	public $primaryKey = 'Id';
	public $belongsTo = array(
		'Monitor' => array(
			'className' => 'Monitor',
			'foreignKey' => 'MonitorId')
		);

	public function createSnapshot($mid = null, $zid = null) {
		# chdir(Configure::read('ZM_PATH_WEB') . '/' . Configure::read('ZM_DIR_IMAGES'));
		chdir(WWW_ROOT . '/img');
		$command = Configure::read('ZM_PATH_BIN');
                $command .= "/zmu -m $mid -z";
		exec( escapeshellcmd( $command ), $output, $status );
		return $status;
	}
}
?>
