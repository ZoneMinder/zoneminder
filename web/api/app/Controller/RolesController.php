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
    if ($user and ($user->System() == 'None')) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    $roles = $this->Paginator->paginate('Role');

    $this->set(array(
      'roles' => $roles,
      '_serialize' => array('roles')
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
    $this->Role->recursive = 1;

    global $user;
    if ($user and ($user->System() == 'None')) {
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
      if ($user and ($user->System() != 'Edit')) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

      $this->pinRequestId($this->Role, null);
      $this->Role->create();
      $id = null;
      if ($this->Role->save($this->request->data)) {
        $message = 'Saved';
        $id = $this->Role->id;
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if (!$this->Role->validates()) {
          $message = $this->Role->validationErrors;
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
    $this->request->allowMethod('post', 'put');
    $this->Role->id = $id;

    global $user;
    if ($user and ($user->System() != 'Edit')) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    if (!$this->Role->exists($id)) {
      throw new NotFoundException(__('Invalid role'));
    }

    $message = '';
    if ($this->request->is('post') || $this->request->is('put')) {
      $this->pinRequestId($this->Role, $id);
      if ($this->Role->save($this->request->data)) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        if (!$this->Role->validates()) {
          $message = $this->Role->validationErrors;
        }
        $this->response->statusCode(400);
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
    if ($user and ($user->System() != 'Edit')) {
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
      throw new InternalErrorException(__('The role could not be deleted.'));
    }
    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));
  }
}  # end class RolesController
