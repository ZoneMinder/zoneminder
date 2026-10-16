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
  public $components = array( 'Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();

    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user->System() != 'None');
    if (!$canView and $this->isSelfEdit()) {
      # view() and edit() check what a user may see and change about themselves
      $canView = true;
    }
    if (!$canView) {
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
		$this->User->recursive = 0;

    global $user;
    # We should actually be able to list our own user, but I'm not bothering at this time.
    if ($user->System() == 'None' ) {
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
    # We can view ourselves when ZM_USER_SELF_EDIT allows us to edit ourselves
    $canView = ($user->System() != 'None') || (ZM_USER_SELF_EDIT && ($user->Id() == $id));
    if (!$canView) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if (!$this->User->exists($id)) {
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
		if ($this->request->is('post')) {
      global $user;
      if ($user->System() != 'Edit') {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

      $this->User->create();
      if ($this->User->save($this->request->data)) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if (!$this->User->validates()) {
          $message = $this->User->validationErrors;
        }
      }
    } else {
      $message = 'Add without post data';
    }
    $this->set(array(
      'user'        => $this->User,
      'message'     => $message,
      '_serialize'  => array('message')
    ));
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
    $selfEdit = ($user->System() != 'Edit');
    $canEdit = ($user->System() == 'Edit') || (ZM_USER_SELF_EDIT && ($user->Id() == $id));
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
      $data = $this->request->data;
      $fieldList = array();
      if ($selfEdit) {
        # Same fields the web UI lets a user change about themselves (includes/actions/user.php)
        $data = $this->selfEditData($data);
        $fieldList = array_keys($data['User']);
      }
			if ($selfEdit and !count($fieldList)) {
				$message = 'Error';
			} else if ( $this->User->save($data, true, $fieldList) ) {
				$message = 'Saved';
			} else {
				$message = 'Error';
        if (!$this->User->validates()) {
          $message = $this->User->validationErrors;
        }
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
    if ( ($user->System() != 'Edit') or ($user->Id() == $id) ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
		if ( !$this->User->exists() ) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->User->delete()) {
			$message = 'The user has been deleted.';
		} else {
			$message = 'The user could not be deleted. Please, try again.';
		}
		$this->set(array(
			'message' => $message,
			'_serialize' => array('message')
		));
	}

  # True for view or edit of the logged in user's own record while ZM_USER_SELF_EDIT is on
  private function isSelfEdit() {
    global $user;
    if (!$user or !ZM_USER_SELF_EDIT) return false;
    if (!in_array($this->request->params['action'], array('view', 'edit'))) return false;
    $pass = $this->request->params['pass'];
    return isset($pass[0]) && ($pass[0] == $user->Id());
  }

  # Keep only Password, Language and HomeView from the request, as the web UI does for self edit
  private function selfEditData($data) {
    $in = (isset($data['User']) and is_array($data['User'])) ? $data['User'] : $data;
    if (!is_array($in)) $in = array();
    $fields = array_intersect_key($in, array('Password'=>'', 'Language'=>'', 'HomeView'=>''));
    if (empty($fields['Password'])) unset($fields['Password']);
    if (!empty($fields['Language']) and
      (!is_string($fields['Language']) or !file_exists(ZM_PATH_WEB.'/lang/'.$fields['Language'].'.php'))) {
      throw new BadRequestException(__('Invalid Language'));
    }
    return array('User' => $fields);
  }
}  # end class UsersController
