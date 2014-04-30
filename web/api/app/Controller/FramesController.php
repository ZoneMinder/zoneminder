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

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Frame->recursive = -1;
		$frames = $this->Frame->find('all');
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
