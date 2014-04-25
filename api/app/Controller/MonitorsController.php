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

/**
 * index method
 *
 * @return void
 */
	public function index() {
                $this->Monitor->recursive = 0;
        	$monitors = $this->Monitor->find('all');
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
		$options = array('conditions' => array('Monitor.' . $this->Monitor->primaryKey => $id));
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
			$this->Monitor->create();
			if ($this->Monitor->save($this->request->data)) {
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

		if ($this->Monitor->save($this->request->data)) {
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
		$this->Monitor->id = $id;
		if (!$this->Monitor->exists()) {
			throw new NotFoundException(__('Invalid monitor'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Monitor->delete()) {
			return $this->flash(__('The monitor has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The monitor could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
