<?php
App::uses('AppController', 'Controller');
/**
 * UserPreference Controller
 *
 * @property UserPreference $UserPreference
 */
class UserPreferenceController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler');

  # A preference belongs to the user who set it, so ownership is the permission:
  # everyone may manage their own, and nobody else's. Requiring System instead
  # let any System account read and overwrite another user's preferences, and
  # stopped ordinary users saving theirs at all, which is what the montage layout
  # in montage_common.js does through this controller.

/**
 * The authenticated user's id, or null when authentication is off.
 */
  private function currentUserId() {
    global $user;
    return $user ? $user->Id() : null;
  }

/**
 * Restrict a row to the caller. A System Edit account may manage anyone's, so
 * that an administrator can still clear a broken preference.
 *
 * @throws UnauthorizedException
 */
  private function assertOwned($id) {
    global $user;
    if (!$user) return;
    if ($user->System() == 'Edit') return;
    $row = $this->UserPreference->find('first', array(
      'conditions' => array('UserPreference.'.$this->UserPreference->primaryKey => $id)
    ));
    if (!$row or ($row['UserPreference']['UserId'] != $user->Id())) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->UserPreference->recursive = -1;
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    global $user;
    if ($user and ($user->System() != 'Edit')) {
      $conditions['UserPreference.UserId'] = $user->Id();
    }

    $find_array = array(
      'conditions' => &$conditions,
    );
    $user_preferences = $this->UserPreference->find('all', $find_array);
    $this->set(array(
      'user_preferences' => $user_preferences,
      '_serialize' => array('user_preferences')
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
		$this->UserPreference->recursive = -1;
		if (!$this->UserPreference->exists($id)) {
			throw new NotFoundException(__('Invalid user preference'));
		}
		$this->assertOwned($id);
		$options = array('conditions' => array('UserPreference.' . $this->UserPreference->primaryKey => $id));
		$user_preference = $this->UserPreference->find('first', $options);
		$this->set(array(
			'user_preference' => $user_preference,
			'_serialize' => array('user_preference')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
    $data = $this->request->data;
    if ($this->RequestHandler->requestedWith('json')) {
      $data = $this->request->input('json_decode', true) ;
    }
    $message = '';
		if ($this->request->is('post')) {
      # The client sends its own UserId; taking it on trust would let anyone
      # write a preference for another account.
      $own = $this->currentUserId();
      if ($own !== null) $data['UserId'] = $own;
      $exists = $this->UserPreference->find('first', ['conditions'=>['UserId'=>$data['UserId'],'Name'=>$data['Name']]]);
      if ($exists) {
        $this->UserPreference->id = $exists['UserPreference']['Id'];
        $rc = $this->UserPreference->save($data);
      } else {
        $this->UserPreference->create();
        $rc = $this->UserPreference->save($data);
      }
      if ($rc) {
        $message = 'Success';
      } else {
        $message = 'Failure';
        ZM\Warning($this->validationErrors);
      }
    } else {
      ZM\Error("NOT POST in add()");
    }
    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
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
		if (!$this->UserPreference->exists($id)) {
			throw new NotFoundException(__('Invalid user_preference'));
		}
		$this->assertOwned($id);
		if ($this->request->is(array('post', 'put'))) {
			if ($this->UserPreference->save($this->request->data)) {
			}
		} else {
			$options = array('conditions' => array('UserPreference.'.$this->UserPreference->primaryKey => $id));
			$this->request->data = $this->UserPreference->find('first', $options);
		}
		$preference = $this->UserPreference;
		$this->set(compact('user_preference'));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->UserPreference->id = $id;
		if (!$this->UserPreference->exists()) {
			throw new NotFoundException(__('Invalid user_preference'));
		}
		$this->assertOwned($id);
		$this->request->allowMethod('post', 'delete');
		if ($this->UserPreference->delete()) {
			return $this->flash(__('The user_preference has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The user_preference could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}
}
