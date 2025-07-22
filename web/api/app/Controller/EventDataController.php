<?php
App::uses('AppController', 'Controller');
/**
 * EventData Controller
 *
 * @property EventData $EventData
 */
class EventDataController extends AppController {

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
 *
 * @return void
 */
	public function index() {
		$this->EventData->recursive = -1;
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    $find_array = array(
      'conditions' => &$conditions,
    );
    $event_data = $this->EventData->find('all', $find_array);
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
		$this->EventData->recursive = -1;
		if (!$this->EventData->exists($id)) {
			throw new NotFoundException(__('Invalid event data'));
		}
		$options = array('conditions' => array('Event_Data.' . $this->EventData->primaryKey => $id));
		$event_data = $this->EventData->find('first', $options);
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
			$this->EventData->create();
			if ($this->EventData->save($this->request->data)) {
			}
		}
		$events = $this->EventData->Event->find('list');
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
		if (!$this->EventData->exists($id)) {
			throw new NotFoundException(__('Invalid event_data'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->EventData->save($this->request->data)) {
			}
		} else {
			$options = array('conditions' => array('Event_Data.' . $this->EventData->primaryKey => $id));
			$this->request->data = $this->EventData->find('first', $options);
		}
		$events = $this->EventData->Event->find('list');
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
		$this->EventData->id = $id;
		if (!$this->EventData->exists()) {
			throw new NotFoundException(__('Invalid event_data'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->EventData->delete()) {
			return $this->flash(__('The event_data has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The event_data could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
