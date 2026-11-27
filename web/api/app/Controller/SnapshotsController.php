<?php
App::uses('AppController', 'Controller');
require_once __DIR__ . '/../../../includes/SnapshotCreate.php';
/**
 * Snapshots Controller
 *
 * @property Snapshot $Snapshot
 * @property PaginatorComponent $Paginator
 */
class SnapshotsController extends AppController {
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
    $canView = (!$user) || ($user->Snapshots() != 'None');
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
		$this->Snapshot->recursive = 1;

    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named'], array_keys($this->Snapshot->schema()));
    } else {
      $conditions = array();
    }

    $find_array = array(
      'conditions' => &$conditions,
      'contain'    => array('Event'),
    );

		$snapshots = array_map(array($this, 'filterContainedEvents'), $this->Snapshot->find('all', $find_array));
		$this->set(array(
			'snapshots' => $snapshots,
			'_serialize' => array('snapshots')
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
		$this->Snapshot->recursive = 1;
		if (!$this->Snapshot->exists($id)) {
			throw new NotFoundException(__('Invalid snapshot'));
		}
		$options = array('conditions' => array('Snapshot.' . $this->Snapshot->primaryKey => $id));
		$snapshot = $this->filterContainedEvents($this->Snapshot->find('first', $options));
		$this->set(array(
			'snapshot' => $snapshot,
			'_serialize' => array('snapshot')
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
      $canEdit = (!$user) || ($user->Snapshots() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

      # MonitorIds takes the same path as the web action: force an alarm on each
      # monitor, link the events it records and archive them.
      $monitorIds = $this->requestField('Snapshot', 'MonitorIds');
      if ( $monitorIds !== null ) {
        if ( is_string($monitorIds) ) {
          $monitorIds = trim($monitorIds) === '' ? array() : explode(',', $monitorIds);
        } else if ( !is_array($monitorIds) ) {
          $monitorIds = array($monitorIds);
        }
        $snapshotId = ZM\create_snapshot_from_monitors($monitorIds);
        if ( $snapshotId ) {
          $this->set(array(
            'message' => 'Saved',
            'id' => $snapshotId,
            '_serialize' => array('message', 'id')
          ));
        } else {
          ZM\Error('Failed to create Snapshot from monitors');
          $this->set(array(
            'message' => 'Error',
            'id' => null,
            '_serialize' => array('message', 'id')
          ));
        }
        return;
      }

      $eventIds = $this->requestField('Snapshot', 'EventIds');
      if ( is_string($eventIds) and $eventIds !== '' and ! isset($this->request->data['Event']) ) {
        $this->request->data['Event'] = explode(',', $eventIds);
        unset($this->request->data['EventIds']);
        if ( isset($this->request->data['Snapshot']) and is_array($this->request->data['Snapshot']) ) unset($this->request->data['Snapshot']['EventIds']);
      }
      # A snapshot exposes its events to Snapshots viewers, so only viewable events may be added.
      $eventIds = $this->requestAssociatedIds('Snapshot', 'Event');
      if ($eventIds) $this->requireEventsView($eventIds);
      $this->pinRequestId($this->Snapshot, null);
			$this->Snapshot->create();

      if ( $this->Snapshot->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        $this->set(array(
          'message' => 'Saved',
          'id' => $this->Snapshot->id,
          '_serialize' => array('message', 'id')
        ));
        return;
      } else {
        ZM\Error("Failed to save Snapshot");
        $this->set(array(
          'message' => $this->Snapshot->validationErrors ? $this->Snapshot->validationErrors : 'Error',
          'id' => null,
          '_serialize' => array('message', 'id')
        ));
        return;
      }
    } # end if post
	} # end add

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit( $id = null ) {
		if ( !$this->Snapshot->exists($id) ) {
			throw new NotFoundException(__('Invalid snapshot'));
		}
		$this->request->allowMethod('post', 'put');
    $message = '';
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Snapshots() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->pinRequestId($this->Snapshot, $id);
      $eventIds = $this->requestAssociatedIds('Snapshot', 'Event');
      if ($eventIds !== null) {
        $this->request->data['Event'] = array('Event' => $this->eventLinksToSave('Snapshots_Events', 'SnapshotId', $id, $eventIds));
      }
			if ( $this->Snapshot->save($this->request->data) ) {
        $message = 'Saved';
      } else {
        $message = $this->Snapshot->validationErrors ? $this->Snapshot->validationErrors : 'Error';
			}
		} # end if post/put

		$snapshot = $this->Snapshot->findById($id);
		$this->set(array(
			'message' => $message,
			'snapshot' => $snapshot,
			'_serialize' => array('message', 'snapshot')
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
		$this->Snapshot->id = $id;
		if ( !$this->Snapshot->exists() ) {
			throw new NotFoundException(__('Invalid snapshot'));
		}
		$this->request->allowMethod('post', 'delete');

    global $user;
    # We already tested for auth in appController,
    # so we just need to test for specific permission
    $canEdit = (!$user) || ($user->Snapshots() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if ( $this->Snapshot->delete() ) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
		} else {
      throw new InternalErrorException(__('The snapshot could not be deleted.'));
		}
  } // end function delete
  
  // returns event associations
  public function associations() {
    $this->Snapshot->recursive = -1;
    $snapshots = $this->Snapshot->find('all', array(
                                        'contain'=> array(
                                          'Event' => array(
                                            'fields'=>array('Id','Name','MonitorId')
                                          )
                                        )
                                      )
                                );
            $snapshots = array_map(array($this, 'filterContainedEvents'), $snapshots);
            $this->set(array(
                    'snapshots' => $snapshots,
                    '_serialize' => array('snapshots')
            ));
  } // end associations

} // end class SnapshotController
