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

	// Frames belong to an event, so a user without Events permission, or restricted to
	// certain monitors, must not read arbitrary frames. index() returned the whole Frames
	// table and view() any frame, with no check. refs per-monitor ACL (425e472ec).
	public function beforeFilter() {
		parent::beforeFilter();
		global $user;
		if ($user and ($user['Events'] == 'None')) {
			throw new UnauthorizedException(__('Insufficient Privileges'));
		}
	}

	// For a user restricted to specific monitors: a condition limiting frames to events of
	// those monitors, or '' when the user is not restricted.
	private function allowedFrameCondition() {
		global $user;
		$allowed = $user ? preg_split('@,@', $user['MonitorIds'], NULL, PREG_SPLIT_NO_EMPTY) : [];
		if (!count($allowed)) return '';
		$ids = implode(',', array_map('intval', $allowed));
		return array('Frame.EventId IN (SELECT Id FROM Events WHERE MonitorId IN ('.$ids.'))');
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Frame->recursive = -1;
		$cond = $this->allowedFrameCondition();
		$frames = $this->Frame->find('all', $cond ? array('conditions' => $cond) : array());
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
		$conditions = array('Frame.' . $this->Frame->primaryKey => $id);
		$cond = $this->allowedFrameCondition();
		if ($cond) $conditions = array_merge($conditions, $cond);
		$frame = $this->Frame->find('first', array('conditions' => $conditions));
		if (!$frame) throw new UnauthorizedException(__('Insufficient Privileges'));
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
