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
        $this->daemonControl($this->Monitor->id, 'start');
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
    global $user;
    $canEdit = (!$user) || ($user['Monitors'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $message = '';
    if ( $this->Monitor->save($this->request->data) ) {
      $message = 'Saved';
      $Monitor = $this->Monitor->find('first', array(
        'fields' => array('Function','ServerId'),
        'conditions' => array('Id' => $id)
      ))['Monitor'];

      // - restart or stop this monitor after change
      $func = $Monitor['Function'];
      // We don't pass the request data as the monitor object because it may be a subset of the full monitor array
      $this->daemonControl($this->Monitor->id, 'stop');
      if (
        ( $func != 'None' )
        and
        (
          (!defined('ZM_SERVER_ID'))
          or
          ($Monitor['ServerId']==ZM_SERVER_ID)
        )
      ) {
        if ( !defined('ZM_SERVER_ID')) {
          ZM\Logger::Debug("Not defined ZM_SERVER_ID");
        }
        $this->daemonControl($this->Monitor->id, 'start');
      }
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

    $this->daemonControl($this->Monitor->id, 'stop');

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
  // where C=on|off|status
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
      case 'status':
        $verbose = ''; // zmu has a bug - gives incorrect verbose output in this case
        $q = '-s';
        break;      
      default :
        throw new BadRequestException(__('Invalid command'));
    }

    // form auth key based on auth credentials
    $auth = '';
    
    if ( ZM_OPT_USE_AUTH ) {
      global $user;
      $mToken = $this->request->query('token') ? $this->request->query('token') : $this->request->data('token');;
      if ( $mToken ) {
        $auth = ' -T '.$mToken;
      } else if ( ZM_AUTH_RELAY == 'hashed' ) {
        $auth = ' -A '.generateAuthHash(ZM_AUTH_HASH_IPS);
      } else if ( ZM_AUTH_RELAY == 'plain' ) {
        # Plain requires the plain text password which must either be in request or stored in session
        $password = $this->request->query('pass') ? $this->request->query('pass') : $this->request->data('pass');;
        if ( !$password ) 
          $password = $this->request->query('password') ? $this->request->query('password') : $this->request->data('password');

        if ( ! $password ) {
          # during auth the session will have been populated with the plaintext password
          $stateful = $this->request->query('stateful') ? $this->request->query('stateful') : $this->request->data('stateful');
          if ( $stateful ) {
            $password = $_SESSION['password'];
          }
        } else if ( $_COOKIE['ZMSESSID'] ) {
          $password = $_SESSION['password'];
        }

        $auth = ' -U ' .$user['Username'].' -P '.$password;
      } else if ( ZM_AUTH_RELAY == 'none' ) {
        $auth = ' -U ' .$user['Username'];
      }
    }
    
    $shellcmd = escapeshellcmd(ZM_PATH_BIN."/zmu $verbose -m$id $q $auth");
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

    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }

    if (preg_match('/^[a-z]+$/i', $daemon) !== 1) {
      throw new BadRequestException(__('Invalid command'));
    }

    $monitor = $this->Monitor->find('first', array(
      'fields' => array('Id', 'Type', 'Device'),
      'conditions' => array('Id' => $id)
    ));

    // Clean up the returned array
    $monitor = Set::extract('/Monitor/.', $monitor);

    // Pass -d for local, otherwise -m
    if ( $monitor[0]['Type'] == 'Local' ) {
      $args = '-d '. $monitor[0]['Device'];  
    } else {
      $args = '-m '. $monitor[0]['Id'];
    }

    // Build the command, and execute it
    $zm_path_bin = Configure::read('ZM_PATH_BIN');
    $command = escapeshellcmd("$zm_path_bin/zmdc.pl status $daemon $args");
    $status = exec($command);

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

  public function daemonControl($id, $command, $daemon=null) {

    // Need to see if it is local or remote
    $monitor = $this->Monitor->find('first', array(
      'fields' => array('Type', 'Function', 'Device'),
      'conditions' => array('Id' => $id)
    ));
    $monitor = $monitor['Monitor'];

    $daemons = array();
    if ( ! $daemon ) {
      if ( $monitor['Function'] == 'Monitor' ) {
        array_push($daemons, 'zmc');
      } else {
        array_push($daemons, 'zmc', 'zma');
      }
    } else {
      array_push($daemons, $daemon);
    }
    
    $zm_path_bin = Configure::read('ZM_PATH_BIN');

    $status_text = '';
    foreach ( $daemons as $daemon ) {
      $args = '';
      if ( $daemon == 'zmc' and $monitor['Type'] == 'Local' ) {
        $args = '-d ' . $monitor['Device'];
      } else if ( $daemon == 'zmcontrol.pl' ) {
        $args = '--id '.$id;
      } else {
        $args = '-m ' . $id;
      }

      $shellcmd = escapeshellcmd("$zm_path_bin/zmdc.pl $command $daemon $args");
      ZM\Logger::Debug("Command $shellcmd");
      $status = exec($shellcmd);
      $status_text .= $status."\n";
    }
    $this->set(array(
      'status' => 'ok',
      'statustext' => $status_text,
      '_serialize' => array('status','statustext'),
    ));
  } // end function daemonControl
} // end class MonitorsController
