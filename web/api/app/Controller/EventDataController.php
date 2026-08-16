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

  # Event_Data mutation requires Events=Edit (beforeFilter only guarantees
  # Events != None) plus the per-monitor ACL on the row being changed, so a
  # user denied a monitor cannot alter that monitor's event data by Id.
  private function requireEventDataEdit($id) {
    global $user;
    if ( $user and ($user->Events() != 'Edit') ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : null;
    if ( $allowedMonitors !== null ) {
      $this->EventData->recursive = -1;
      $row = $this->EventData->find('first', array(
        'conditions' => array($this->EventData->alias.'.'.$this->EventData->primaryKey => $id),
      ));
      $monitorId = $row ? $row[$this->EventData->alias]['MonitorId'] : null;
      if ( !in_array($monitorId, $allowedMonitors) ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
      }
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

    # Event_Data carries its own MonitorId, so the per-monitor ACL that
    # EventsController applies via Event.MonitorId can be applied directly here.
    # Without it any user with Events != None reads event data for cameras they
    # are explicitly denied.
    global $user;
    $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : array();
    if ( count($allowedMonitors) ) {
      $conditions[] = array($this->EventData->alias.'.MonitorId' => $allowedMonitors);
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
    global $user;
    $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : array();
    $conditions = array($this->EventData->alias.'.'.$this->EventData->primaryKey => $id);
    if ( count($allowedMonitors) ) {
      $conditions[$this->EventData->alias.'.MonitorId'] = $allowedMonitors;
    }
		$event_data = $this->EventData->find('first', array('conditions' => $conditions));
    if ( !$event_data ) {
      # exists() above proved the row is present, so an empty result here means
      # the per-monitor ACL filtered it out: the caller is denied this monitor.
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
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
		$this->requireEventDataEdit($id);
		if ($this->request->is(array('post', 'put'))) {
			if ($this->EventData->save($this->request->data)) {
			}
		} else {
			$options = array('conditions' => array($this->EventData->alias.'.'.$this->EventData->primaryKey => $id));
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
		$this->requireEventDataEdit($id);
		if ($this->EventData->delete()) {
			return $this->flash(__('The event_data has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The event_data could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
