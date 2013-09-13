<?php
class VersionController extends AppController {
  
	public function index() {
		$this->set('zmDynLastVersion', Configure::read('ZM_DYN_LAST_VERSION'));
		$this->set('zmDynDBVersion', Configure::read('ZM_DYN_DB_VERSION'));
	}

}
?>
