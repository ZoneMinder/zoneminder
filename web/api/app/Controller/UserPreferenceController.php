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

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = $user && ($user->System() != 'None');
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
		$this->UserPreference->recursive = -1;
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
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
		$options = array('conditions' => array('User_Preferences.' . $this->UserPreference->primaryKey => $id));
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
		if ($this->request->is(array('post', 'put'))) {
			if ($this->UserPreference->save($this->request->data)) {
			}
		} else {
			$options = array('conditions' => array('User_Preferences.'.$this->UserPreference->primaryKey => $id));
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
		$this->request->allowMethod('post', 'delete');
		if ($this->UserPreference->delete()) {
			return $this->flash(__('The user_preference has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The user_preference could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}
}
