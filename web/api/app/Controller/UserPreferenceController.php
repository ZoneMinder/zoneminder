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
    $canView = (!$user) || ($user->Users() != 'None');
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
    $user_preference = $this->UserPreference->find('all', $find_array);
    $this->set(array(
      'user_preference' => $user_preference,
      '_serialize' => array('user_preference')
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
			throw new NotFoundException(__('Invalid user data'));
		}
		$options = array('conditions' => array('User_Data.' . $this->UserPreference->primaryKey => $id));
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
		if ($this->request->is('post')) {
			$this->UserPreference->create();
			if ($this->UserPreference->save($this->request->data)) {
			}
		}
		$users = $this->UserPreference->User->find('list');
		$this->set(compact('users'));
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
			$options = array('conditions' => array('User_Data.' . $this->UserPreference->primaryKey => $id));
			$this->request->data = $this->UserPreference->find('first', $options);
		}
		$users = $this->UserPreference->User->find('list');
		$this->set(compact('users'));
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
