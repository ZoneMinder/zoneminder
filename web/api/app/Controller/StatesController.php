<?php
App::uses('AppController', 'Controller');
/**
 * States Controller
 *
 * @property State $State
 * @property PaginatorComponent $Paginator
 */

class StatesController extends AppController {

public $components = array('RequestHandler');

public function beforeFilter() {
  parent::beforeFilter();
  global $user;
  $canView = (!$user) || ($user->System() != 'None');
  if ( !$canView ) {
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
  $this->State->recursive = 0;
  $named_params = $this->request->params['named'];
  if ( $named_params ) {
    $this->FilterComponent = $this->Components->load('Filter');
    $conditions = $this->FilterComponent->buildFilter($named_params, array_keys($this->State->schema()));
  } else {
    $conditions = array();
  }
  $states = $this->State->find('all', array('conditions' => $conditions));
  $this->set(array(
    'states' => $states,
    '_serialize' => array('states')
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
  if ( !$this->State->exists($id) ) {
    throw new NotFoundException(__('Invalid state'));
  }
  $options = array('conditions' => array('State.' . $this->State->primaryKey => $id));
  $this->set(array(
    'state' => $this->State->find('first', $options),
    '_serialize' => array('state')
  ));
}

/**
 * add method
 *
 * @return void
 */
public function add() {

  if (!$this->request->is('post')) {
    throw new BadRequestException(__('Invalid method. Should be post'));
  }

  global $user;
  $canEdit = (!$user) || ($user->System() == 'Edit');
  if ( !$canEdit ) {
    throw new UnauthorizedException(__('Insufficient privileges'));
    return;
  }

  $this->pinRequestId($this->State, null);
  $this->State->create();
  $id = null;
  if ($this->State->save($this->request->data)) {
    $message = 'Saved';
    $id = $this->State->id;
  } else {
    $message = $this->State->validationErrors ? $this->State->validationErrors : 'Error';
  }
  $this->set(array(
    'message' => $message,
    'id' => $id,
    '_serialize' => array('message', 'id')
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
  if (!$this->State->exists($id)) {
    throw new NotFoundException(__('Invalid state'));
  }

  global $user;
  $canEdit = (!$user) || ($user->System() == 'Edit');
  if ( !$canEdit ) {
    throw new UnauthorizedException(__('Insufficient privileges'));
    return;
  }

  $this->request->allowMethod('post', 'put');
  // Without the id, save() inserts a new state instead of updating this one.
  $this->pinRequestId($this->State, $id);
  if ( $this->State->save($this->request->data) ) {
    $message = 'Saved';
  } else {
    $message = $this->State->validationErrors ? $this->State->validationErrors : 'Error';
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
  $this->State->id = $id;
  global $user;
  $canEdit = (!$user) || ($user->System() == 'Edit');
  if ( !$canEdit ) {
    throw new UnauthorizedException(__('Insufficient privileges'));
    return;
  }

  if (!$this->State->exists()) {
    throw new NotFoundException(__('Invalid state'));
  }
  $this->request->allowMethod('post', 'delete');
  if ($this->State->delete()) {
    $this->set(array(
      'message' => 'Deleted',
      '_serialize' => array('message'),
    ));
    return;
  } else {
    throw new InternalErrorException(__('The state could not be deleted.'));
  }
}

public function change() {
  global $user;
  $canEdit = (!$user) || ($user->System() == 'Edit');
  if ( !$canEdit ) {
    throw new UnauthorizedException(__('Insufficient privileges'));
    return;
  }

  if ( empty($this->request->params['pass'][0]) ) {
    throw new BadRequestException(__('A state name is required'));
  }
  $newState = $this->request->params['pass'][0];
  $blah = $this->packageControl($newState);

  $this->set(array(
    'blah' => $blah,
    '_serialize' => array('blah')
  ));
}

// protected, not public: a public controller method is directly routable
// (GET /api/states/packageControl/<cmd>), which would skip the System=Edit
// check in change() and let any user with System>=View run zmpkg.pl. change()
// still calls it after that check (refs GHSA-9gc6-9xg2-5636).
protected function packageControl( $command ) {
  $zm_path_bin = Configure::read('ZM_PATH_BIN');
  $string = $zm_path_bin.'/zmpkg.pl '.escapeshellarg( $command );
  $status = exec( $string );

  return $status;
}


}
