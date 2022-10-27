<?php
App::uses('AppController', 'Controller');
/**
 * Event_Data Controller
 *
 * @property Event_Data $Event_Data
 */
class Event_DataController extends AppController {

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
		$this->Event_Data->recursive = -1;
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    $find_array = array(
      'conditions' => &$conditions,
    );
    $event_data = $this->Event_Data->find('all', $find_array);
    $this->set(array(
      'event_data' => $event_data,
      '_serialize' => array('event_data')
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
		$this->Event_Data->recursive = -1;
		if (!$this->Event_Data->exists($id)) {
			throw new NotFoundException(__('Invalid event data'));
		}
		$options = array('conditions' => array('Event_Data.' . $this->Event_Data->primaryKey => $id));
		$event_data = $this->Event_Data->find('first', $options);
		$this->set(array(
			'event_data' => $event_data,
			'_serialize' => array('event_data')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Event_Data->create();
			if ($this->Event_Data->save($this->request->data)) {
				return $this->flash(__('The event_data has been saved.'), array('action' => 'index'));
			}
		}
		$events = $this->Event_Data->Event->find('list');
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
		if (!$this->Event_Data->exists($id)) {
			throw new NotFoundException(__('Invalid event_data'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Event_Data->save($this->request->data)) {
				return $this->flash(__('The event_data has been saved.'), array('action' => 'index'));
			}
		} else {
			$options = array('conditions' => array('Event_Data.' . $this->Event_Data->primaryKey => $id));
			$this->request->data = $this->Event_Data->find('first', $options);
		}
		$events = $this->Event_Data->Event->find('list');
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
		$this->Event_Data->id = $id;
		if (!$this->Event_Data->exists()) {
			throw new NotFoundException(__('Invalid event_data'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Event_Data->delete()) {
			return $this->flash(__('The event_data has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The event_data could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
