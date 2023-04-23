<?php
App::uses('AppController', 'Controller');
/**
 * Models Controller
 *
 * @property Model $Model
 * @property PaginatorComponent $Paginator
 */
class CameraModelsController extends AppController {

/**
 * Components
 *
 * @var array
 */
  public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
    /*
     * A user needs the model data to calculate how to view a monitor, and there really isn't anything sensitive in this data.
     * So it has been decided for now to just let everyone read it.
     
    global $user;
    $canView = (!$user) || ($user->System() != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
     */
  }

/**
 * index method
 *
 * @return void
 */
  public function index() {
    $this->CameraModel->recursive = 0;
    
    $options = '';
    $models = $this->CameraModel->find('all', $options);
		$this->set(array(
					'models' => $models,
					'_serialize' => array('models')
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
    $this->CameraModel->recursive = 0;
    if ( !$this->CameraModel->exists($id) ) {
      throw new NotFoundException(__('Invalid model'));
    }
    $restricted = '';
    
    $options = array('conditions' => array( 
          array('CameraModel.'.$this->CameraModel->primaryKey => $id),
          $restricted
          )
        );
    $model = $this->CameraModel->find('first', $options);
    $this->set(array(
      'model' => $model,
      '_serialize' => array('model')
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
      $canEdit = (!$user) || ($user->System() == 'Edit');
      if (!$canEdit) {
        throw new UnauthorizedException(__('Insufficient privileges'));
        return;
      }

      $this->CameraModel->create();
      if ($this->CameraModel->save($this->request->data)) {
        return $this->flash(__('The model has been saved.'), array('action' => 'index'));
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
    $this->CameraModel->id = $id;

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if (!$this->CameraModel->exists($id)) {
      throw new NotFoundException(__('Invalid model'));
    }
    if ($this->CameraModel->save($this->request->data)) {
      $message = 'Saved';
    } else {
      $message = 'Error';
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
    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->CameraModel->id = $id;
    if (!$this->CameraModel->exists()) {
      throw new NotFoundException(__('Invalid model'));
    }
    $this->request->allowMethod('post', 'delete');

    if ($this->CameraModel->delete()) {
      return $this->flash(__('The model has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The model could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
}
