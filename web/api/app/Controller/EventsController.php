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
	public $components = array('RequestHandler', 'Scaler', 'Image');

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
	}

	public function search() {
		$this->Event->recursive = -1;
		$conditions = array();

		foreach ($this->params['named'] as $param_name => $value) {
			// Transform params into mysql
			if (preg_match("/interval/i", $value, $matches)) {
				$condition = array("$param_name >= (date_sub(now(), $value))");
			} else {
				$condition = array($param_name => $value);
			}
			array_push($conditions, $condition);
		}

		$results = $this->Event->find('all', array(
			'conditions' => $conditions
		));

		$this->set(array(
			'results' => $results,
			'_serialize' => array('results')
		));

		
	}

	public function consoleEvents($interval = null) {
		$this->Event->recursive = -1;
		$results = array();

		$query = $this->Event->query("select MonitorId, COUNT(*) AS Count from Events WHERE StartTime >= (DATE_SUB(NOW(), interval $interval)) GROUP BY MonitorId;");

		foreach ($query as $result) {
			$results[$result['Events']['MonitorId']] = $result[0]['Count'];
		}

		$this->set(array(
			'results' => $results,
			'_serialize' => array('results')
		));
	}

	// Create a thumbnail and return the thumbnail's data for a given event id.
	public function createThumbnail($id = null) {
		$this->Event->recursive = -1;

		if (!$this->Event->exists($id)) {
			throw new NotFoundException(__('Invalid event'));
		}

		$event = $this->Event->find('first', array(
			'conditions' => array('Id' => $id)
		));

		// Find the max Frame for this Event.  Error out otherwise.
		$this->loadModel('Frame');
		if (! $frame = $this->Frame->find('first', array(
			'conditions' => array(
				'EventId' => $event['Event']['Id'],
				'Score' => $event['Event']['MaxScore']
			)
		))) {
			throw new NotFoundException(__('Can not find Frame'));
		}

		$this->loadModel('Config');

		// Get the config options required for reScale and getImageSrc
		// The $bw, $thumbs and unset() code is a workaround / temporary
		// until I have a better way of handing per-bandwidth config options
		$bw = strtoupper(substr($_COOKIE['zmBandwidth'], 0, 1));
		$thumbs = "ZM_WEB_${bw}_SCALE_THUMBS";

		$config = $this->Config->find('list', array(
			'conditions' => array('OR' => array(
				'Name' => array('ZM_WEB_LIST_THUMB_WIDTH',
				'ZM_WEB_LIST_THUMB_HEIGHT',
				'ZM_EVENT_IMAGE_DIGITS',
				'ZM_DIR_IMAGES',
				"$thumbs",
				'ZM_DIR_EVENTS'
				)
			)),
			'fields' => array('Name', 'Value')
		));
		$config['ZM_WEB_SCALE_THUMBS'] = $config[$thumbs];
		unset($config[$thumbs]);

		// reScale based on either the width, or the hight, of the event.
		if ( $config['ZM_WEB_LIST_THUMB_WIDTH'] ) {
			$thumbWidth = $config['ZM_WEB_LIST_THUMB_WIDTH'];
			$scale = (100 * $thumbWidth) / $event['Event']['Width'];
			$thumbHeight = $this->Scaler->reScale( $event['Event']['Height'], $scale );
		}
		elseif ( $config['ZM_WEB_LIST_THUMB_HEIGHT'] ) {
			$thumbHeight = $config['ZM_WEB_LIST_THUMB_HEIGHT'];
			$scale = (100*$thumbHeight)/$event['Event']['Height'];
			$thumbWidth = $this->Scaler->reScale( $event['Event']['Width'], $scale );
		}
		else {
			throw new NotFoundException(__('No thumbnail width or height specified, please check in Options->Web'));
		}

		$imageData = $this->Image->getImageSrc( $event, $frame, $scale, $config );
		$thumbData['Path'] = $imageData['thumbPath'];
		$thumbData['Width'] = (int)$thumbWidth;
		$thumbData['Height'] = (int)$thumbHeight;
		
		return( $thumbData );

	}

}
