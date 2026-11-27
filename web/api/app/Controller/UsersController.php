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
    if ($user and $user->System() == 'None' ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    $this->requireScalarSort();
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
    $canView = (!$user) || ($user->System() != 'None') || (ZM_USER_SELF_EDIT && ($user->Id() == $id));
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
      if ($user and $user->System() != 'Edit') {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

      // save() takes the primary key from the data, so an Id in the body would
      // update that user instead of creating one.
      $this->pinRequestId($this->User, null);
      $this->User->create();
      $id = null;
      if ($this->User->save($this->request->data)) {
        $message = 'Saved';
        $id = $this->User->id;
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if (!$this->User->validates()) {
          $message = $this->User->validationErrors;
        }
        $this->response->statusCode(400);
      }
    } else {
      throw new BadRequestException(__('POST required'));
    }
    $this->set(array(
      'message'     => $message,
      'id'          => $id,
      '_serialize'  => array('message', 'id')
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
    $selfEdit = ($user and $user->System() != 'Edit');
    $canEdit = (!$user) || ($user->System() == 'Edit') || (ZM_USER_SELF_EDIT && ($user->Id() == $id));
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}

    $this->request->allowMethod('post', 'put');
    $message = 'Error';
    $data = $this->request->data;
    $fieldList = array();
    if ($selfEdit) {
      # Same fields the web UI lets a user change about themselves (includes/actions/user.php)
      $data = $this->selfEditData($data);
      $fieldList = array_keys($data['User']);
      # A self-service password change must also persist TokenMinExpiry, which
      # the User model sets in beforeSave to revoke the user's existing API
      # tokens; it is not a field the user submitted, so the self-edit
      # whitelist has to admit it (refs GHSA-5w6w-qp25-2qqh).
      if (in_array('Password', $fieldList)) $fieldList[] = 'TokenMinExpiry';
    }
    if (!$selfEdit) {
      # As in the web UI, an empty Password means "leave it unchanged"
      if (isset($data['User']) and is_array($data['User'])) {
        if (empty($data['User']['Password'])) unset($data['User']['Password']);
      } else if (is_array($data) and empty($data['Password'])) {
        unset($data['Password']);
      }
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
    if ($message != 'Saved') $this->response->statusCode(400);

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
    if ( $user and (($user->System() != 'Edit') or ($user->Id() == $id)) ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
		if ( !$this->User->exists() ) {
			throw new NotFoundException(__('Invalid user'));
		}
		$this->request->allowMethod('post', 'delete');
		# User_Preferences has no ON DELETE CASCADE, so the user can't be deleted while it has rows.
		$this->loadModel('UserPreference');
		$this->UserPreference->deleteAll(array('UserPreference.UserId' => $id), false);
		if ($this->User->delete()) {
			$message = 'The user has been deleted.';
		} else {
			throw new InternalErrorException(__('The user could not be deleted.'));
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
      (!is_string($fields['Language']) or !preg_match('/^[\w\-]+$/', $fields['Language']) or !file_exists(ZM_PATH_WEB.'/lang/'.$fields['Language'].'.php'))) {
      throw new BadRequestException(__('Invalid Language'));
    }
    return array('User' => $fields);
  }
}  # end class UsersController
