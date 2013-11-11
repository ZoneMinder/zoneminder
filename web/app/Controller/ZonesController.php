<?php

class ZonesController extends AppController {
    public function index() {
	$this->layout = 'nosidebar';
	$this->loadModel('Monitor');

	$monitors = $this->Monitor->find('all');

	foreach ($monitors as $key => $monitor) {
		if ($this->Zone->createSnapshot($monitor['Monitor']['Id']) == 0) {
			$monitors[$key]['Monitor']['Snapshot'] = '/img/Zones'.$monitor['Monitor']['Id'].'.jpg?'.time();
		}
	}

	$this->set('monitors', $monitors);
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
		$this->set('mid', $mid);
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

	public function add($id = null) {
		$this->loadModel('Monitor');
		if (!$id) {
			throw new NotFoundException(__('Invalid Monitor'));
		}

		$monitor = $this->Monitor->findById($id);
		if (!$monitor) {
			throw new NotFoundException(__('Invalid Monitor'));
		}

		$mid = $monitor['Monitor']['Id'];
		$this->set('mid', $mid);
		$this->Zone->createSnapshot($mid);
		$this->set('zoneImage', '/img'.'/Zones'.$mid.'.jpg?'.time());



		if ($this->request->is('post')) {
			$this->Zone->create();
			if ($this->Zone->save($this->request->data)) {
				$this->Session->setFlash(__('Your zone has been saved.'));
				return $this->redirect(array('action' => 'index'));
			}
			$this->Session->setFlash(__('Unable to add your zone.'));
		}
	}

}

?>
