<?php
	class MonitorsController extends AppController {
	public $helpers = array('LiveStream');
  
		public function index() {
			$monitoroptions['fields'] = array('Name', 'Id', 'Function');
			$this->set('monitors', $this->Monitor->find('all', $monitoroptions));
			$monitors = $this->Monitor->find('list', array('fields' => array('Id')));
			$intervals = array('HOUR', 'DAY', 'WEEK', 'MONTH');
			foreach ($monitors as $monitor) {
				foreach ($intervals as $interval) {
					
				}
			}


		}

		public function view($id = null) {
			$this->loadModel('Config');
			if (!$id) {
				throw new NotFoundException(__('Invalid monitor'));
			}

			$monitor = $this->Monitor->findById($id);
			if (!$monitor) {
				throw new NotFoundException(__('Invalid monitor'));
			}
			$this->set('monitor', $monitor);


			  $zmBandwidth = $this->Cookie->read('zmBandwidth');

			  $ZM_MPEG_LIVE_FORMAT = $this->Config->find('first', array(
			    'fields' => array('Value'), 'conditions' => array('Name' => 'ZM_MPEG_LIVE_FORMAT')
			  ));

			  $ZM_WEB_STREAM_METHOD = $this->Config->getWebOption('ZM_WEB_STREAM_METHOD', $zmBandwidth);
			  $ZM_WEB_VIDEO_BITRATE = $this->Config->getWebOption('ZM_WEB_VIDEO_BITRATE', $zmBandwidth);
			  $ZM_WEB_VIDEO_MAXFPS = $this->Config->getWebOption('ZM_WEB_VIDEO_MAXFPS', $zmBandwidth);
			  $ZM_MPEG_LIVE_FORMAT = $ZM_MPEG_LIVE_FORMAT['Config']['Value'];
			  $buffer = $monitor['Monitor']['StreamReplayBuffer'];

			  if ($ZM_WEB_STREAM_METHOD == 'mpeg' && $ZM_MPEG_LIVE_FORMAT) {
			    $this->set('streamSrc', "/cgi-bin/nph-zms?mode=mpeg&scale=100&maxfps=$ZM_WEB_VIDEO_MAXFPS&bitrate=$ZM_WEB_VIDEO_BITRATE&format=$ZM_MPEG_LIVE_FORMAT");
			  } else {
			    $this->set('streamSrc', "/cgi-bin/nph-zms?mode=jpeg&scale=100&maxfps=$ZM_WEB_VIDEO_MAXFPS&buffer=$buffer");
			  }



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
