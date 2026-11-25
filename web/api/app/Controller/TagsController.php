<?php
App::uses('AppController', 'Controller');
/**
 * Tags Controller
 *
 * @property Tag $Tag
 * @property PaginatorComponent $Paginator
 */
class TagsController extends AppController {
/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user->canView('Events'));
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

/**
 * Adds to each tag the number of its events on monitors the caller may view.
 *
 * @param array $tags          Tag rows from the tag find.
 * @param array $visibleCounts Tag Id => events on monitors the caller may view.
 * @return array
 */
	protected function tagEventCounts(array $tags, array $visibleCounts) {
		foreach ( $tags as &$tag ) {
			$tagId = $tag['Tag']['Id'];
			$tag['Tag']['Events'] = isset($visibleCounts[$tagId]) ? (int)$visibleCounts[$tagId] : 0;
		}
		return $tags;
	}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Tag->recursive = -1;

    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      # Name and Id are also Events columns, and the Events.Id filter joins Events, so
      # a bare Name:x would be ambiguous. Events.Id stays as the caller wrote it.
      $named = array();
      foreach ($this->request->params['named'] as $attribute => $value) {
        $named[(!in_array(trim($attribute), $this->FilterComponent->paramsToFilterOut) and strpos($attribute, '.') === false) ? 'Tag.'.$attribute : $attribute] = $value;
      }
      $conditions = $this->FilterComponent->buildFilter($named, array_keys($this->Tag->schema()));
    } else {
      $conditions = array();
    }

    ZM\Debug(print_r($conditions, true));
    $find_array = array(
      'conditions' => &$conditions,
      'joins' => [],
      'contain'=> [],
    );

    # Each tag's event count, from one grouped query, counting only events on
    # monitors the caller may view. The tag list itself stays unrestricted.
    global $user;
    $monitorCondition = $this->viewableMonitorCondition('Events.MonitorId');
    $count_rows = $this->Tag->find('all', array(
      'recursive' => -1,
      'fields' => array('Tag.Id', 'COUNT(Events.Id) AS VisibleEvents'),
      'joins' => array(
        array(
          'table' => 'Events_Tags',
          'alias' => 'Events_Tags',
          'type'  => 'inner',
          'conditions' => array('Events_Tags.TagId = Tag.Id'),
        ),
        array(
          'table' => 'Events',
          'alias' => 'Events',
          'type'  => 'inner',
          'conditions' => array_merge(array('Events.Id = Events_Tags.EventId'), $monitorCondition),
        ),
      ),
      'group' => array('Tag.Id'),
    ));
    $visibleCounts = array();
    foreach ( $count_rows as $row ) {
      $visibleCounts[$row['Tag']['Id']] = (int)$row[0]['VisibleEvents'];
    }

    if (isset($conditions['Events.Id'])) {
      // Support comma-separated Event IDs for querying tags across multiple events
      $event_id_value = $conditions['Events.Id'];
      if (!is_array($event_id_value) && strpos($event_id_value, ',') !== false) {
        $conditions['Events.Id'] = array_map('intval', explode(',', $event_id_value));
      }

      // Include EventId in the results
      $find_array['fields'] = ['Tag.*', 'Events_Tags.EventId'];

      $find_array['joins'][]   = [
          'table' => 'Events_Tags',
          'type'  => 'inner',
          'conditions' => ['Events_Tags.TagId = Tag.Id'],
      ];
      $find_array['joins'][]   = [
          'table' => 'Events',
          'type'  => 'inner',
          'conditions' => ['Events.Id = Events_Tags.EventId'],
        ];

      # The join to Events exposes the tag/event association, which is
      # per-monitor data. Restrict it to monitors the caller may view so a
      # monitor-restricted user cannot confirm which tags attach to events on
      # cameras they are denied. The plain tag list (no Events.Id filter) is a
      # global label vocabulary and is intentionally left unrestricted.
      if ( count($monitorCondition) ) {
        $conditions[] = $monitorCondition;
      }
    }

		$tags = $this->Tag->find('all', $find_array);
		$tags = $this->tagEventCounts($tags, $visibleCounts);
		$this->set(array(
			'tags' => $tags,
			'_serialize' => array('tags')
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
		$this->Tag->recursive = -1;
		if (!$this->Tag->exists($id)) {
			throw new NotFoundException(__('Invalid tag'));
		}
		$options = array('conditions' => array('Tag.' . $this->Tag->primaryKey => $id));
		$tag = $this->Tag->find('first', $options);
		$this->set(array(
			'tag' => $tag,
			'_serialize' => array('tag')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ( !$this->request->is('post') ) {
			throw new BadRequestException(__('Invalid method. Should be post'));
		}
		if ( $this->request->is('post') ) {

      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->canEdit('Events'));
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

      $eventIds = $this->requestField('Tag', 'EventIds');
      if ( is_string($eventIds) and $eventIds !== '' and ! isset($this->request->data['Event']) ) {
        $this->request->data['Event'] = explode(',', $eventIds);
        unset($this->request->data['EventIds']);
        if ( isset($this->request->data['Tag']) and is_array($this->request->data['Tag']) ) unset($this->request->data['Tag']['EventIds']);
      }
      # Only events the user may view can be tagged.
      $eventIds = $this->requestAssociatedIds('Tag', 'Event');
      if ($eventIds) $this->requireEventsView($eventIds);
      $this->pinRequestId($this->Tag, null);
			$this->Tag->create();

      if ( $this->Tag->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        $this->set(array(
          'message' => 'Saved',
          'id' => $this->Tag->id,
          '_serialize' => array('message', 'id')
        ));
        return;
      } else {
        ZM\Error("Failed to save Tag");
        $this->set(array(
          'message' => $this->Tag->validationErrors ? $this->Tag->validationErrors : 'Error',
          'id' => null,
          '_serialize' => array('message', 'id')
        ));
        return;
      }
    } # end if post
	} # end add

  /**
   * assign method
   *
   * Adds one tag to each event in EventIds, as web/ajax/event.php addtag does:
   * INSERT IGNORE keeps a duplicate harmless, and the tag's LastAssignedDate
   * records the assignment.
   *
   * @return void
   */
  public function assign() {
    $this->tagEventLinks('insert');
  }

  /**
   * unassign method
   *
   * Removes one tag from each event in EventIds, as web/ajax/event.php
   * removetag does.
   *
   * @return void
   */
  public function unassign() {
    $this->tagEventLinks('delete');
  }

  # The tag/event change behind assign() and unassign(). Events=Edit is per
  # area, so requireEventsEdit() also enforces the per-monitor ACL on each
  # event, as the ajax handler does.
  private function tagEventLinks($action) {
    if ( !$this->request->is('post') ) {
      throw new BadRequestException(__('Invalid method. Should be post'));
    }

    global $user;
    $canEdit = (!$user) || ($user->canEdit('Events'));
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    $tagId = $this->requestField('Tag', 'TagId');
    if ( !is_numeric($tagId) or intval($tagId) <= 0 ) {
      throw new BadRequestException(__('TagId is required'));
    }
    $tagId = intval($tagId);

    $eventIds = $this->requestField('Tag', 'EventIds');
    if ( is_string($eventIds) ) $eventIds = explode(',', $eventIds);
    if ( !is_array($eventIds) or !$eventIds ) {
      throw new BadRequestException(__('EventIds is required'));
    }
    $ids = array();
    foreach ( $eventIds as $eventId ) {
      if ( !is_numeric($eventId) or intval($eventId) <= 0 ) {
        throw new BadRequestException(__('EventIds must be event ids'));
      }
      $ids[] = intval($eventId);
    }
    $ids = array_values(array_unique($ids));

    $this->requireEventsEdit($ids);

    if ( $action == 'insert' ) {
      if ( !dbFetchOne('SELECT Id FROM Tags WHERE Id = ?', 'Id', array($tagId)) ) {
        throw new NotFoundException(__('Invalid tag'));
      }
      $sql = 'INSERT IGNORE INTO Events_Tags (TagId, EventId, AssignedBy) VALUES (?, ?, ?)';
      foreach ( $ids as $eventId ) {
        if ( !dbQuery($sql, array($tagId, $eventId, $user ? $user->Id() : null)) ) {
          throw new InternalErrorException(__('Failed to add tag'));
        }
      }
      if ( !dbQuery('UPDATE Tags SET LastAssignedDate = NOW() WHERE Id = ?', array($tagId)) ) {
        throw new InternalErrorException(__('Failed to add tag'));
      }
      $message = 'Saved';
    } else {
      foreach ( $ids as $eventId ) {
        if ( !dbQuery('DELETE FROM Events_Tags WHERE TagId = ? AND EventId = ?', array($tagId, $eventId)) ) {
          throw new InternalErrorException(__('Failed to remove tag'));
        }
      }
      $message = 'Deleted';
    }

    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));
  } # end tagEventLinks

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit( $id = null ) {
		if ( !$this->Tag->exists($id) ) {
			throw new NotFoundException(__('Invalid tag'));
		}
		$this->request->allowMethod('post', 'put');
    $message = '';
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->canEdit('Events'));
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->pinRequestId($this->Tag, $id);
      $eventIds = $this->requestAssociatedIds('Tag', 'Event');
      if ($eventIds !== null) {
        $this->request->data['Event'] = array('Event' => $this->eventLinksToSave('Events_Tags', 'TagId', $id, $eventIds));
      }
			if ( $this->Tag->save($this->request->data) ) {
        $message = 'Saved';
      } else {
        $message = $this->Tag->validationErrors ? $this->Tag->validationErrors : 'Error';
			}
		} # end if post/put

		$tag = $this->Tag->findById($id);
		$this->set(array(
			'message' => $message,
			'tag' => $tag,
			'_serialize' => array('message', 'tag')
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
		$this->Tag->id = $id;
		if ( !$this->Tag->exists() ) {
			throw new NotFoundException(__('Invalid tag'));
		}
		$this->request->allowMethod('post', 'delete');

    global $user;
    # We already tested for auth in appController,
    # so we just need to test for specific permission
    $canEdit = (!$user) || ($user->canEdit('Events'));
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    # Events_Tags has no Id column, so Cake cannot clear the links itself.
    $this->Tag->unbindModel(array('hasAndBelongsToMany' => array('Event')), false);
		if ( $this->Tag->delete() ) {
      dbQuery('DELETE FROM `Events_Tags` WHERE `TagId`=?', array($id));
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
		} else {
      throw new InternalErrorException(__('The tag could not be deleted.'));
		}
  } // end function delete
  
  // returns monitor associations
  public function associations() {
    $this->Tag->recursive = -1;

    # Each tag is returned with its associated Events, which is per-monitor
    # data. Restrict the contained events to monitors the caller may view so a
    # monitor-restricted user does not learn which events on denied cameras
    # carry a given tag.
    global $user;
    $event_contain = array('fields'=>array('Id','Name'));
    $monitorCondition = $this->viewableMonitorCondition('Event.MonitorId');
    if ( count($monitorCondition) ) {
      $event_contain['conditions'] = $monitorCondition;
    }

    $tags = $this->Tag->find('all', array(
                                        'contain'=> array(
                                          'Event' => $event_contain
                                        )
                                      )
                                );
            $this->set(array(
                    'tags' => $tags,
                    '_serialize' => array('tags')
            ));
  } // end associations

} // end class TagController
