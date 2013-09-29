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

	        $oldFunction = $monitor['Monitor']['Function'];
	        $newFunction = $this->request->data['Monitor']['Function'];
	        $oldEnabled = $monitor['Monitor']['Enabled'];
	        $newEnabled = $this->request->data['Monitor']['Enabled'];

	        if( $oldFunction != $newFunction || $newEnabled != $oldEnabled ) {
				$restart = ($oldFunction == 'None') || ($newFunction == 'None') || ($newEnabled != $oldEnabled);
                $this->zmaControl( $this->request->data['Monitor'], "stop" );
                $this->zmcControl( $this->request->data['Monitor'], $restart?"restart":"" );
                $this->zmaControl( $this->request->data['Monitor'], "start" );
	        }

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

    public function daemonControl( $command, $daemon=false, $args=false ){
		$string = Configure::read('ZM_PATH_BIN')."/zmdc.pl $command";
		if ( $daemon )
		{
			$string .= " $daemon";
			if ( $args )
			{
				$string .= " $args";
			}
		}
		$string .= " 2>/dev/null >&- <&- >/dev/null";
		error_log($string);
		exec( $string );
	}

    public function zmcControl( $monitor, $mode=false ) {
		if ( $monitor['Type'] == "Local" )
		{
			$zmcArgs = "-d ".$monitor['Device'];
		}
		else
		{
			$zmcArgs = "-m ".$monitor['Id'];
		}

		if (  $monitor['Function'] == 'None' || !$monitor['Enabled'] || $mode == "stop" )
		{
			$this->daemonControl( "stop", "zmc", $zmcArgs );
		}
		else
		{
			if ( $mode == "restart" )
			{
				$this->daemonControl( "stop", "zmc", $zmcArgs );
			}
			$this->daemonControl( "start", "zmc", $zmcArgs );
		}
	}

	public function zmaControl( $monitor, $mode=false ) {
		if ( $monitor['Function'] == 'None' || $monitor['Function'] == 'Monitor' || !$monitor['Enabled'] || $mode == "stop" ){
			$this->daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
		} else {
			if ( $mode == "restart" ) {
				$this->daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
			}
			$this->daemonControl( "start", "zma", "-m ".$monitor['Id'] );

			if ( $mode == "reload" )
			{
				$this->daemonControl( "reload", "zma", "-m ".$monitor['Id'] );
			}
		}
	}

}
?>
