<?php
App::uses('AppController', 'Controller');
/**
 * Roles Controller
 *
 * @property Role $Role
 */
class RolesController extends AppController {

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
    $canView = (!$user) || ($user->System() != 'None');
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
    $this->Role->recursive = 0;

    global $user;
    if ($user->System() == 'None') {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    $roles = $this->Paginator->paginate('Role');

    $this->set(compact('roles'));
  }

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
  public function view($id = null) {
    $this->Role->recursive = 1;

    global $user;
    if ($user->System() == 'None') {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    if (!$this->Role->exists($id)) {
      throw new NotFoundException(__('Invalid role'));
    }

    $options = array('conditions' => array('Role.' . $this->Role->primaryKey => $id));
    $role = $this->Role->find('first', $options);

    $this->set(array(
      'role' => $role,
      '_serialize' => array('role')
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

      $this->Role->create();
      if ($this->Role->save($this->request->data)) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if (!$this->Role->validates()) {
          $message = $this->Role->validationErrors;
        }
      }
    } else {
      $message = 'Add without post data';
    }
    $this->set(array(
      'role'        => $this->Role,
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
    $this->Role->id = $id;

    global $user;
    if ($user->System() != 'Edit') {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    if (!$this->Role->exists($id)) {
      throw new NotFoundException(__('Invalid role'));
    }

    if ($this->request->is('post') || $this->request->is('put')) {
      if ($this->Role->save($this->request->data)) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        if (!$this->Role->validates()) {
          $message = $this->Role->validationErrors;
        }
      }
    } else {
      $this->request->data = $this->Role->read(null, $id);
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
    $this->Role->id = $id;

    global $user;
    if ($user->System() != 'Edit') {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    if (!$this->Role->exists()) {
      throw new NotFoundException(__('Invalid role'));
    }
    $this->request->allowMethod('post', 'delete');
    if ($this->Role->delete()) {
      $message = 'The role has been deleted.';
    } else {
      $message = 'The role could not be deleted. Please, try again.';
    }
    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));
  }
}  # end class RolesController
