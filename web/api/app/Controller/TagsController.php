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
    $canView = (!$user) || ($user->Events() != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
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
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    ZM\Debug(print_r($conditions, true));
    $find_array = array(
      'conditions' => &$conditions,
      'joins' => [],
      'contain'=> [],
    );

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
      global $user;
      $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : array();
      if ( count($allowedMonitors) ) {
        $conditions[] = array('Events.MonitorId' => $allowedMonitors);
      }
    }

		$tags = $this->Tag->find('all', $find_array);
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
		if ( $this->request->is('post') ) {

      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Tags() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

			$this->Tag->create();

      if ( $this->request->data['Tag']['EventIds'] and ! isset($this->request->data['Event']) ) {
        $this->request->data['Event'] = explode(',', $this->request->data['Tag']['EventIds']);
        unset($this->request->data['Tag']['EventIds']);
      }
      if ( $this->Tag->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        return $this->flash(
          __('The tag has been saved.'),
          array('action' => 'index')
        );
      } else {
        ZM\Error("Failed to save Tag");
        debug($this->Tag->invalidFields());
      }
    } # end if post
    $monitors = $this->Tag->Event->find('list');
		$this->set(compact('monitors'));
	} # end add

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
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Tags() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->Tag->id = $id;
			if ( $this->Tag->save($this->request->data) ) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if ( !$this->tag->validates() ) {
          $message .= ': '.$this->Tag->validationErrors;
        }
			}
		} # end if post/put

		$tag = $this->Tag->findById($id);
		$this->set(array(
			'message' => $message,
			'tag' => $tag,
			'_serialize' => array('tag')
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
    $canEdit = (!$user) || ($user->Tags() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if ( $this->Tag->delete() ) {
      return $this->flash(
        __('The tag has been deleted.'),
        array('action' => 'index')
      );
		} else {
      return $this->flash(
        __('The tag could not be deleted. Please, try again.'),
        array('action' => 'index')
      );
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
    $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : array();
    $event_contain = array('fields'=>array('Id','Name'));
    if ( count($allowedMonitors) ) {
      $event_contain['conditions'] = array('Event.MonitorId' => $allowedMonitors);
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
