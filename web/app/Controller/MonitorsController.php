<?php
	class MonitorsController extends AppController {
    public $helpers = array('LiveStream', 'Js'=>array('Jquery'));
  
		public function index() {
      $zmBandwidth = $this->Cookie->read('zmBandwidth');
      $this->set('width', Configure::read('ZM_WEB_LIST_THUMB_WIDTH'));
      $monitoroptions = array('fields' => array('Name', 'Id', 'Function', 'Enabled', 'Sequence', 'Function'), 'order' => 'Sequence ASC', 'recursive' => -1);
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
      $this->set('monitor', $monitor['Monitor']);

      $typeoptions = array(
        'Local' => 'Local',
        'Remote' => 'Remote',
        'File' => 'File',
        'Ffmpeg' => 'Ffmpeg'
      );
      $this->set('typeoptions', $typeoptions);
    
      $functionoptions = array(
        'Modect' => 'Modect',
        'Monitor' => 'Monitor',
        'Record' => 'Record',
        'None' => 'None',
        'Nodect' => 'Nodect',
        'Mocord' => 'Mocord'
      );
      $this->set('functionoptions', $functionoptions);

      $protocoloptions = array(
        'rtsp' => 'RTSP',
        'http' => 'HTTP'
      );
      $this->set('protocoloptions', $protocoloptions);

      $methodoptions = array(
        'simple' => 'Simple',
        'regexp' => 'Regexp'
      );
      $this->set('methodoptions', $methodoptions);

      $optionsColours = array(
        1 => '8 bit grayscale',
        3 => '24 bit color',
        4 => '32 bit color'
      );
      $this->set('optionsColours', $optionsColours);

      $channeloptions = array();
      for ($i=1; $i<32; $i++) {
        array_push($channeloptions, $i);
      }
      $this->set('channeloptions', $channeloptions);

      $formatoptions = array(
        255 => "PAL",
        45056 => "NTSC",
        1 => "PAL B",
        2 => "PAL B1",
        4 => "PAL G",
        8 => "PAL H",
        16 => "PAL I",
        32 => "PAL D",
        64 => "PAL D1",
        128 => "PAL K",
        256 => "PAL M",
        512 => "PAL N",
        1024 => "PAL Nc",
        2048 => "PAL 60",
        4096 => "NTSC M",
        8192 => "NTSC M JP",
        16384 => "NTSC 443",
        32768 => "NTSC M KR",
        65536 => "SECAM B",
        131072 => "SECAM D",
        262144 => "SECAM G",
        524288 => "SECAM H",
        1048576 => "SECAM K",
        2097152 => "SECAM K1",
        4194304 => "SECAM L",
        8388608 => "SECAM LC",
        16777216 => "ATSC 8 VSB",
        33554432 => "ATSC 16 VSB"
      );
      $this->set('formatoptions', $formatoptions);

      $optionsPalette = array(
        0 => 'Auto',
        1497715271 => 'Gray',
        877807426 => 'BGR32',
        876758866 => 'RGB32',
        861030210 => 'BGR24',
        859981650 => 'RGB24',
        1448695129 => '*YUYV',
        1195724874 => '*JPEG',
        1196444237 => '*MJPEG',
        875836498 => '*RGB444',
        1329743698 => '*RGB555',
        1346520914 => '*RGB565',
        1345466932 => '*YUV422P',
        1345401140 => '*YUV411P',
        875836505 => '*YUV444',
        961959257 => '*YUV410',
        842093913 => '*YUV420'
      );
      $this->set('optionsPalette', $optionsPalette);

      $optionsMethod = array(
        'v4l2' => 'Video For Linux 2'
      );
      $this->set('optionsMethod', $optionsMethod);

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