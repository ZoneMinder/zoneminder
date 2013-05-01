<?php

class EventsController extends AppController {
    public $helpers = array('Html', 'Form', 'Paginator');
    public $components = array('Paginator');

    public $paginate = array(
		'limit' => 25,
		'order' => array( 'Event.Id' => 'asc'
		)
	);


    public function index() {
#        $this->set('events', $this->Event->find('all', array('limit' => 1000)));
	$data = $this->paginate('Event');
	$this->set('events', $data);
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
