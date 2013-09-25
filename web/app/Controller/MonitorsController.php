<?php
	class MonitorsController extends AppController {
    public $helpers = array('Js'=>array('Jquery'));
  
		public function index() {
      $zmBandwidth = $this->Cookie->read('zmBandwidth');
      $monitoroptions = array('fields' => array('Name', 'Id', 'Function', 'Enabled', 'Sequence', 'Function', 'Width', 'StreamReplayBuffer'), 'order' => 'Sequence ASC', 'recursive' => -1);
      $monitors = $this->Monitor->find('all', $monitoroptions);


      foreach ($monitors as $key => $value) {
        $monitors[$key]['img'] = $this->Monitor->getStreamSrc($value['Monitor']['Id'], $zmBandwidth, $value['Monitor']['StreamReplayBuffer'], $value['Monitor']['Function'], $value['Monitor']['Enabled'], $value['Monitor']['Name'], $value['Monitor']['Width']);
      }
	$this->set('monitors', $monitors);
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
       			 $this->set('streamSrc', $this->Monitor->getStreamSrc($id, $zmBandwidth, $buffer, $monitor['Monitor']['Function'], $monitor['Monitor']['Enabled'], $monitor['Monitor']['Name'], $monitor['Monitor']['Width'], false));
		}

		public function edit($id = null) {
	    if (!$id) {
	      throw new NotFoundException(__('Invalid monitor'));
	    }
	
	    $monitor = $this->Monitor->findById($id);
	    if (!$monitor) {
	      throw new NotFoundException(__('Invalid monitor'));
      }
      $this->set('monitor', $monitor['Monitor']);
      $this->set('linkedMonitors', $this->Monitor->find('list', array('fields' => array('Id', 'Name')))); 
	
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
      $this->set('linkedMonitors', $this->Monitor->find('list', array('fields' => array('Id', 'Name')))); 
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

    public function reorder() {
      foreach ($this->data['Monitor'] as $key => $value) {
        $this->Monitor->id = $value;
        $this->Monitor->saveField('Sequence', $key+1);
      }
      exit();
    }

	}
?>
