<?php

class ZonesController extends AppController {
    public function index() {
	$this->loadModel('Monitor');
	$this->set('zones', $this->Zone->find('all'));
	$this->set('monitors', $this->Monitor->find('list'));
    }

	public function edit($zid = null) {
		$zone = $this->Zone->find('first', array('conditions' => array('Zone.Id' => $zid)));
		$mid = $zone['Monitor']['Id'];
		$this->Zone->createSnapshot($mid, $zid);
		$this->set('zoneImage', '/img'.'/Zones'.$mid.'.jpg?'.time());

	}

}

?>
