<?php
	class MonitorsController extends AppController {
	public $helpers = array('LiveStream');
  
		public function index() {
      $zmBandwidth = $this->Cookie->read('zmBandwidth');
			$monitoroptions = array( 'fields' => array('Name', 'Id', 'Function'), 'recursive' => -1);
			$this->set('monitors', $this->Monitor->find('all', $monitoroptions));
      $monitors = $this->Monitor->find('all', array('recursive' => -1, 'fields' => array('Id', 'StreamReplayBuffer')));
      foreach ($monitors as $monitor => $mon) {
        $streamSrc[$monitor] = $this->Monitor->getStreamSrc($monitor['Monitor']['Id'], $zmBandwidth, $monitor['Monitor']['StreamReplayBuffer']);
      }
      $this->set('streamSrc', $streamSrc);
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


			  $zmBandwidth = $this->Cookie->read('zmBandwidth');
			  $buffer = $monitor['Monitor']['StreamReplayBuffer'];
        $this->set('streamSrc', $this->Monitor->getStreamSrc($id, $zmBandwidth, $buffer));

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

	public function add() {
		if ($this->request->is('post')) {
			$this->Monitor->create();
			if ($this->Monitor->save($this->request->data)) {
				$this->Session->setFlash('Your monitor has been created.');
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash('Unable to create your monitor.');
			}
		}
	}

	}

?>
