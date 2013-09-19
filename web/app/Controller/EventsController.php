<?php

class EventsController extends AppController {
    public $helpers = array('Paginator');
    public $components = array('Paginator');

public function index() {

	$this->loadModel('Monitor');
	$this->loadModel('Frame');
	$conditions = array();

	$this->set('thumb_width', Configure::read('ZM_WEB_LIST_THUMB_WIDTH'));

  $named = $this->extractNamedParams(
	  array('MonitorId', 'StartTime' )
  );

  if ($named) {
	  foreach ($named as $key => $value) {
		  switch ($key) {
		  case "StartTime":
			  $StartTime = array("$key BETWEEN FROM_UNIXTIME($value[0]) and FROM_UNIXTIME($value[1])");
			  array_push($conditions, $StartTime);
			  break;
		  case "MonitorId":
			  $$key = array($key => $value);
			  array_push($conditions, $$key);
			  break;
		  }
	  }
  };

	$this->paginate = array(
    'fields' => array('Event.Name', 'Event.Length', 'Event.MonitorId', 'Event.Id', 'Monitor.Name', 'Event.MaxScore', 'Event.Width', 'Event.Height', 'Event.StartTime', 'Event.TotScore', 'Event.AvgScore', 'Event.Cause', 'Event.AlarmFrames', 'TIMESTAMPDIFF (SECOND, Event.StartTime, Event.EndTime) AS Duration' ),
    'limit' => Configure::read('ZM_WEB_EVENTS_PER_PAGE'),
    'order' => array( 'Event.Id' => 'asc'),
    'conditions' => $conditions
	);
	$data = $this->paginate('Event');
	$this->set('events', $data);

	$this->set('monitors', $this->Monitor->find('all', array('fields' => array('Monitor.Name') )));

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

    if (!strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
      $videoFormat = 'webm';
    } else {
      $videoFormat = 'mp4';
    }
    $this->set('videoSrc', $this->Event->createVideo( $id, $videoFormat, 100, 100 ));

  }

  public function delete($id) {
    if ($this->request->is('get')) {
      throw new MethodNotAllowedException();
    }

    if ($this->Event->delete($id)) {
      return $this->redirect(array('action' => 'index'));
    }
  }
  
  public function deleteSelected() {
    foreach($this->data['Events'] as $key => $value) {
      $this->Event->delete($value);
    }
    $this->redirect($this->referer());
  }
}

?>
