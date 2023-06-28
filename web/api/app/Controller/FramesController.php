<?php
App::uses('AppController', 'Controller');
/**
 * Frames Controller
 *
 * @property Frame $Frame
 */
class FramesController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler');


  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user->Events() != 'None');
    if (!$canView) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

/**
 * index method
 * @return void
 */
	public function index() {
		$this->Frame->recursive = -1;

    global $user;
    $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : null;
    if ( $allowedMonitors ) {
      $mon_options = array('Event.MonitorId' => $allowedMonitors);
    } else {
      $mon_options = '';
    }
    $named_params = $this->request->params['named'];
    if ( $named_params ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($named_params);
    } else {
      $conditions = array();
    }

    $frames = $this->Frame->find('all', ['conditions'=>$conditions]);
		$this->set(array(
			'frames' => $frames,
			'_serialize' => array('frames')
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
		$this->Frame->recursive = -1;
		if (!$this->Frame->exists($id)) {
			throw new NotFoundException(__('Invalid frame'));
		}
		$options = array('conditions' => array('Frame.' . $this->Frame->primaryKey => $id));
		$frame = $this->Frame->find('first', $options);
		$this->set(array(
			'frame' => $frame,
			'_serialize' => array('frame')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Frame->create();
			if ($this->Frame->save($this->request->data)) {
				return $this->flash(__('The frame has been saved.'), array('action' => 'index'));
			}
		}
		$events = $this->Frame->Event->find('list');
		$this->set(compact('events'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->Frame->exists($id)) {
			throw new NotFoundException(__('Invalid frame'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Frame->save($this->request->data)) {
				return $this->flash(__('The frame has been saved.'), array('action' => 'index'));
			}
		} else {
			$options = array('conditions' => array('Frame.' . $this->Frame->primaryKey => $id));
			$this->request->data = $this->Frame->find('first', $options);
		}
		$events = $this->Frame->Event->find('list');
		$this->set(compact('events'));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Frame->id = $id;
		if (!$this->Frame->exists()) {
			throw new NotFoundException(__('Invalid frame'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Frame->delete()) {
			return $this->flash(__('The frame has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The frame could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
