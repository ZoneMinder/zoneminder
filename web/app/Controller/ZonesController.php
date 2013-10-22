<?php

class ZonesController extends AppController {
    public function index() {
	$this->loadModel('Monitor');
	$this->set('zones', $this->Zone->find('all'));
	$this->set('monitors', $this->Monitor->find('list'));
    }

	public function edit($zid = null) {
		$zone = $this->Zone->findById($zid);
		$mid = $zone['Monitor']['Id'];
		$this->Zone->createSnapshot($mid, $zid);
		$this->set('zoneImage', '/img'.'/Zones'.$mid.'.jpg?'.time());
		$this->set('zone', $zone['Zone']);
		if (!$this->request->data) {
			$this->request->data = $zone;
		}
	}

}

?>
