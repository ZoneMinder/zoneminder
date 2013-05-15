<?php

class EventsController extends AppController {
    public $helpers = array('Paginator');
    public $components = array('Paginator');

    public $paginate = array(
		'limit' => 25,
		'order' => array( 'Event.Id' => 'asc'
		)
	);
	$this->loadModel('Monitor');
	$this->loadModel('Config');


    public function index() {
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
