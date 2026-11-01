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
}

