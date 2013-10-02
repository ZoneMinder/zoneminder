<?php
class Config extends AppModel {
  public $useTable = 'Config';
  public $primaryKey = 'Name';

	public function getWebOption($name) {
		$zmBandwidth = Configure::read('zmBandwidth');
		$name_begin = substr($name, 0, 7);
		$name_end   = substr($name, 6);
		$bandwidth_short = strtoupper($zmBandwidth[0]);
		$option = $name_begin . $bandwidth_short . $name_end;

		$ZM_OPTIONS = $this->find('first', array(
		  'fields' => array('Value'),
		  'conditions' => array('Category' => $zmBandwidth.'band', 'Name' => $option)
		));

		return($ZM_OPTIONS['Config']['Value']);
	}
}
?>
