<?php
App::uses('AppController', 'Controller');
/**
 * Monitors Controller
 *
 * @property Monitor $Monitor
 * @property PaginatorComponent $Paginator
 */
class MonitorsController extends AppController {


/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');


public function beforeFilter() {
	parent::beforeFilter();
        $canView = $this->Session->Read('monitorPermission');
	if ($canView =='None')
	{
		throw new UnauthorizedException(__('Insufficient Privileges'));
		return;
	}

}


/**
 * index method
 *
 * @return void
 */
	public function index() {
                $this->Monitor->recursive = 0;
		$allowedMonitors=preg_split ('@,@', $this->Session->Read('allowedMonitors'),NULL, PREG_SPLIT_NO_EMPTY);
		
		if (!empty($allowedMonitors))
		{
			$options = array('conditions'=>array('Monitor.Id'=> $allowedMonitors));
		}
		else
		{
			$options='';
		}
        	$monitors = $this->Monitor->find('all',$options);
        	$this->set(array(
        	    'monitors' => $monitors,
        	    '_serialize' => array('monitors')
        	));
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->Monitor->recursive = 0;
		if (!$this->Monitor->exists($id)) {
			throw new NotFoundException(__('Invalid monitor'));
		}
		$allowedMonitors=preg_split ('@,@', $this->Session->Read('allowedMonitors'),NULL, PREG_SPLIT_NO_EMPTY);
		if (!empty($allowedMonitors))
		{
			$restricted = array('Monitor.' . $this->Monitor->primaryKey => $allowedMonitors);
		}
		else
		{
			$restricted = '';
		}
		
		$options = array('conditions' => array( 
					array('Monitor.' . $this->Monitor->primaryKey => $id),
					$restricted
					)
				);
		$monitor = $this->Monitor->find('first', $options);
		$this->set(array(
			'monitor' => $monitor,
			'_serialize' => array('monitor')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {

			if ($this->Session->Read('systemPermission') != 'Edit')
			{
				 throw new UnauthorizedException(__('Insufficient privileges'));
				return;
			}

			$this->Monitor->create();
			if ($this->Monitor->save($this->request->data)) {
				$this->daemonControl($this->Monitor->id, 'start');
				return $this->flash(__('The monitor has been saved.'), array('action' => 'index'));
			}
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->Monitor->id = $id;

		if (!$this->Monitor->exists($id)) {
			throw new NotFoundException(__('Invalid monitor'));
		}
		if ($this->Session->Read('monitorPermission') != 'Edit')
		{
			 throw new UnauthorizedException(__('Insufficient privileges'));
			return;
		}
		if ($this->Monitor->save($this->request->data)) {
			$message = 'Saved';
		} else {
			$message = 'Error';
		}

		$this->set(array(
			'message' => $message,
			'_serialize' => array('message')
		));

		// - restart or stop this monitor after change
    $func = $this->Monitor->find('first', array(
          'fields' => array('Function'),
          'conditions' => array('Id' => $id)
          ))['Monitor']['Function'];
    // We don't pass the request data as the monitor object because it may be a subset of the full monitor array
    if ( $func == 'None' ) {
      $this->daemonControl( $this->Monitor->id, 'stop' );
    } else {
      $this->daemonControl( $this->Monitor->id, 'restart' );
    }
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Monitor->id = $id;
		if (!$this->Monitor->exists()) {
			throw new NotFoundException(__('Invalid monitor'));
		}
		if ($this->Session->Read('systemPermission') != 'Edit')
		{
			 throw new UnauthorizedException(__('Insufficient privileges'));
			return;
		}
		$this->request->allowMethod('post', 'delete');

		$this->daemonControl($this->Monitor->id, 'stop');

		if ($this->Monitor->delete()) {
			return $this->flash(__('The monitor has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The monitor could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}

	public function sourceTypes() {
		$sourceTypes = $this->Monitor->query("describe Monitors Type;");

		preg_match('/^enum\((.*)\)$/', $sourceTypes[0]['COLUMNS']['Type'], $matches);
		foreach( explode(',', $matches[1]) as $value ) {
			$enum[] = trim( $value, "'" );
		}

		$this->set(array(
			'sourceTypes' => $enum,
			'_serialize' => array('sourceTypes')
		));
	}

	// arm/disarm alarms
	// expected format: http(s):/portal-api-url/monitors/alarm/id:M/command:C.json
	// where M=monitorId
	// where C=on|off|status
	public function alarm()
	{
		$id = $this->request->params['named']['id'];
		$cmd = strtolower($this->request->params['named']['command']);
		if (!$this->Monitor->exists($id)) {
			throw new NotFoundException(__('Invalid monitor'));
		}
		if ( $cmd != 'on' && $cmd != 'off' && $cmd != 'status')
		{
			throw new BadRequestException(__('Invalid command'));
		}
		$zm_path_bin = Configure::read('ZM_PATH_BIN');

		switch ($cmd) 
		{
			case "on":
				$q = '-a';
				$verbose = "-v";
				break;
			case "off":
			  $q = "-c";
				$verbose = "-v";
				break;
			case "status":
				$verbose = ""; // zmu has a bug - gives incorrect verbose output in this case
				$q = "-s";
				break;			
		}

		// form auth key based on auth credentials
		$this->loadModel('Config');
		$options = array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_OPT_USE_AUTH'));
                $config = $this->Config->find('first', $options);
		$zmOptAuth = $config['Config']['Value'];


		$options = array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_RELAY'));
                $config = $this->Config->find('first', $options);
		$zmAuthRelay = $config['Config']['Value'];
	
		$auth="";
		if ($zmOptAuth)
		{
			if ($zmAuthRelay == 'hashed')
			{
				$options = array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_HASH_SECRET'));
                		$config = $this->Config->find('first', $options);
				$zmAuthHashSecret = $config['Config']['Value'];

				$time = localtime();
				$ak = $zmAuthHashSecret.$this->Session->Read('username').$this->Session->Read('passwordHash').$time[2].$time[3].$time[4].$time[5];
				$ak = md5($ak);
				$auth = " -A ".$ak;
			}
			elseif ($zmAuthRelay == 'plain')
			{
				$auth = " -U " .$this->Session->Read('username')." -P ".$this->Session->Read('password');
				
			}
			elseif ($zmAuthRelay == 'none')
			{
				$auth = " -U " .$this->Session->Read('username');
			}
		}
		
		$shellcmd = escapeshellcmd("$zm_path_bin/zmu $verbose -m$id $q $auth");
		$status = exec ($shellcmd);

		$this->set(array(
			'status' => $status,
			'_serialize' => array('status'),
		));

		
	}

	// Check if a daemon is running for the monitor id
	public function daemonStatus() {
		$id = $this->request->params['named']['id'];
		$daemon = $this->request->params['named']['daemon'];

		if (!$this->Monitor->exists($id)) {
			throw new NotFoundException(__('Invalid monitor'));
		}

		$monitor = $this->Monitor->find('first', array(
			'fields' => array('Id', 'Type', 'Device'),
			'conditions' => array('Id' => $id)
		));

		// Clean up the returned array
		$monitor = Set::extract('/Monitor/.', $monitor);

		// Pass -d for local, otherwise -m
		if ($monitor[0]['Type'] == 'Local') {
			$args = "-d ". $monitor[0]['Device'];	
		} else {
			$args = "-m ". $monitor[0]['Id'];
		}

		// Build the command, and execute it
		$zm_path_bin = Configure::read('ZM_PATH_BIN');
		$command = escapeshellcmd("$zm_path_bin/zmdc.pl status $daemon $args");
		$status = exec( $command );

		// If 'not' is present, the daemon is not running, so return false
		// https://github.com/ZoneMinder/ZoneMinder/issues/799#issuecomment-108996075
		// Also sending back the status text so we can check if the monitor is in pending
		// state which means there may be an error
		$statustext = $status;
		$status = (strpos($status, 'not')) ? false : true;

		$this->set(array(
			'status' => $status,
			'statustext' => $statustext,
			'_serialize' => array('status','statustext'),
		));
	}

	public function daemonControl($id, $command, $monitor=null, $daemon=null) {
		$args = '';
		$daemons = array();

		if (!$monitor) {
			// Need to see if it is local or remote
			$monitor = $this->Monitor->find('first', array(
				'fields' => array('Type', 'Function'),
				'conditions' => array('Id' => $id)
			));
			$monitor = $monitor['Monitor'];
		}

		if ($monitor['Type'] == 'Local') {
			$args = "-d " . $monitor['Device'];
		} else {
			$args = "-m " . $id;
		}

		if ($monitor['Function'] == 'Monitor') {
			array_push($daemons, 'zmc');
		} else {
			array_push($daemons, 'zmc', 'zma');
		}
		
		$zm_path_bin = Configure::read('ZM_PATH_BIN');

		foreach ($daemons as $daemon) {
			$shellcmd = escapeshellcmd("$zm_path_bin/zmdc.pl $command $daemon $args");
			$status = exec( $shellcmd );
		}
	}

}

