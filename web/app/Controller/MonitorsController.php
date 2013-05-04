<?php
	class MonitorsController extends AppController {
  
		public function index() {
			$monitoroptions['fields'] = array('Name', 'Id', 'Function', 'Host');
			$this->set('monitors', $this->Monitor->find('all', $monitoroptions));

			$elhoptions = array(
			'conditions' => array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 HOUR)'),
			'group' => array('Event.MonitorId'),
			'fields' => array('count(Event.Id) AS count')
			);
			$this->set('elh', $this->Monitor->Event->find('all', $elhoptions));

			$eldoptions = array(
			'conditions' => array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 DAY)'),
			'group' => array('Event.MonitorId'),
			'fields' => array('count(Event.Id) AS count')
			);
			$this->set('eld', $this->Monitor->Event->find('all', $eldoptions));

			$elwoptions = array(
			'conditions' => array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 WEEK)'),
			'group' => array('Event.MonitorId'),
			'fields' => array('count(Event.Id) AS count')
			);
			$this->set('elw', $this->Monitor->Event->find('all', $elwoptions));

			$elmoptions = array(
			'conditions' => array('Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 MONTH)'),
			'group' => array('Event.MonitorId'),
			'fields' => array('count(Event.Id) AS count')
			);
			$this->set('elm', $this->Monitor->Event->find('all', $elmoptions));
		}

		public function view($id = null) {
			if (!$id) {
				throw new NotFoundException(__('Invalid monitor'));
			}

			$monitor = $this->Monitor->findById($id);
			if (!$monitor) {
				throw new NotFoundException(__('Invalid monitor'));
			}
			$this->set('monitor', $monitor);
		}

		public function edit($id = null) {
	    if (!$id) {
	        throw new NotFoundException(__('Invalid monitor'));
	    }
	
	    $monitor = $this->Monitor->findById($id);
	    if (!$monitor) {
	        throw new NotFoundException(__('Invalid monitor'));
	    }
	
	    if ($this->request->is('put') || $this->request->is('post')) {
	        $this->Monitor->id = $id;
	        if ($this->Monitor->save($this->request->data)) {
	            $this->Session->setFlash('Your monitor has been updated.');
	            $this->redirect(array('action' => 'index'));
	        } else {
	            $this->Session->setFlash('Unable to update your monitor.');
	        }
	    }
	
	    if (!$this->request->data) {
	        $this->request->data = $monitor;
	    }
		}

	}

?>
