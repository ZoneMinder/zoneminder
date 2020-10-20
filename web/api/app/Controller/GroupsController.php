<?php
App::uses('AppController', 'Controller');
/**
 * Groups Controller
 *
 * @property Group $Group
 * @property PaginatorComponent $Paginator
 */
class GroupsController extends AppController {
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
    $canView = (!$user) || ($user['Groups'] != 'None');
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
		$this->Group->recursive = -1;
		$groups = $this->Group->find('all');
		$this->set(array(
			'groups' => $groups,
			'_serialize' => array('groups')
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
		$this->Group->recursive = -1;
		if (!$this->Group->exists($id)) {
			throw new NotFoundException(__('Invalid group'));
		}
		$options = array('conditions' => array('Group.' . $this->Group->primaryKey => $id));
		$group = $this->Group->find('first', $options);
		$this->set(array(
			'group' => $group,
			'_serialize' => array('group')
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
      $canEdit = (!$user) || ($user['Groups'] == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

			$this->Group->create();

      if ( $this->request->data['Group']['MonitorIds'] and ! isset($this->request->data['Monitor']) ) {
        $this->request->data['Monitor'] = explode(',', $this->request->data['Group']['MonitorIds']);
        unset($this->request->data['Group']['MonitorIds']);
      }
      if ( $this->Group->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        return $this->flash(
          __('The group has been saved.'),
          array('action' => 'index')
        );
      } else {
        ZM\Error("Failed to save Group");
        debug($this->Group->invalidFields());
      }
    } # end if post
    $monitors = $this->Group->Monitor->find('list');
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
		if ( !$this->Group->exists($id) ) {
			throw new NotFoundException(__('Invalid group'));
		}
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user['Groups'] == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
			if ( $this->Group->save($this->request->data) ) {
        return $this->flash(
          __('The group has been saved.'),
          array('action' => 'index')
        );
      } else {
        $message = 'Error';
			}
		} else {
			$options = array('conditions' => array('Group.' . $this->Group->primaryKey => $id));
			$this->request->data = $this->Group->find('first', $options);
		}
		$monitors = $this->Group->Monitor->find('list');
		$this->set(array(
			'message' => $message,
      'monitors'=> $monitors,
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
		$this->Group->id = $id;
		if ( !$this->Group->exists() ) {
			throw new NotFoundException(__('Invalid group'));
		}
		$this->request->allowMethod('post', 'delete');

    global $user;
    # We already tested for auth in appController,
    # so we just need to test for specific permission
    $canEdit = (!$user) || ($user['Groups'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if ( $this->Group->delete() ) {
      return $this->flash(
        __('The group has been deleted.'),
        array('action' => 'index')
      );
		} else {
      return $this->flash(
        __('The group could not be deleted. Please, try again.'),
        array('action' => 'index')
      );
		}
  } // end function delete
  
  // returns monitor associations
  public function associations() {
    $this->Group->recursive = -1;
    $groups = $this->Group->find('all', array(
                                        'contain'=> array(
                                          'Monitor' => array(
                                            'fields'=>array('Id','Name')
                                          )
                                        )
                                      )
                                );
            $this->set(array(
                    'groups' => $groups,
                    '_serialize' => array('groups')
            ));
  } // end associations

} // end class GroupController
