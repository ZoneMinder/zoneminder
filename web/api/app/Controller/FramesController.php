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

  # Frames are addressed by their own Id, so the parent Event's per-monitor ACL
  # has to be resolved explicitly. Without this a user denied a monitor can
  # reach that monitor's frames by guessing frame Ids.
  private function eventForFrame($id) {
    $this->Frame->recursive = -1;
    $frame = $this->Frame->find('first', array(
      'conditions' => array('Frame.' . $this->Frame->primaryKey => $id)
    ));
    if (!$frame) {
      throw new NotFoundException(__('Invalid frame'));
    }
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

  # A frame being added or re-pointed names its event in the request data. Require
  # edit on that event too, or a user could attach frames to a denied monitor's event.
  private function requireRequestEventEdit($required) {
    $data = $this->request->data;
    if (isset($data['Frame']) and is_array($data['Frame'])) $data = $data['Frame'];
    if (!isset($data['EventId'])) {
      if ($required) throw new BadRequestException(__('EventId is required'));
      return;
    }
    $this->loadModel('Event');
    $this->Event->recursive = -1;
    $event = $this->Event->find('first', array('conditions' => array('Event.Id' => $data['EventId'])));
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
  private function requireFrameEdit($id) {
    global $user;
    if ($user and ($user->Events() != 'Edit')) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    if (!$this->eventForFrame($id)->canEdit()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
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
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->Frame->recursive = -1;
		if (!$this->Frame->exists($id)) {
			throw new NotFoundException(__('Invalid frame'));
		}
		if (!$this->eventForFrame($id)->canView()) {
			throw new UnauthorizedException(__('Insufficient Privileges'));
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
			global $user;
			if ($user and ($user->Events() != 'Edit')) {
				throw new UnauthorizedException(__('Insufficient Privileges'));
			}
			$this->requireRequestEventEdit(true);
			$this->pinRequestId($this->Frame, null);
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
		$this->requireFrameEdit($id);
		if ($this->request->is(array('post', 'put'))) {
			$this->pinRequestId($this->Frame, $id);
			$this->requireRequestEventEdit(false);
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
		$this->requireFrameEdit($id);
		if ($this->Frame->delete()) {
			return $this->flash(__('The frame has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The frame could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
