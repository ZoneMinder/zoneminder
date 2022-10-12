<?php
App::uses('AppController', 'Controller');
/**
 * Logs Controller
 *
 * @property Log $Log
 * @property PaginatorComponent $Paginator
 */
class LogsController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');
	public $paginate = array(
		'limit' => 100,
		'order' => array( 'Log.TimeKey' => 'asc' ),
		'paramType' => 'querystring'
	);

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user['System'] != 'None');
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
		$this->Log->recursive = -1;
		$this->Paginator->settings = $this->paginate;

		$logs = $this->Paginator->paginate('Log');
		$this->set(compact('logs'));
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->Log->exists($id)) {
			throw new NotFoundException(__('Invalid log'));
		}
		$options = array('conditions' => array('Log.' . $this->Log->primaryKey => $id));
		$this->set('log', $this->Log->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
    global $user;
    $canAdd = (!$user) || (($user['System'] == 'Edit') || ZM_LOG_INJECT);
    if (!$canAdd) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }
		if ($this->request->is('post')) {
			$this->Log->create();
			if ($this->Log->save($this->request->data)) {
				return $this->flash(__('The log has been saved.'), array('action' => 'index'));
			}
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
    global $user;
    $canEdit = (!$user) || ($user['System'] == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

		if (!$this->Log->exists($id)) {
			throw new NotFoundException(__('Invalid log'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Log->save($this->request->data)) {
				return $this->flash(__('The log has been saved.'), array('action' => 'index'));
			}
		} else {
			$options = array('conditions' => array('Log.' . $this->Log->primaryKey => $id));
			$this->request->data = $this->Log->find('first', $options);
		}
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
    $canDelete = (!$user) || ($user['System'] == 'Edit');
    if (!$canDelete) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }
		$this->Log->id = $id;
		if (!$this->Log->exists()) {
			throw new NotFoundException(__('Invalid log'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Log->delete()) {
			return $this->flash(__('The log has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The log could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
