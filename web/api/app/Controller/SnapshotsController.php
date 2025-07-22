<?php
App::uses('AppController', 'Controller');
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
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    $find_array = array(
      'conditions' => &$conditions,
      'contain'    => array('Event'),
      'joins'      => array(
        array(
          'table' => 'Snapshots_Events',
          'type'  => 'left',
          'conditions' => array(
            'Snapshots_Events.SnapshotId = Snapshot.Id',
          ),
        ),
      ),
      'snapshot' => '`Snapshot`.`Id`',
    );

		$snapshots = $this->Snapshot->find('all', $find_array);
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
		$snapshot = $this->Snapshot->find('first', $options);
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
		if ( $this->request->is('post') ) {

      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Snapshots() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

			$this->Snapshot->create();

      if ( $this->request->data['Snapshot']['EventIds'] and ! isset($this->request->data['Event']) ) {
        $this->request->data['Event'] = explode(',', $this->request->data['Snapshot']['EventIds']);
        unset($this->request->data['Snapshot']['EventIds']);
      }
      if ( $this->Snapshot->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        return $this->flash(
          __('The snapshot has been saved.'),
          array('action' => 'index')
        );
      } else {
        ZM\Error("Failed to save Snapshot");
        debug($this->Snapshot->invalidFields());
      }
    } # end if post
    $monitors = $this->Snapshot->Event->find('list');
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
		if ( !$this->Snapshot->exists($id) ) {
			throw new NotFoundException(__('Invalid snapshot'));
		}
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Snapshots() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->Snapshot->id = $id;
			if ( $this->Snapshot->save($this->request->data) ) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if ( !$this->snapshot->validates() ) {
          $message .= ': '.$this->Snapshot->validationErrors;
        }
			}
		} # end if post/put

		$snapshot = $this->Snapshot->findById($id);
		$this->set(array(
			'message' => $message,
			'snapshot' => $snapshot,
			'_serialize' => array('snapshot')
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
      return $this->flash(
        __('The snapshot has been deleted.'),
        array('action' => 'index')
      );
		} else {
      return $this->flash(
        __('The snapshot could not be deleted. Please, try again.'),
        array('action' => 'index')
      );
		}
  } // end function delete
  
  // returns event associations
  public function associations() {
    $this->Snapshot->recursive = -1;
    $snapshots = $this->Snapshot->find('all', array(
                                        'contain'=> array(
                                          'Event' => array(
                                            'fields'=>array('Id','Name')
                                          )
                                        )
                                      )
                                );
            $this->set(array(
                    'snapshots' => $snapshots,
                    '_serialize' => array('snapshots')
            ));
  } // end associations

} // end class SnapshotController
