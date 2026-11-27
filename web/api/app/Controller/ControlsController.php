<?php
App::uses('AppController', 'Controller');
/**
 * Controls Controller
 *
 * @property Control $Control
 * @property PaginatorComponent $Paginator
 */

/* 
We need a control API to get the PTZ parameters associated with a monitor
The monitor API returns a control ID which we then need to use to construct
an appropriate PTZ command to control PTZ operations 
https://github.com/ZoneMinder/ZoneMinder/issues/799#issuecomment-105233112
*/
class ControlsController extends AppController {


/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');

  # This controller only reads control definitions (index/view); editing them
  # happens under Options, which requires System. A client building a PTZ
  # command needs to read the monitor's control definition, and the web ui lets
  # anyone who can control a monitor (canView('Control')) do that, so allow it
  # here too rather than requiring System, which locked PTZ clients out.
  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    $canView = (!$user) || $user->canView('System') || $user->canView('Control');
    if (!$canView) {
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
                $this->Control->recursive = 0;
        	$controls = $this->Control->find('all');
        	$this->set(array(
        	    'controls' => $controls,
        	    '_serialize' => array('controls')
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
		if (!$this->Control->exists($id)) {
			throw new NotFoundException(__('Invalid control'));
		}
		$options = array('conditions' => array('Control.' . $this->Control->primaryKey => $id));
		$control = $this->Control->find('first', $options);
		$this->set(array(
			'control' => $control,
			'_serialize' => array('control')
		));
	}

/**
 * command method
 *
 * Send a PTZ command to a monitor, the same way web/ajax/control.php does.
 * The command name and its parameters come from the request (POST body or
 * query string), are checked against the monitor's control capabilities, and
 * are built and sent by the same functions the web UI uses.
 *
 * @param int $id monitor id
 * @return void
 */
  public function command($id = null) {
    if ( !$this->request->is('post') ) {
      throw new MethodNotAllowedException(__('Invalid method. Should be post'));
    }

    if ( $id === null ) $id = $this->requestParam('id');
    if ( $id === null or !is_scalar($id) or !ctype_digit((string)$id) ) {
      throw new BadRequestException(__('Invalid monitor id'));
    }
    $id = intval($id);

    require_once __DIR__ .'/../../../includes/functions.php';
    require_once __DIR__ .'/../../../includes/control_functions.php';
    require_once __DIR__ .'/../../../includes/Monitor.php';

    $monitor = new ZM\Monitor($id);
    if ( !$monitor->Id() ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    // The ajax path requires control of this monitor, which is both the Control
    // area permission and the per-monitor check.
    if ( !canView('Control', $id) ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    if ( !$monitor->Controllable() ) {
      throw new BadRequestException(__('Monitor is not controllable'));
    }

    // buildControlCommand() reads the request directly, so hand it the same
    // parameters the ajax path accepts and nothing else.
    $_REQUEST = array();
    foreach ( array('control', 'xge', 'yge', 'x', 'y', 'scale', 'preset', 'newLabel') as $name ) {
      $value = $this->requestParam($name);
      if ( $value !== null ) $_REQUEST[$name] = $value;
    }

    $control = isset($_REQUEST['control']) ? $_REQUEST['control'] : '';
    if ( !$this->controlCommandIsSupported($monitor, $control) ) {
      throw new BadRequestException(__('Invalid control command'));
    }

    $ctrlCommand = buildControlCommand($monitor);
    if ( !$ctrlCommand ) {
      throw new BadRequestException(__('No command received'));
    }

    if ( $monitor->sendControlCommand($ctrlCommand) ) {
      $this->set(array(
        'message' => 'Success',
        '_serialize' => array('message')
      ));
    } else {
      $this->response->statusCode(400);
      $this->set(array(
        'message' => 'Failed',
        '_serialize' => array('message')
      ));
    }
  }

  // A scalar request parameter from the POST body, query string or named
  // parameters, in that order. Arrays are ignored so they can never reach the
  // command builder.
  private function requestParam($name) {
    $sources = array();
    if ( isset($this->request->data) ) $sources[] = $this->request->data;
    if ( isset($this->request->query) ) $sources[] = $this->request->query;
    foreach ( $sources as $source ) {
      if ( !is_array($source) ) continue;
      if ( isset($source['Control']) and is_array($source['Control'])
          and array_key_exists($name, $source['Control']) ) {
        $value = $source['Control'][$name];
        if ( is_scalar($value) ) return $value;
      }
      if ( array_key_exists($name, $source) ) {
        $value = $source[$name];
        if ( is_scalar($value) ) return $value;
      }
    }
    if ( isset($this->request->params['named'][$name]) ) {
      $value = $this->request->params['named'][$name];
      if ( is_scalar($value) ) return $value;
    }
    return null;
  }

  // Whether the monitor's control actually offers this command. The web UI only
  // draws buttons for the commands Control::commands() returns, so the API
  // accepts the same set; the map commands are built by buildControlCommand()
  // itself and need both coordinates.
  private function controlCommandIsSupported($monitor, $control) {
    if ( !is_string($control) or !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $control) ) {
      return false;
    }

    if ( in_array($control, array('moveMap', 'movePseudoMap', 'moveConMap'), true) ) {
      return isset($_REQUEST['x']) and isset($_REQUEST['y']);
    }

    // Numbered variants carry their preset or audio file in the name.
    $base = preg_replace('/\d+$/', '', $control);
    $zmControl = $monitor->Control();
    foreach ( $zmControl->commands() as $known ) {
      if ( !is_string($known) or $known === '' ) continue;
      if ( $known === $control or $known === $base ) return true;
    }
    return false;
  }
}

