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

/**
 * index method
 *
 * @return void
 * This also creates a thumbnail for each user.
 */
	public function index() {
		$this->User->recursive = 0;

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
		$this->loadModel('Config');
		$configs = $this->Config->find('list', array(
			'fields' => array('Name', 'Value'),
			'conditions' => array('Name' => array('ZM_DIR_EVENTS'))
		));

		$this->User->recursive = 1;
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
			$this->User->create();
			if ($this->User->save($this->request->data)) {
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

		if (!$this->User->exists($id)) {
			throw new NotFoundException(__('Invalid user'));
		}

		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->User->save($this->request->data)) {
				$message = 'Saved';
			} else {
				$message = 'Error';
			}
		} else {
            $this->request->data = $this->User->read(null, $id);
            unset($this->request->data['User']['password']);
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
		if (!$this->User->exists()) {
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

    public function beforeFilter() {
        parent::beforeFilter();

		$this->loadModel('Config');
		$configs = $this->Config->find('list', array(
			'fields' => array('Name', 'Value'),
			'conditions' => array('Name' => array('ZM_OPT_USE_AUTH'))
		));
		if ( $configs['ZM_OPT_USE_AUTH'] ) {
        $this->Auth->allow('add','logout');
		} else {
		$this->Auth->allow();
		}
    }

	public function login() {
		$this->loadModel('Config');
		$configs = $this->Config->find('list', array(
			'fields' => array('Name', 'Value'),
			'conditions' => array('Name' => array('ZM_OPT_USE_AUTH'))
		));

		if ( ! $configs['ZM_OPT_USE_AUTH'] ) {
			$this->set(array(
						'message' => 'Login is not required.',
						'_serialize' => array('message')
						));
			return;
		}

		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				return $this->redirect($this->Auth->redirectUrl());
			}
			$this->Session->setFlash(__('Invalid username or password, try again'));
		}
	}

	public function logout() {
		return $this->redirect($this->Auth->logout());
	}

}
