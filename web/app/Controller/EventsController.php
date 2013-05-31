<?php

class EventsController extends AppController {
    public $helpers = array('Paginator');
    public $components = array('Paginator');

public function index() {
	$this->loadModel('Monitor');
	$this->loadModel('Frame');
  $conditions = array();

  $named = $this->extractNamedParams(
    array('MonitorId')
  );

  if ($named) {
    foreach ($named as $key => $value) {
      $$key = array($key => $value);
      array_push($conditions, $$key);
    }
  };

  $events_per_page = Configure::read('ZM_WEB_EVENTS_PER_PAGE');

	$this->paginate = array(
    'fields' => array('Event.Name', 'Event.Length', 'Event.MonitorId', 'Event.Id', 'Monitor.Name', 'Event.MaxScore', 'Event.Width', 'Event.Height', 'Event.StartTime'),
    'limit' => $events_per_page,
    'order' => array( 'Event.Id' => 'asc'),
    'conditions' => $conditions
	);
	$data = $this->paginate('Event');
	$this->set('events', $data);

	$this->set('monitors', $this->Monitor->find('all', array('fields' => array('Monitor.Name') )));

	$this->set('eventsLastHour', $this->Monitor->query('SELECT COUNT(Event.Id) AS count FROM Monitors AS Monitor LEFT JOIN Events as Event ON Monitor.Id = Event.MonitorId AND Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 HOUR) GROUP BY Monitor.Id'));
	$this->set('eventsLastDay', $this->Monitor->query('SELECT COUNT(Event.Id) AS count FROM Monitors AS Monitor LEFT JOIN Events as Event ON Monitor.Id = Event.MonitorId AND Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY Monitor.Id'));
	$this->set('eventsLastWeek', $this->Monitor->query('SELECT COUNT(Event.Id) AS count FROM Monitors AS Monitor LEFT JOIN Events as Event ON Monitor.Id = Event.MonitorId AND Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 WEEK) GROUP BY Monitor.Id'));
	$this->set('eventsLastMonth', $this->Monitor->query('SELECT COUNT(Event.Id) AS count FROM Monitors AS Monitor LEFT JOIN Events as Event ON Monitor.Id = Event.MonitorId AND Event.StartTime > DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY Monitor.Id'));
	$this->set('eventsArchived', $this->Monitor->query('SELECT COUNT(Event.Id) AS count FROM Monitors AS Monitor LEFT JOIN Events as Event ON Monitor.Id = Event.MonitorId AND Event.Archived = 1 GROUP BY Monitor.Id'));

    foreach ($data as $key => $value) {
        $thumbData[$key] = $this->Frame->createListThumbnail($value['Event']);
        $this->set('thumbData', $thumbData);
  }
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
