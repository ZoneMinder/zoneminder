<?php
App::uses('AppController', 'Controller');
require_once __DIR__ .'/../../../includes/Event.php';
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

  # A single frame is addressed by (EventId, FrameId), its primary key.
  private function findFrame($eventId, $frameId) {
    $this->Frame->recursive = -1;
    $frame = $this->Frame->find('first', array(
      'conditions' => array('Frame.EventId' => $eventId, 'Frame.FrameId' => $frameId)
    ));
    if (!$frame) {
      throw new NotFoundException(__('Invalid frame'));
    }
    return $frame;
  }

  # Frames carry no MonitorId, so the parent Event's per-monitor ACL has to be
  # resolved explicitly.
  private function eventForFrame($eventId, $frameId) {
    $frame = $this->findFrame($eventId, $frameId);
    $this->loadModel('Event');
    $this->Event->recursive = -1;
    $event = $this->Event->find('first', array(
      'conditions' => array('Event.Id' => $frame['Frame']['EventId'])
    ));
    if (!$event) {
      throw new NotFoundException(__('Invalid event'));
    }
    return new ZM\Event($event['Event']);
  }

  # A frame being added names its event in the request data. Require edit on
  # that event too, or a user could attach frames to a denied monitor's event.
  private function requireRequestEventEdit() {
    $eventId = $this->requestField('Frame', 'EventId');
    if ($eventId === null) {
      throw new BadRequestException(__('EventId is required'));
    }
    $this->loadModel('Event');
    $this->Event->recursive = -1;
    $event = $this->Event->find('first', array('conditions' => array('Event.Id' => $eventId)));
    if (!$event) {
      throw new NotFoundException(__('Invalid event'));
    }
    $event = new ZM\Event($event['Event']);
    if (!$event->canEdit()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  # Frame mutation is an Event mutation, so require Events=Edit as well as the
  # per-monitor ACL. beforeFilter() only guarantees Events != None.
  private function requireFrameEdit($eventId, $frameId) {
    global $user;
    if ($user and ($user->Events() != 'Edit')) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    if (!$this->eventForFrame($eventId, $frameId)->canEdit()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  # The request's Frame fields that are real columns. Model save() cannot be
  # used to write them: it would match rows on the single-column primaryKey.
  private function requestColumns($exclude = array()) {
    $data = $this->request->data;
    if (isset($data['Frame']) and is_array($data['Frame'])) $data = $data['Frame'];
    $columns = array();
    foreach (array_keys($this->Frame->schema()) as $field) {
      if (in_array($field, $exclude) or !array_key_exists($field, $data)) continue;
      $columns[$field] = $data[$field];
    }
    return $columns;
  }

/**
 * index method
 * @return void
 */
	public function index() {
		$this->Frame->recursive = -1;

    global $user;
    $monitorCondition = $this->viewableMonitorCondition('Event.MonitorId');

    $named_params = $this->request->params['named'];
    if ( $named_params ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($named_params);
    } else {
      $conditions = array();
    }

    $findOptions = array('conditions' => $conditions);
    if ( count($monitorCondition) ) {
      // Frame has no MonitorId of its own, and recursive=-1 above means the
      // Event association isn't auto-joined, so the per-monitor ACL has to
      // join through to the owning Event explicitly.
      $findOptions['joins'] = array(array(
        'table' => 'Events',
        'alias' => 'Event',
        'type' => 'inner',
        'conditions' => array('Event.Id = Frame.EventId'),
      ));
      $findOptions['conditions'][] = $monitorCondition;
    }

    $frames = $this->Frame->find('all', $findOptions);
		$this->set(array(
			'frames' => $frames,
			'_serialize' => array('frames')
		));
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $eventId
 * @param string $frameId
 * @return void
 */
	public function view($eventId = null, $frameId = null) {
		if (!$this->eventForFrame($eventId, $frameId)->canView()) {
			throw new UnauthorizedException(__('Insufficient Privileges'));
		}
		$this->set(array(
			'frame' => $this->findFrame($eventId, $frameId),
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
			global $user;
			if ($user and ($user->Events() != 'Edit')) {
				throw new UnauthorizedException(__('Insufficient Privileges'));
			}
			$this->requireRequestEventEdit();
			$columns = $this->requestColumns();
			$this->Frame->set($columns);
			if ($this->Frame->validates() and
				$this->Frame->getDataSource()->create($this->Frame, array_keys($columns), array_values($columns))) {
				return $this->flash(__('The frame has been saved.'), array('action' => 'index'));
			}
		}
		$events = $this->Frame->Event->find('list');
		$this->set(compact('events'));
	}

/**
 * edit method
 *
 * EventId and FrameId are the key and cannot be changed.
 *
 * @throws NotFoundException
 * @param string $eventId
 * @param string $frameId
 * @return void
 */
	public function edit($eventId = null, $frameId = null) {
		$this->requireFrameEdit($eventId, $frameId);
		if ($this->request->is(array('post', 'put'))) {
			# updateAll() takes SQL expressions, so the values must be quoted here.
			$db = $this->Frame->getDataSource();
			$columns = array();
			foreach ($this->requestColumns(array('EventId', 'FrameId')) as $field => $value) {
				$columns[$field] = $db->value($value, $this->Frame->getColumnType($field));
			}
			if (count($columns) and $this->Frame->updateAll($columns,
				array('Frame.EventId' => $eventId, 'Frame.FrameId' => $frameId))) {
				return $this->flash(__('The frame has been saved.'), array('action' => 'index'));
			}
		} else {
			$this->request->data = $this->findFrame($eventId, $frameId);
		}
		$events = $this->Frame->Event->find('list');
		$this->set(compact('events'));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $eventId
 * @param string $frameId
 * @return void
 */
	public function delete($eventId = null, $frameId = null) {
		$this->request->allowMethod('post', 'delete');
		$this->requireFrameEdit($eventId, $frameId);
		if ($this->Frame->deleteAll(array('Frame.EventId' => $eventId, 'Frame.FrameId' => $frameId), false)) {
			return $this->flash(__('The frame has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The frame could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
