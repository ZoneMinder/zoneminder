<?php
App::uses('AppController', 'Controller');
/**
 * Events Controller
 *
 * @property Event $Event
 */
class EventsController extends AppController {

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
		$this->Event->recursive = -1;
		$events = $this->Event->find('all');
		$this->set(array(
			'events' => $events,
			'_serialize' => array('events')
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
		$this->Event->recursive = -1;
		if (!$this->Event->exists($id)) {
			throw new NotFoundException(__('Invalid event'));
		}
		$options = array('conditions' => array('Event.' . $this->Event->primaryKey => $id));
		$event = $this->Event->find('first', $options);
		$this->set(array(
			'event' => $event,
			'_serialize' => array('event')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Event->create();
			if ($this->Event->save($this->request->data)) {
				return $this->flash(__('The event has been saved.'), array('action' => 'index'));
			}
		}
		$monitors = $this->Event->Monitor->find('list');
		$this->set(compact('monitors'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->Event->id = $id;

		if (!$this->Event->exists($id)) {
			throw new NotFoundException(__('Invalid event'));
		}

		if ($this->Event->save($this->request->data)) {
			$message = 'Saved';
		} else {
			$message = 'Error';
		}

		$this->set(array(
			'message' => $message,
			'_serialize' => array('message')
		));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Event->id = $id;
		if (!$this->Event->exists()) {
			throw new NotFoundException(__('Invalid event'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Event->delete()) {
			return $this->flash(__('The event has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The event could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
