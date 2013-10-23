<?php

class ZonesController extends AppController {
    public function index() {
	$this->loadModel('Monitor');
	$this->set('zones', $this->Zone->find('all'));
	$this->set('monitors', $this->Monitor->find('list'));
    }

	public function edit($zid = null) {
		if (!$zid) {
			throw new NotFoundException(__('Invalid Zone'));
		}

		$zone = $this->Zone->findById($zid);
		if (!$zone) {
			throw new NotFoundException(__('Invalid Zone'));
		}

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
