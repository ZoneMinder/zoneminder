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
		chdir(Configure::read('ZM_PATH_WEB') . '/' . Configure::read('ZM_DIR_IMAGES'));
		$command = Configure::read('ZM_PATH_BIN');
                $command .= "/zmu -m $mid -z$zid";
		exec( escapeshellcmd( $command ), $output, $status );
		return $status;
	}
}
?>
