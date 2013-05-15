<?php

class EventsController extends AppController {
    public $helpers = array('Paginator');
    public $components = array('Paginator');

	$this->loadModel('Monitor');
	$this->loadModel('Config');

	$events_per_page = $this->Config->find('first', array('conditions' => array('Name' => 'ZM_WEB_EVENTS_PER_PAGE'), 'fields' => 'Value'));

    public function index() {
	$this->paginate = array(
		'fields' => array('Event.Name', 'Event.Length', 'Event.MonitorId', 'Event.Id', 'Monitor.Name'),
		'limit' => $events_per_page['Config']['Value'],
		'order' => array( 'Event.Id' => 'asc')
	);
	$data = $this->paginate('Event');
	$this->set('events', $data);
	$options = array('fields' => array('DISTINCT Monitor.Id'));
	$this->set('monitors', $this->Event->Monitor->Find('all', $options));
    }

  public function view($id = null) {
    if (!$id) {
       throw new NotFoundException(__('Invalid event'));
    }

    $event = $this->Event->findById($id);
    if (!$event) {
       throw new NotFoundException(__('Invalid event'));
    }
    $this->set('event', $event);
  }

}

?>
