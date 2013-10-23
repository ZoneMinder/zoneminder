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

		if ($this->request->is('put') || $this->request->is('post')) {
			$this->Zone->id = $zid;
	        	if ($this->Zone->save($this->request->data)) {
	        	    $this->Session->setFlash('Your zone has been updated.');
	        	    $this->redirect(array('action' => 'index'));
	        	} else {
	        	    $this->Session->setFlash('Unable to update your zone.');
	        	}
		}

		if (!$this->request->data) {
			$this->request->data = $zone;
		}
	}

}

?>
