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
  public $components = array('RequestHandler', 'Scaler', 'Image', 'Paginator');

  public function beforeRender() {
    $this->set($this->Event->enumValues());
  }

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    $canView = (!$user) || ($user['Events'] != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

  /**
   * index method
   *
   * @return void
   * This also creates a thumbnail for each event.
   */
  public function index() {
    $this->Event->recursive = -1;

    global $user;
    require_once __DIR__ .'/../../../includes/Event.php';
    $allowedMonitors = $user ? preg_split('@,@', $user['MonitorIds'], NULL, PREG_SPLIT_NO_EMPTY) : null;

    if ( $allowedMonitors ) {
      $mon_options = array('Event.MonitorId' => $allowedMonitors);
    } else {
      $mon_options = '';
    }

    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }
    $settings = array(
      // https://github.com/ZoneMinder/ZoneMinder/issues/995
      // 'limit' => $limit['ZM_WEB_EVENTS_PER_PAGE'],
      //  25 events per page which is what the above
      // default is, is way too low for an API
      // changing this to 100 so we don't kill ZM
      // with many event APIs. In future, we can
      // make a nice ZM_API_ITEMS_PER_PAGE for all pagination
      // API

      'limit' => '100',
      'order' => array('StartTime'),
      'paramType' => 'querystring',
    );
    if ( isset($conditions['GroupId']) ) {
      $settings['joins'] = array(
        array(
          'table' => 'Groups_Monitors',
          'type' => 'inner',
          'conditions' => array(
            'Groups_Monitors.MonitorId = Event.MonitorId'
          ),
        ),
      );
      $settings['contain'] = array('Group');
    }
    $settings['conditions'] = array($conditions, $mon_options);

    // How many events to return 
    $this->loadModel('Config');
    $limit = $this->Config->find('list', array(
      'conditions' => array('Name' => 'ZM_WEB_EVENTS_PER_PAGE'),
      'fields' => array('Name', 'Value')
    ));
    $this->Paginator->settings = $settings;
    $events = $this->Paginator->paginate('Event');

    // For each event, get the frameID which has the largest score
    // also add FS path

    foreach ( $events as $key => $value ) {
      $EventObj = new ZM\Event($value['Event']['Id']);
      $maxScoreFrameId = $this->getMaxScoreAlarmFrameId($value['Event']['Id']);
      $events[$key]['Event']['MaxScoreFrameId'] = $maxScoreFrameId;
      $events[$key]['Event']['FileSystemPath'] = $EventObj->Path();
    }

    $this->set(compact('events'));
  } // end public function index()

  /**
   * view method
   *
   * @throws NotFoundException
   * @param string $id
   * @return void
   */
  public function view($id = null) {
    $this->loadModel('Config');

    $this->Event->recursive = 1;
    if ( !$this->Event->exists($id) ) {
      throw new NotFoundException(__('Invalid event'));
    }

    global $user;
    $allowedMonitors = $user ? preg_split('@,@', $user['MonitorIds'], NULL, PREG_SPLIT_NO_EMPTY) : null;

    if ( $allowedMonitors ) {
      $mon_options = array('Event.MonitorId' => $allowedMonitors);
    } else {
      $mon_options = '';
    }

    $options = array('conditions' => array(array('Event.' . $this->Event->primaryKey => $id), $mon_options));
    $event = $this->Event->find('first', $options);

    # Get the previous and next events for any monitor
    $this->Event->id = $id;
    $event_neighbors = $this->Event->find('neighbors');
    $event['Event']['Next'] = $event_neighbors['next']['Event']['Id'];
    $event['Event']['Prev'] = $event_neighbors['prev']['Event']['Id'];

    $event['Event']['fileExists'] = $this->Event->fileExists($event['Event']);
    $event['Event']['fileSize'] = $this->Event->fileSize($event['Event']);

    $EventObj = new ZM\Event($id);
    $event['Event']['FileSystemPath'] = $EventObj->Path();

    # Also get the previous and next events for the same monitor
    $event_monitor_neighbors = $this->Event->find('neighbors', array(
      'conditions'=>array('Event.MonitorId'=>$event['Event']['MonitorId'])
    ));
    $event['Event']['NextOfMonitor'] = $event_monitor_neighbors['next']['Event']['Id'];
    $event['Event']['PrevOfMonitor'] = $event_monitor_neighbors['prev']['Event']['Id'];
  
    $this->loadModel('Frame');
    $event['Event']['MaxScoreFrameId'] = $this->Frame->findByEventid($id,'FrameId',array('Score'=>'desc','FrameId'=>'asc'))['Frame']['FrameId'];
    $event['Event']['AlarmFrameId'] = $this->Frame->findByEventidAndType($id,'Alarm')['Frame']['FrameId'];

    $this->set(array(
      'event' => $event,
      '_serialize' => array('event')
    ));
  } // end function view


  /**
   * add method
   *
   * @return void
   */
  public function add() {

    global $user;
    $canEdit = (!$user) || ($user['Events'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( $this->request->is('post') ) {
      $this->Event->create();
      if ( $this->Event->save($this->request->data) ) {
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

    global $user;
    $canEdit = (!$user) || ($user['Events'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->Event->id = $id;

    if ( !$this->Event->exists($id) ) {
      throw new NotFoundException(__('Invalid event'));
    }

    if ( $this->Event->save($this->request->data) ) {
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
    global $user;
    $canEdit = (!$user) || ($user['Events'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }
    $this->Event->id = $id;
    if ( !$this->Event->exists() ) {
      throw new NotFoundException(__('Invalid event'));
    }
    $this->request->allowMethod('post', 'delete');
    if ( $this->Event->delete() ) {
      //$this->loadModel('Frame');
      //$this->Event->Frame->delete();
      return $this->flash(__('The event has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The event could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  } // end public function delete

  public function search() {
    $this->Event->recursive = -1;
    // Unmodified conditions to pass to find()
    $find_conditions = array();
    // Conditions to be filtered by buildFilter
    $conditions = array();

    foreach ($this->params['named'] as $param_name => $value) {
      // Transform params into conditions
      if ( preg_match('/^\s?interval\s?/i', $value) ) {
        if (preg_match('/^[a-z0-9]+$/i', $param_name) !== 1) {
          throw new Exception('Invalid field name: ' . $param_name);
        }
        $matches = NULL;
        $value = preg_replace('/^\s?interval\s?/i', '', $value);
        if (preg_match('/^(?P<expr>[ -.:0-9\']+)\s+(?P<unit>[_a-z]+)$/i', trim($value), $matches) !== 1) {
          throw new Exception('Invalid interval: ' . $value);
        }
        $expr = trim($matches['expr']);
        $unit = trim($matches['unit']);
        array_push($find_conditions, "$param_name >= DATE_SUB(NOW(), INTERVAL $expr $unit)");
      } else {
        $conditions[$param_name] = $value;
      }
    }

    $this->FilterComponent = $this->Components->load('Filter');
    $conditions = $this->FilterComponent->buildFilter($conditions);
    array_push($conditions, $find_conditions);

    $results = $this->Event->find('all', array(
      'conditions' => $conditions
    ));

    $this->set(array(
      'results' => $results,
      '_serialize' => array('results')
    ));
  } // end public function search

  // format expected:
  // you can changed AlarmFrames to any other named params
  // consoleEvents/1 hour/AlarmFrames >=: 1/AlarmFrames <=: 20.json

  public function consoleEvents($interval = null) {
    $matches = NULL;
    // https://dev.mysql.com/doc/refman/5.5/en/expressions.html#temporal-intervals
    // Examples: `'1-1' YEAR_MONTH`, `'-1 10' DAY_HOUR`, `'1.999999' SECOND_MICROSECOND`
    if (preg_match('/^(?P<expr>[ -.:0-9\']+)\s+(?P<unit>[_a-z]+)$/i', trim($interval), $matches) !== 1) {
      throw new Exception('Invalid interval: ' . $interval);
    }
    $expr = trim($matches['expr']);
    $unit = trim($matches['unit']);

    $this->Event->recursive = -1;
    $results = array();
    $this->FilterComponent = $this->Components->load('Filter');
    if ( $this->request->params['named'] ) {
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    } 
    array_push($conditions, array("StartTime >= DATE_SUB(NOW(), INTERVAL $expr $unit)"));
    $query = $this->Event->find('all', array(
                                             'fields' => array(
                                                               'MonitorId',
                                                               'COUNT(*) AS Count',
                                                               ),
                                             'conditions' => $conditions,
                                             'group' => 'MonitorId',
    ));

    foreach ($query as $result) {
      $results[$result['Event']['MonitorId']] = $result[0]['Count'];
    }

    $this->set(array(
      'results' => $results,
      '_serialize' => array('results')
    ));
  }

  // Create a thumbnail and return the thumbnail's data for a given event id.
  public function createThumbnail($id = null) {
    $this->Event->recursive = -1;

    if ( !$this->Event->exists($id) ) {
      throw new NotFoundException(__('Invalid event'));
    }

    $event = $this->Event->find('first', array(
      'conditions' => array('Id' => $id)
    ));

    // Find the max Frame for this Event.  Error out otherwise.
    $this->loadModel('Frame');
    if ( !( $frame = $this->Frame->find('first', array(
      'conditions' => array(
        'EventId' => $event['Event']['Id'],
        'Score' => $event['Event']['MaxScore']
      )
    ))) ) {
      throw new NotFoundException(__('Can not find Frame for Event ' . $event['Event']['Id']));
    }

    $this->loadModel('Config');

    // Get the config options required for reScale and getImageSrc
    // The $bw, $thumbs and unset() code is a workaround / temporary
    // until I have a better way of handing per-bandwidth config options
    $bw = (isset($_COOKIE['zmBandwidth']) ? strtoupper(substr($_COOKIE['zmBandwidth'], 0, 1)) : 'L');
    $thumbs = "ZM_WEB_${bw}_SCALE_THUMBS";

    $config = $this->Config->find('list', array(
      'conditions' => array('OR' => array(
        'Name' => array(
          'ZM_WEB_LIST_THUMB_WIDTH',
          'ZM_WEB_LIST_THUMB_HEIGHT',
          'ZM_EVENT_IMAGE_DIGITS',
          $thumbs,
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
    } elseif ( $config['ZM_WEB_LIST_THUMB_HEIGHT'] ) {
      $thumbHeight = $config['ZM_WEB_LIST_THUMB_HEIGHT'];
      $scale = (100*$thumbHeight)/$event['Event']['Height'];
      $thumbWidth = $this->Scaler->reScale( $event['Event']['Width'], $scale );
    } else {
      throw new NotFoundException(__('No thumbnail width or height specified, please check in Options->Web'));
    }

    $imageData = $this->Image->getImageSrc( $event, $frame, $scale, $config );
    $thumbData['Path'] = $imageData['thumbPath'];
    $thumbData['Width'] = (int)$thumbWidth;
    $thumbData['Height'] = (int)$thumbHeight;

    return $thumbData;
  }

  public function archive($id = null) {
    $this->Event->recursive = -1;
    if ( !$this->Event->exists($id) ) {
      throw new NotFoundException(__('Invalid event'));
    }

    // Get the current value of Archive
    $archived = $this->Event->find('first', array(
      'fields' => array('Event.Archived'),
      'conditions' => array('Event.Id' => $id)
    ));
    // If 0, 1, if 1, 0
    $archiveVal = (($archived['Event']['Archived'] == 0) ? 1 : 0);

    // Save the new value 
    $this->Event->id = $id;
    $this->Event->saveField('Archived', $archiveVal);

    $this->set(array(
      'archived' => $archiveVal,
      '_serialize' => array('archived')
    ));
  }

  public function getMaxScoreAlarmFrameId($id = null) {
    $this->Event->recursive = -1;

    if ( !$this->Event->exists($id) ) {
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
      throw new NotFoundException(__('Can not find Frame for Event ' . $event['Event']['Id']));
    }
    return $frame['Frame']['Id'];
  }
} // end class EventsController
