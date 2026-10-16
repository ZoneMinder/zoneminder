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

  public function beforeRender() {
    $this->set($this->Monitor->enumValues());
  }

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user['Monitors'] != 'None');
    if ( !$canView ) {
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

    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }
    global $user;
    $allowedMonitors = $user ? preg_split('@,@', $user['MonitorIds'], NULL, PREG_SPLIT_NO_EMPTY) : null;
    if ( $allowedMonitors ) {
      $conditions['Monitor.Id' ] = $allowedMonitors;
    }

    $find_array = array(
      'conditions' => &$conditions,
      'contain'    => array('Group'),
      'joins'      => array(
        array(
          'table' => 'Groups_Monitors',
          'type'  => 'left',
          'conditions' => array(
            'Groups_Monitors.MonitorId = Monitor.Id',
          ),
        ),
      ),
      'group' => '`Monitor`.`Id`',
    );
    $monitors = $this->Monitor->find('all',$find_array);
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
    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    global $user;
    $allowedMonitors = $user ? preg_split('@,@', $user['MonitorIds'], NULL, PREG_SPLIT_NO_EMPTY) : null;
    if ( $allowedMonitors ) {
      $restricted = array('Monitor.' . $this->Monitor->primaryKey => $allowedMonitors);
    } else {
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
    if ( $this->request->is('post') ) {

      global $user;
      $canAdd = (!$user) || ($user['System'] == 'Edit' );
      if ( !$canAdd ) {
        throw new UnauthorizedException(__('Insufficient privileges'));
        return;
      }
      $this->Monitor->create();
      if ($this->Monitor->save($this->request->data) ) {
        $this->runDaemonControl($this->Monitor->id, 'start');
        //return $this->flash(__('The monitor has been saved.'), array('action' => 'index'));
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if (!$this->Monitor->validates()) {
          $message = $this->Monitor->validationErrors;
       }
      }
      $this->set(array(
        'message' => $message,
        '_serialize' => array('message')
      ));
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

    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    // A user restricted to certain monitors (MonitorIds) must not edit others, so this
    // checks edit on the specific monitor rather than the global Monitors permission.
    $monitor = $this->monitorForDaemonAction($id, true);

    $message = '';
    if ( $this->Monitor->save($this->request->data) ) {
      $message = 'Saved';

      // Stop the monitor. Should happen before saving
      $this->runDaemonControl($id, 'stop');
      $this->runDaemonControl($id, 'start');
    } else {
      $message = 'Error ' . print_r($this->Monitor->invalidFields(), true);
    }

    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));

  } // end function edit

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
  public function delete($id = null) {
    $this->Monitor->id = $id;
    if ( !$this->Monitor->exists() ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    global $user;
    $canEdit = (!$user) || ($user['System'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }
    $this->request->allowMethod('post', 'delete');

    $this->runDaemonControl($this->Monitor->id, 'stop');

    if ( $this->Monitor->delete() ) {
      return $this->flash(__('The monitor has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The monitor could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }

  public function sourceTypes() {
    $sourceTypes = $this->Monitor->query('describe Monitors Type;');

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
  // where C=on|off|status|disable
  public function alarm() {
    $id = $this->request->params['named']['id'];
    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }

    $cmd = strtolower($this->request->params['named']['command']);
    switch ($cmd) {
      case 'on':
        $q = '-a';
        $verbose = '-v';
        break;
      case 'off':
        $q = '-c';
        $verbose = '-v';
        break;
      case 'disable':
        $q = '-n';
        $verbose = '-v';
        break;
      case 'status':
        $verbose = ''; // zmu has a bug - gives incorrect verbose output in this case
        $q = '-s';
        break;      
      default :
        throw new BadRequestException(__('Invalid command'));
    }

    // form auth key based on auth credentials
    $auth = '';
    
    if (ZM_OPT_USE_AUTH) {
      global $user;
      $mToken = $this->request->query('token') ? $this->request->query('token') : $this->request->data('token');;
      if ($mToken) {
        $auth = ' -T '.escapeshellarg($mToken);
      } else if (ZM_AUTH_RELAY == 'hashed') {
        $auth = ' -A '.calculateAuthHash(''); # Can't do REMOTE_IP because zmu doesn't normally have access to it.
      } else if (ZM_AUTH_RELAY == 'plain') {
        # Plain requires the plain text password which must either be in request or stored in session
        $password = $this->request->query('pass') ? $this->request->query('pass') : $this->request->data('pass');;
        if (!$password) 
          $password = $this->request->query('password') ? $this->request->query('password') : $this->request->data('password');

        if (!$password) {
          # during auth the session will have been populated with the plaintext password
          $stateful = $this->request->query('stateful') ? $this->request->query('stateful') : $this->request->data('stateful');
          if ($stateful) {
            $password = $_SESSION['password'];
          }
        } else if ($_COOKIE['ZMSESSID']) {
          $password = $_SESSION['password'];
        }

        $auth = ' -U '.escapeshellarg($user['Username']).' -P '.escapeshellarg($password);
      } else if (ZM_AUTH_RELAY == 'none') {
        $auth = ' -U '.escapeshellarg($user['Username']);
      }
    }
    
    // Each value is quoted on its own: escapeshellcmd() on the whole command still let a
    // value add arguments to zmu.
    $shellcmd = ZM_PATH_BIN.'/zmu'
      .($verbose ? " $verbose" : '')
      .' -m'.escapeshellarg($id)
      ." $q"
      .$auth;
    $status = exec($shellcmd, $output, $rc);
    ZM\Debug("Command: $shellcmd output: ".implode(PHP_EOL, $output)." rc: $rc");
    if ($rc) {
      $this->set(array(
        'status'=>'false',
        'code' => $rc,
        'error'=> implode(PHP_EOL, $output),
        '_serialize' => array('status','code','error'),
      ));
    } else if ($cmd == 'status') {
      // In 1.36.16 the values got shifted up so that we could index into an array of strings.
      // So do a hack to restore the previous behavour
      $this->set(array(
        'status' => intval($status)-1,
        'output' => intval($output[0])-1,
        '_serialize' => array('status','output'),
      ));
    } else {
      $this->set(array(
        'status' => $status,
        'output' => implode(PHP_EOL, $output),
        '_serialize' => array('status','output'),
      ));
    }
  }

  // Check if a daemon is running for the monitor id
  // Load a monitor the caller may act on. daemonStatus() and daemonControl()
  // checked nothing, so any API user could read or control the daemons of a
  // monitor it could not even see. Reporting state needs view; starting or
  // stopping it is a change, so it needs edit. refs GHSA (daemon API)
  private function monitorForDaemonAction($id, $for_edit) {
    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    require_once __DIR__ .'/../../../includes/Monitor.php';
    $monitor = $this->Monitor->find('first', array(
      'conditions' => array('Id' => $id)
    ));
    $zm_monitor = new ZM\Monitor($monitor['Monitor']);
    if ($for_edit ? !$zm_monitor->canEdit() : !$zm_monitor->canView()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    return $monitor['Monitor'];
  }

  // zmdc.pl takes the daemon and command as separate arguments, interpolated
  // into the command line, so both are restricted to known values rather than
  // just escaped.
  private function assertDaemonAndCommand($daemon, $command) {
    $daemons = array('zmc', 'zma', 'zmfilter.pl', 'zmaudit.pl', 'zmtrigger.pl',
      'zmwatch.pl', 'zmupdate.pl', 'zmtrack.pl', 'zmcontrol.pl', 'zmstats.pl', 'zmeventnotification.pl');
    $commands = array('start', 'stop', 'restart', 'reload', 'status', 'check', 'logrot');
    if (($daemon !== null) and !in_array($daemon, $daemons, true)) {
      throw new BadRequestException(__('Invalid daemon'));
    }
    if (!in_array($command, $commands, true)) {
      throw new BadRequestException(__('Invalid command'));
    }
  }

  public function daemonStatus() {
    $id = $this->request->params['named']['id'];
    $daemon = $this->request->params['named']['daemon'];

    $this->monitorForDaemonAction($id, false);

    if (preg_match('/^[a-z]+$/i', $daemon) !== 1) {
      throw new BadRequestException(__('Invalid command'));
    }

    $monitor = $this->Monitor->find('first', array(
      'fields' => array('Id', 'Type', 'Device', 'Function'),
      'conditions' => array('Id' => $id)
    ));

    // Clean up the returned array
    $monitor = Set::extract('/Monitor/.', $monitor);
    if ($monitor[0]['Function'] == 'None') {
      $this->set(array(
        'status' => false,
        'statustext' => 'Monitor function is set to None',
        '_serialize' => array('status','statustext'),
      ));
      return;
    }

    // Pass -d for local, otherwise -m
    if ( $monitor[0]['Type'] == 'Local' ) {
      $args = '-d '. escapeshellarg($monitor[0]['Device']);
    } else {
      $args = '-m '. escapeshellarg($monitor[0]['Id']);
    }

    // Build the command, and execute it
    $command = escapeshellcmd(ZM_PATH_BIN."/zmdc.pl status $daemon $args");
    $status = exec($command);
    ZM\Debug("Command: $command output: $status");

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

  // Stop or start a monitor's daemons, with no permission check of its own.
  // add(), edit() and delete() call this after their own permission decision;
  // a System Edit account need not hold Monitors Edit, so re-gating here would
  // wrongly block them.
  private function runDaemonControl($id, $command, $daemon=null) {
    $monitor = $this->Monitor->find('first', array(
      'fields' => array('Id', 'Type', 'Function', 'Device', 'ServerId'),
      'conditions' => array('Id' => $id)
    ));
    return $this->Monitor->daemonControl($monitor['Monitor'], $command, $daemon);
  }

  public function daemonControl($id, $command, $daemon=null) {
    $this->assertDaemonAndCommand($daemon, $command);
    $this->monitorForDaemonAction($id, true);
    $status_text = $this->runDaemonControl($id, $command, $daemon);

    $this->set(array(
      'status' => 'ok',
      'statustext' => $status_text,
      '_serialize' => array('status','statustext'),
    ));
  } // end function daemonControl
} // end class MonitorsController
