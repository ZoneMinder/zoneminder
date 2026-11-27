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
    $canView = (!$user) || ($user->canView('Events'));
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

  // The columns of Events and of Tags.Id, the one joined column index() filters on.
  // Qualify bare Events columns so they aren't ambiguous next to the joins.
  private function filterColumns() {
    return array(
      'Event' => array_keys($this->Event->schema()),
      'Tags' => array('Id'),
    );
  }

  private function renameLegacyTimeParams($params) {
    # In 1.35.13 we renamed StartTime and EndTime to StartDateTime and EndDateTime.
    # This hack renames the query string params
    foreach ( $params as $k=>$v ) {
      if ( false !== strpos($k, 'StartTime') ) {
        $new_k = preg_replace('/StartTime/', 'StartDateTime', $k);
        $params[$new_k] = $params[$k];
        unset($params[$k]);
      }
      if ( false !== strpos($k, 'EndTime') ) {
        $new_k = preg_replace('/EndTime/', 'EndDateTime', $k);
        $params[$new_k] = $params[$k];
        unset($params[$k]);
      }
    }
    return $params;
  }

  private function datetimeConditions($conditions) {
    # DateTime is a pseudo-attribute meaning "the event was running then", so a
    # window over it is an overlap test: the event started by the upper bound
    # and had not finished by the lower bound. Applying the term's own operator
    # to both StartDateTime and EndDateTime instead makes the upper bound a
    # containment test, which drops every event that spans the end of the
    # window -- with continuous recording that is most of them.
    $datetime_terms = array();
    foreach ($conditions as $k=>$v) {
      if ( 0 === strpos($k, 'DateTime') ) {
        $datetime_terms[$k] = $v;
        unset($conditions[$k]);
      }
    }
    if ($datetime_terms) {
      # An event still being written has no EndDateTime, but it is not
      # unbounded: zmc flushes Length every few seconds, so StartDateTime +
      # Length is its effective end. Only an event with neither falls back to
      # NOW(). Treating a missing EndDateTime as "matches any window" instead
      # made every crash-orphaned event ever recorded match every query.
      $effective_end = '(CASE'
        .' WHEN Event.EndDateTime IS NOT NULL THEN Event.EndDateTime'
        .' WHEN Event.Length > 0 THEN DATE_ADD(Event.StartDateTime, INTERVAL FLOOR(Event.Length) SECOND)'
        .' ELSE NOW() END)';
      $ds = $this->Event->getDataSource();
      foreach ($datetime_terms as $k=>$v) {
        $op = trim(substr($k, strlen('DateTime')));
        if ($op == '<' or $op == '<=') {
          # Upper bound: the event must have started by then.
          $conditions[] = array('Event.StartDateTime '.$op => $v);
        } else if ($op == '>' or $op == '>=') {
          # Lower bound: the event must not have ended before then.
          $conditions[] = $effective_end.' '.$op.' '.$ds->value($v, 'string');
        } else {
          # Anything else (=, !=) still matches against either end.
          $conditions[] = array('OR' => array(
            array('Event.StartDateTime '.$op => $v),
            array('Event.EndDateTime '.$op => $v),
          ));
        }
      }
    } // end if datetime terms
    return $conditions;
  }

  /**
   * index method
   *
   * @return void
   * This also creates a thumbnail for each event.
   */
  public function index() {
    $this->Event->recursive = 0;

    global $user;
    require_once __DIR__ .'/../../../includes/Event.php';
    $mon_options = $this->viewableMonitorCondition('Event.MonitorId');

    $this->FilterComponent = $this->Components->load('Filter');
    $named_params = $this->request->params['named'];
    $tag_filter_value = null;
    $tag_filter_field = null;
    if ($named_params) {
      if (isset($named_params['TagId'])) {
        $tag_filter_value = $named_params['TagId'];
        $tag_filter_field = 'Id';
        unset($named_params['TagId']);
      } else if (isset($named_params['Tag'])) {
        $tag_filter_value = $named_params['Tag'];
        $tag_filter_field = 'Name';
        unset($named_params['Tag']);
      } else if (isset($named_params['Tags'])) {
        $tag_filter_value = $named_params['Tags'];
        $tag_filter_field = 'Name';
        unset($named_params['Tags']);
      }
      $named_params = $this->renameLegacyTimeParams($named_params);
      // Events columns, plus the two names this action resolves itself: DateTime
      // is a pseudo-attribute turned into an overlap test below, and GroupId is
      // served by the Groups_Monitors join added further down. Anything else
      // would reach MySQL as an unknown column and come back as a 500.
      $valid_fields = array_keys($this->Event->schema());
      $valid_fields[] = 'DateTime';
      $valid_fields[] = 'GroupId';
      $named_params = $this->FilterComponent->qualifyFields($named_params, $this->filterColumns(), array('DateTime', 'GroupId'));
      $conditions = $this->FilterComponent->buildFilter($named_params, $valid_fields);
      #ZM\Debug(print_r($conditions, true));
    } else {
      $raw_params = $_REQUEST;
      // Legacy API authentication rides in the query string next to the filters.
      foreach (array('user', 'pass', 'username', 'password', 'auth', 'stateful') as $credential) {
        unset($raw_params[$credential]);
      }
      if (isset($raw_params['TagId'])) {
        $tag_filter_value = $raw_params['TagId'];
        $tag_filter_field = 'Id';
        unset($raw_params['TagId']);
      } else if (isset($raw_params['Tag'])) {
        $tag_filter_value = $raw_params['Tag'];
        $tag_filter_field = 'Name';
        unset($raw_params['Tag']);
      } else if (isset($raw_params['Tags'])) {
        $tag_filter_value = $raw_params['Tags'];
        $tag_filter_field = 'Name';
        unset($raw_params['Tags']);
      }
      $raw_params = $this->renameLegacyTimeParams($raw_params);
      $raw_params = $this->FilterComponent->qualifyFields($raw_params, $this->filterColumns(), array('DateTime', 'GroupId'));
      $conditions = $this->FilterComponent->buildFilter($raw_params, array_merge(array_keys($this->Event->schema()), array('DateTime', 'GroupId')));
    }
    $conditions = $this->datetimeConditions($conditions);
    $settings = array(
      // https://github.com/ZoneMinder/ZoneMinder/issues/995
      // 'limit' => $limit['ZM_WEB_EVENTS_PER_PAGE'],
      //  25 events per page which is what the above
      // default is, is way too low for an API
      // changing this to 100 so we don't kill ZM
      // with many event APIs. In future, we can
      // make a nice ZM_API_ITEMS_PER_PAGE for all pagination
      // API
      // ICON: 2023-11-16: MontageReview now uses API and unless we specifiy a limit in params, there should be no limit
      // TODO: Implement request based limits.

      'paramType' => 'querystring',
      'joins'=>[],
      'contain'=>[]
    );

    $settings['contain'] = [];
    # buildFilter() writes a condition as 'GroupId' <operator>, so a filter such
    # as "GroupId >:1" produces the key 'GroupId >' and an array value (IN, or a
    # bare array) is nested one level deeper. Look for the field in the keys
    # rather than for the bare key, or the join is skipped and MySQL reports an
    # unknown column.
    $references_group = function($conds) use (&$references_group) {
      foreach ($conds as $key => $value) {
        if (is_string($key) and preg_match('/^(Groups_Monitors\.)?GroupId(\s|$)/', $key)) {
          return true;
        }
        if (is_array($value) and $references_group($value)) {
          return true;
        }
      }
      return false;
    };
    if ($references_group($conditions)) {
      $settings['joins'] = array(
        array(
          'table' => 'Groups_Monitors',
          'type' => 'inner',
          'conditions' => array(
            'Groups_Monitors.MonitorId = Event.MonitorId'
          ),
        ),
      );
      $settings['contain'][] = 'Group';
    }
    if ($tag_filter_value !== null) {
      #$settings['contain'][] = 'Tag';
      if (!isset($settings['joins'])) {
        $settings['joins'] = array();
      }
      $settings['joins'][] = array(
        'table' => 'Events_Tags',
        'type' => 'inner',
        'conditions' => array(
          'Events_Tags.EventId = Event.Id'
        ),
      );
      $settings['joins'][] = array(
        'table' => 'Tags',
        'type' => 'inner',
        'conditions' => array(
          'Tags.Id = Events_Tags.TagId'
        ),
      );
      if ($tag_filter_field === 'Id') {
        $tag_ids = is_array($tag_filter_value) ? $tag_filter_value : explode(',', $tag_filter_value);
        $tag_ids = array_map('intval', $tag_ids);
        $conditions[] = array('Tags.Id' => $tag_ids);
      } else {
        $conditions[] = array('Tags.Name' => $tag_filter_value);
      }
      $settings['group'] = 'Event.Id';
    }
    if (isset($conditions['Tags.Id'])) {
      $settings['joins'][] = [
        'table' => 'Events_Tags',
        'type'  => 'inner',
        'conditions' => ['Events_Tags.EventId = Event.Id'],
      ];
      $settings['joins'][] = [
        'table' => 'Tags',
        'type'  => 'inner',
        'conditions' => ['Tags.Id = Events_Tags.TagId'],
      ];
      //$settings['contain'][] = 'Tag';
    }

    $settings['conditions'] = array($conditions, $mon_options);

    // Only paginate() reads sort and direction from the query string, so apply them here
    // for the unpaginated find() below as well.
    $sort = $this->request->query('sort');
    if (is_string($sort) and in_array($sort, array_keys($this->Event->schema()), true)) {
      $direction = $this->request->query('direction');
      $settings['order'] = array('Event.'.$sort => (is_string($direction) and strtolower($direction) == 'desc') ? 'desc' : 'asc');
    } else if (!$sort) {
      $settings['order'] = array('Event.Id' => 'asc');
    }

    $limit = $this->request->query('limit');
    if ($limit !== null) {
      if (!is_string($limit) || !preg_match('/^[0-9]+$/', $limit) || (int)$limit <= 0) {
        throw new BadRequestException('limit must be a positive integer');
      }
    }
    $page = $this->request->query('page');
    if ($page !== null) {
      if (!is_string($page) || !preg_match('/^[0-9]+$/', $page) || (int)$page <= 0) {
        throw new BadRequestException('page must be a positive integer');
      }
    }

    $this->Paginator->settings = $settings;
    if ($this->request->query('limit') or $this->request->query('page')) {
      $events = $this->Paginator->paginate('Event');
    } else {
      $events = $this->Event->find('all', $settings);
    }
    #ZM\Debug(print_r($this->Event->getDataSource()->getLog(false, false), true));
    // For each event, get the frameID which has the largest score also add FS path

    foreach ( $events as $key => $value ) {
      $EventObj = new ZM\Event($value['Event']);
      if ($EventObj->MaxScoreFrameId() == NULL) {
        $events[$key]['Event']['MaxScoreFrameId'] = $this->getMaxScoreAlarmFrameId($value['Event']['Id']);
      }
      $events[$key]['Event']['FileSystemPath'] = $EventObj->Path();
      if (isset($events[$key]['Monitor'])) {
        $events[$key]['Monitor'] = $this->hideRowCredentials($events[$key]['Monitor']);
      }
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
    $mon_options = $this->viewableMonitorCondition('Event.MonitorId');

    $noFrames = $this->request->query('noframes');
    if ($noFrames=='true')
      $this->Event->unbindModel(array('hasMany' => array('Frame')));

    $options = array('conditions' => array(array('Event.' . $this->Event->primaryKey => $id), $mon_options));
    $event = $this->Event->find('first', $options);
    # The event exists (checked above), so an empty result means the monitor
    # condition filtered it out. Refuse here: an empty ZM\Event has no monitor
    # and passes canView(), and the code below would then fill in the hidden
    # event's neighbours and frame ids by $id alone.
    if (!$event) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    $EventObj = new ZM\Event($event['Event']);
    if (!$EventObj->canView()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    # Get the previous and next events for any monitor the user may view.
    # Only Id is used below, so skip the wide SELECT + Monitor/Storage joins + Frames hasMany expansion
    # that recursive=1 from above would otherwise pull in for each neighbor row.
    $this->Event->id = $id;
    $event_neighbors = $this->Event->find('neighbors', array(
      'fields' => array('Event.Id'),
      'recursive' => -1,
      'conditions' => $mon_options,
    ));
    $event['Event']['Next'] = isset($event_neighbors['next']) ? $event_neighbors['next']['Event']['Id'] : 0;
    $event['Event']['Prev'] = isset($event_neighbors['prev']) ? $event_neighbors['prev']['Event']['Id'] : 0;

    $event['Event']['fileExists'] = $this->Event->fileExists($event['Event']);
    $event['Event']['fileSize'] = $this->Event->fileSize($event['Event']);
    $event['Event']['FileSystemPath'] = $EventObj->Path();

    # Also get the previous and next events for the same monitor
    $event_monitor_neighbors = $this->Event->find('neighbors', array(
      'fields' => array('Event.Id'),
      'recursive' => -1,
      'conditions' => array('Event.MonitorId' => $event['Event']['MonitorId']),
    ));
    $event['Event']['NextOfMonitor'] = isset($event_monitor_neighbors['next']) ? $event_monitor_neighbors['next']['Event']['Id'] : 0;
    $event['Event']['PrevOfMonitor'] = isset($event_monitor_neighbors['prev']) ? $event_monitor_neighbors['prev']['Event']['Id'] : 0;
  
    $this->loadModel('Frame');
    $maxScoreFrame = $this->Frame->findByEventid($id, 'FrameId', array('Score'=>'desc','FrameId'=>'asc'));

    $event['Event']['MaxScoreFrameId'] = $maxScoreFrame ? $maxScoreFrame['Frame']['FrameId'] : null;
    $alarmFrame = $this->Frame->findByEventidAndType($id, 'Alarm');
    ZM\Debug(print_r($alarmFrame, true));

    $event['Event']['AlarmFrameId'] = $alarmFrame ? $alarmFrame['Frame']['FrameId'] : null;
    if (isset($event['Monitor'])) {
      $event['Monitor'] = $this->hideRowCredentials($event['Monitor']);
    }

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
    $canEdit = (!$user) || ($user->canEdit('Events'));
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->request->is('post') ) {
      throw new BadRequestException(__('Invalid method. Should be post'));
    }
    if ( $this->request->is('post') ) {
      $monitorId = $this->requestField('Event', 'MonitorId');
      if ($monitorId === null) {
        throw new BadRequestException(__('MonitorId is required'));
      }
      $this->requireMonitorView($monitorId);
      $this->pinRequestId($this->Event, null);
      $this->Event->create();
      if ( $this->Event->save($this->request->data) ) {
        $message = 'Saved';
        $id = $this->Event->id;
      } else {
        $message = 'Error';
        $this->response->statusCode(400);
        $id = null;
        // if there is a validation message, use it
        if (!$this->Event->validates()) {
          $message = $this->Event->validationErrors;
        }
      }
      $this->set(array(
        'message' => $message,
        'id' => $id,
        '_serialize' => array('message', 'id')
      ));
    }
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
    $canEdit = (!$user) || ($user->canEdit('Events'));
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->Event->id = $id;

    if ( !$this->Event->exists($id) ) {
      throw new NotFoundException(__('Invalid event'));
    }

    # Events=Edit is coarse. Enforce the per-monitor ACL too, otherwise a user
    # denied a monitor can still mutate that monitor's events by direct Id.
    $this->Event->recursive = -1;
    $event = $this->Event->find('first', array(
      'conditions' => array('Event.' . $this->Event->primaryKey => $id)
    ));
    $EventObj = new ZM\Event($event['Event']);
    if ( !$EventObj->canEdit() ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    $this->request->allowMethod('post', 'put');
    $this->pinRequestId($this->Event, $id);
    $monitorId = $this->requestField('Event', 'MonitorId');
    if ($monitorId !== null and $monitorId != $event['Event']['MonitorId']) {
      $this->requireMonitorView($monitorId);
    }

    if ( $this->Event->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = $this->Event->validationErrors ? $this->Event->validationErrors : 'Error';
      $this->response->statusCode(400);
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
    $canEdit = (!$user) || ($user->canEdit('Events'));
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }
    $this->Event->id = $id;
    if ( !$this->Event->exists() ) {
      throw new NotFoundException(__('Invalid event'));
    }
    $this->request->allowMethod('post', 'delete');

    # Events=Edit is coarse. Enforce the per-monitor ACL too, otherwise a user
    # denied a monitor can still delete that monitor's events by direct Id.
    $this->Event->recursive = -1;
    $event = $this->Event->find('first', array(
      'conditions' => array('Event.' . $this->Event->primaryKey => $id)
    ));
    $EventObj = new ZM\Event($event['Event']);
    if ( !$EventObj->canEdit() ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    # ZM\Event::delete() refuses an archived event; say so instead of answering 500.
    if ( !empty($event['Event']['Archived']) ) {
      throw new BadRequestException(__('The event is archived and cannot be deleted. Unarchive it first.'));
    }

    # Not $this->Event->delete(): the model's beforeDelete() returns false, so that always fails.
    if ( $EventObj->delete() ) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
    } else {
      throw new InternalErrorException(__('The event could not be deleted.'));
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
          throw new BadRequestException('Invalid field name: ' . $param_name);
        }
        $matches = NULL;
        $value = preg_replace('/^\s?interval\s?/i', '', $value);
        if (preg_match('/^(?P<expr>[ \-.:0-9]+)\s+(?P<unit>[_a-z]+)$/i', trim($value), $matches) !== 1) {
          throw new BadRequestException('Invalid interval: ' . $value);
        }
        $find_conditions[] = "$param_name >= DATE_SUB(NOW(), ".$this->intervalSql($matches['expr'], $matches['unit']).')';
      } else {
        $conditions[$param_name] = $value;
      }
    }

    $this->FilterComponent = $this->Components->load('Filter');
    $conditions = $this->FilterComponent->buildFilter($conditions, array_keys($this->Event->schema()));
    array_push($conditions, $find_conditions);
    array_push($conditions, $this->viewableMonitorCondition('Event.MonitorId'));

    $results = $this->Event->find('all', array(
      'conditions' => $conditions
    ));

    $this->set(array(
      'results' => $results,
      '_serialize' => array('results')
    ));
  } // end public function search

  // The INTERVAL clause for an expression and unit taken from the URL. The unit has to be one
  // MySQL knows, and the expression is quoted: a compound unit such as DAY_HOUR only takes
  // a quoted '1 10', and an unknown unit used to come back as a 500 carrying the SQL error.
  private function intervalSql($expr, $unit) {
    $units = array('MICROSECOND', 'SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR',
      'SECOND_MICROSECOND', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'HOUR_MICROSECOND', 'HOUR_SECOND',
      'HOUR_MINUTE', 'DAY_MICROSECOND', 'DAY_SECOND', 'DAY_MINUTE', 'DAY_HOUR', 'YEAR_MONTH');
    $unit = strtoupper(trim($unit));
    if (!in_array($unit, $units, true)) {
      throw new BadRequestException('Invalid interval unit: ' . $unit);
    }
    return 'INTERVAL '.$this->Event->getDataSource()->value(trim($expr), 'string')." $unit";
  }

  // format expected:
  // you can changed AlarmFrames to any other named params
  // consoleEvents/1 hour/AlarmFrames >=: 1/AlarmFrames <=: 20.json

  public function consoleEvents($interval = null) {
    $matches = NULL;
    // https://dev.mysql.com/doc/refman/5.5/en/expressions.html#temporal-intervals
    // Examples: `'1-1' YEAR_MONTH`, `'-1 10' DAY_HOUR`, `'1.999999' SECOND_MICROSECOND`
    if (preg_match('/^(?P<expr>[ \-.:0-9]+)\s+(?P<unit>[_a-z]+)$/i', trim($interval), $matches) !== 1) {
      throw new BadRequestException('Invalid interval: ' . $interval);
    }
    $interval_sql = $this->intervalSql($matches['expr'], $matches['unit']);

    $this->Event->recursive = -1;
    $results = array();
    $this->FilterComponent = $this->Components->load('Filter');
    if ( $this->request->params['named'] ) {
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named'], array_keys($this->Event->schema()));
    } else {
      $conditions = array();
    } 
    array_push($conditions, array("StartDateTime >= DATE_SUB(NOW(), $interval_sql)"));
    array_push($conditions, $this->viewableMonitorCondition('Event.MonitorId'));
    $query = $this->Event->find('all', array(
      'fields' => array('MonitorId', 'COUNT(*) AS Count'),
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
  private function createThumbnail($id = null) {
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
    $thumbs = "ZM_WEB_{$bw}_SCALE_THUMBS";

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

    // Toggling Archived mutates state, so restrict to state-changing verbs (not CSRF-able GET).
    $this->request->allowMethod('post', 'put');

    $archived = $this->Event->find('first', array(
      'conditions' => array('Event.Id' => $id)
    ));
    $EventObj = new ZM\Event($archived['Event']);

    // If 0, 1, if 1, 0
    $archiveVal = (($archived['Event']['Archived'] == 0) ? 1 : 0);

    // Archiving protects an event from purge, so any user who can view the event may do it.
    // Un-archiving makes it eligible for purge again, so that requires edit permission.
    // Both canView() and canEdit() enforce the per-monitor object-level ACL.
    $allowed = $archiveVal ? $EventObj->canView() : $EventObj->canEdit();
    if ( !$allowed ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    // Save the new value
    $this->Event->id = $id;
    if (!$this->Event->saveField('Archived', $archiveVal)) {
      throw new InternalErrorException(__('Failed to update the event'));
    }

    $this->set(array(
      'archived' => $archiveVal,
      '_serialize' => array('archived')
    ));
  }

  // POST /api/events/archiveBulk with ids[] and archived=1 archives, or with
  // archived=0 unarchives, every listed event. Unlike archive() it sets the
  // state rather than toggling it, so a mixed selection ends up all one way.
  // Permissions follow archive(): archiving needs view, unarchiving needs edit.
  // Events the user may not change, that no longer exist, or whose save failed
  // are reported in refused and the remaining ids are still processed.
  public function archiveBulk() {
    $this->Event->recursive = -1;
    $this->request->allowMethod('post');

    if (isset($this->request->data['ids'])) {
      $ids = $this->request->data['ids'];
    } else if (isset($this->request->data['Event']['ids'])) {
      $ids = $this->request->data['Event']['ids'];
    } else {
      $ids = null;
    }
    if (!is_array($ids)) {
      throw new BadRequestException(__('ids must be an array'));
    }
    $archiveVal = isset($this->request->data['archived']) ? $this->request->data['archived'] : null;
    if (!is_scalar($archiveVal) or ($archiveVal !== '0' and $archiveVal !== '1' and $archiveVal !== 0 and $archiveVal !== 1)) {
      throw new BadRequestException(__('archived must be 0 or 1'));
    }
    $archiveVal = (int)$archiveVal;

    $changed = array();
    $refused = array();
    $seen = array();
    foreach ($ids as $id) {
      // A request element may itself be an array; it is not an event id, and
      // passing it on could forge a DB row instead of loading the real one.
      if (is_array($id) or !is_numeric($id)) {
        $refused[] = $id;
        continue;
      }
      $id = (int)$id;
      if (isset($seen[$id])) continue;
      $seen[$id] = true;

      if ($id <= 0 or !$this->Event->exists($id)) {
        $refused[] = $id;
        continue;
      }

      $archived = $this->Event->find('first', array(
        'conditions' => array('Event.Id' => $id)
      ));
      if (empty($archived['Event'])) {
        $refused[] = $id;
        continue;
      }

      $EventObj = new ZM\Event($archived['Event']);
      $allowed = $archiveVal ? $EventObj->canView() : $EventObj->canEdit();
      if (!$allowed) {
        $refused[] = $id;
        continue;
      }

      $this->Event->id = $id;
      if (!$this->Event->saveField('Archived', $archiveVal)) {
        $refused[] = $id;
        continue;
      }
      $changed[] = $id;
    }

    $this->set(array(
      'changed' => $changed,
      'refused' => $refused,
      '_serialize' => array('changed', 'refused')
    ));
  }

  // POST /api/events/video/<id>.json renders the event to a video file with
  // zmvideo.pl, as the event view's Video page does (ajax/event.php action=video),
  // and answers the file name. Viewing the event is enough, as on that page.
  public function video($id = null) {
    $this->request->allowMethod('post');
    $this->Event->recursive = -1;
    if (!$this->Event->exists($id)) {
      throw new NotFoundException(__('Invalid event'));
    }
    if (!(defined('ZM_OPT_FFMPEG') and ZM_OPT_FFMPEG)) {
      throw new BadRequestException(__('Video generation is turned off (ZM_OPT_FFMPEG)'));
    }

    $data = $this->request->data;
    $param = function($name) use ($data) {
      return (isset($data[$name]) and is_scalar($data[$name])) ? trim((string)$data[$name]) : '';
    };
    $format = $param('videoFormat');
    $rate = $param('rate');
    $scale = $param('scale');
    $transform = $param('transform');

    // The formats ZM_FFMPEG_FORMATS offers, without their default markers.
    $formats = array();
    foreach (preg_split('/\s+/', defined('ZM_FFMPEG_FORMATS') ? ZM_FFMPEG_FORMATS : '', -1, PREG_SPLIT_NO_EMPTY) as $f) {
      $formats[] = rtrim($f, '*');
    }
    if (!in_array($format, $formats, true)) {
      throw new BadRequestException(__('videoFormat must be one of: ').implode(', ', $formats));
    }
    if (!preg_match('/^\d+$/', $rate) or !(int)$rate) {
      throw new BadRequestException(__('rate must be a positive whole number'));
    }
    if (!preg_match('/^(\d+|\d+x\d+)$/', $scale) or !(int)$scale) {
      throw new BadRequestException(__('scale must be a positive whole number or WxH'));
    }
    if (!preg_match('/^[\w=]*$/', $transform)) {
      throw new BadRequestException(__('Invalid transform'));
    }

    $event = $this->Event->find('first', array('conditions' => array('Event.Id' => $id)));
    $EventObj = new ZM\Event($event['Event']);
    // canView() enforces the per-monitor ACL.
    if (!$EventObj->canView()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }

    $file = $EventObj->createVideo($format, $rate, $scale, $transform, !empty($data['overwrite']));
    if (!$file) {
      throw new InternalErrorException(__('Video generation failed'));
    }
    $this->set(array(
      'video' => $file,
      '_serialize' => array('video')
    ));
  }

  private function getMaxScoreAlarmFrameId($id = null) {
    $this->loadModel('Frame');
    $maxScoreFrame = $this->Frame->findByEventid($id, 'FrameId', array('Score'=>'desc','FrameId'=>'asc'));
    return $maxScoreFrame ? $maxScoreFrame['Frame']['FrameId'] : null;
  }
} // end class EventsController
