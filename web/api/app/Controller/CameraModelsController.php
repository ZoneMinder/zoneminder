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
    
    $conditions = array();
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      // Manufacturers is joined and has Id and Name too, so write CameraModel.Name.
      $named = $this->FilterComponent->qualifyFields($this->request->params['named'], array(
        'CameraModel' => array_keys($this->CameraModel->schema()),
        'Manufacturer' => array_keys($this->CameraModel->Manufacturer->schema()),
      ));
      $conditions = $this->FilterComponent->buildFilter($named, array_merge(array_keys($this->CameraModel->schema()), array_keys($this->CameraModel->Manufacturer->schema())));
    }
    $models = $this->CameraModel->find('all', array('conditions' => $conditions));
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
    if ( !$this->request->is('post') ) {
      throw new BadRequestException(__('Invalid method. Should be post'));
    }

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->pinRequestId($this->CameraModel, null);
    $this->CameraModel->create();
    if ($this->CameraModel->save($this->request->data)) {
      $this->set(array(
        'message' => 'Saved',
        'id' => $this->CameraModel->id,
        '_serialize' => array('message', 'id')
      ));
      return;
    }
    $this->response->statusCode(400);
    $this->set(array(
      'message' => $this->CameraModel->validationErrors ? $this->CameraModel->validationErrors : 'Error',
      'id' => null,
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
    $this->request->allowMethod('post', 'put');
    $this->pinRequestId($this->CameraModel, $id);
    if ($this->CameraModel->save($this->request->data)) {
      $message = 'Saved';
    } else {
      $message = $this->CameraModel->validationErrors ? $this->CameraModel->validationErrors : 'Error';
      $this->response->statusCode(400);
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

    # Monitors and presets reference a model by foreign key, so deleting one in use is a
    # database error, not a deletion.
    $inUse = dbFetchOne('SELECT COUNT(*) AS Count FROM Monitors WHERE ModelId=?', 'Count', array($id))
      + dbFetchOne('SELECT COUNT(*) AS Count FROM MonitorPresets WHERE ModelId=?', 'Count', array($id));
    if ($inUse) {
      throw new HttpException(__('The model is in use by monitors or presets.'), 409);
    }

    if ($this->CameraModel->delete()) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
    } else {
      throw new InternalErrorException(__('The model could not be deleted.'));
    }
  }
}
