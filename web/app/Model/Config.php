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

	public function writeConfig() {
		$configFile =  "/usr/local/etc/zm.conf";
		$lines = file($configFile);
		foreach ($lines as $linenum => $line) {
			if ( preg_match( '/^\s*([^=\s]+)\s*=\s*(.+?)\s*$/', $line, $matches )) {
				Configure::write($matches[1], $matches[2]);
			}
		}

		$options = $this->find('list', array('fields' => array('Name', 'Value')));
		foreach ($options as $key => $value) {
			Configure::write($key, $value);
		}
    		Configure::write('SCALE_BASE', 100);
	}
}
?>
