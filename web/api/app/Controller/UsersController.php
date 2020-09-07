<?php
App::uses('AppController', 'Controller');
/**
 * Users Controller
 *
 * @property User $User
 */
class UsersController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler', 'Paginator');

  public function beforeFilter() {
    parent::beforeFilter();

    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user['System'] != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

/**
 * index method
 *
 * @return void
 * This also creates a thumbnail for each user.
 */
	public function index() {
		$this->User->recursive = 0;

    global $user;
    # We should actually be able to list our own user, but I'm not bothering at this time.
    if ( $user['System'] == 'None' ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
		$users = $this->Paginator->paginate('User');

		$this->set(compact('users'));
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->User->recursive = 1;

    global $user;
    # We can view ourselves
    $canView = ($user['System'] != 'None') or ($user['Id'] == $id);
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if ( !$this->User->exists($id) ) {
			throw new NotFoundException(__('Invalid user'));
		}

		$options = array('conditions' => array('User.' . $this->User->primaryKey => $id));
		$user = $this->User->find('first', $options);
		
		$this->set(array(
			'user' => $user,
			'_serialize' => array('user')
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
      if ( $user['System'] != 'Edit' ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

			$this->User->create();
			if ( $this->User->save($this->request->data) ) {
				return $this->flash(__('The user has been saved.'), array('action' => 'index'));
			}
			$this->Session->setFlash(
					__('The user could not be saved. Please, try again.')
					);
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
		$this->User->id = $id;

    global $user;
    $canEdit = ($user['System'] == 'Edit') or (($user['Id'] == $id) and ZM_USER_SELF_EDIT);
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if ( !$this->User->exists($id) ) {
			throw new NotFoundException(__('Invalid user'));
		}

		if ( $this->request->is('post') || $this->request->is('put') ) {
			if ( $this->User->save($this->request->data) ) {
				$message = 'Saved';
			} else {
				$message = 'Error';
			}
		} else {
      # What is this doing? Resetting the request data? I understand clearing the password field
      # but generally I feel like the request data should be read only
      $this->request->data = $this->User->read(null, $id);
      unset($this->request->data['User']['Password']);
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
		$this->User->id = $id;

    global $user;
    # Can't delete ourselves
    if ( ($user['System'] != 'Edit') or ($user['Id'] == $id) ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
		if ( !$this->User->exists() ) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->request->allowMethod('post', 'delete');
		if ( $this->User->delete() ) {
			$message = 'The user has been deleted.';
		} else {
			$message = 'The user could not be deleted. Please, try again.';
		}
		$this->set(array(
			'message' => $message,
			'_serialize' => array('message')
		));
	}
}  # end class UsersController
