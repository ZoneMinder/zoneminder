<?php
App::uses('AppController', 'Controller');
/**
 * Manufacturers Controller
 *
 * @property Manufacturer $Manufacturer
 * @property PaginatorComponent $Paginator
 */
class ManufacturersController extends AppController {

/**
 * Components
 *
 * @var array
 */
  public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
    /*
     * A user needs the manufacturer data to calculate how to view a monitor, and there really isn't anything sensitive in this data.
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
    $this->Manufacturer->recursive = 0;
    
    $conditions = array();
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named'], array_keys($this->Manufacturer->schema()));
    }
    $manufacturers = $this->Manufacturer->find('all', array('conditions' => $conditions));
		$this->set(array(
					'manufacturers' => $manufacturers,
					'_serialize' => array('manufacturers')
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
    $this->Manufacturer->recursive = 0;
    if ( !$this->Manufacturer->exists($id) ) {
      throw new NotFoundException(__('Invalid manufacturer'));
    }
    $restricted = '';
    
    $options = array('conditions' => array( 
          array('Manufacturer.'.$this->Manufacturer->primaryKey => $id),
          $restricted
          )
        );
    $manufacturer = $this->Manufacturer->find('first', $options);
    $this->set(array(
      'manufacturer' => $manufacturer,
      '_serialize' => array('manufacturer')
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
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->pinRequestId($this->Manufacturer, null);
    $this->Manufacturer->create();
    if ( $this->Manufacturer->save($this->request->data) ) {
      $this->set(array(
        'message' => 'Saved',
        'id' => $this->Manufacturer->id,
        '_serialize' => array('message', 'id')
      ));
      return;
    }
    $this->set(array(
      'message' => $this->Manufacturer->validationErrors ? $this->Manufacturer->validationErrors : 'Error',
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
    $this->Manufacturer->id = $id;

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->Manufacturer->exists($id) ) {
      throw new NotFoundException(__('Invalid manufacturer'));
    }
    $this->request->allowMethod('post', 'put');
    $this->pinRequestId($this->Manufacturer, $id);
    if ( $this->Manufacturer->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = $this->Manufacturer->validationErrors ? $this->Manufacturer->validationErrors : 'Error';
    }

    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));
    // - restart this manufacturer after change
    #$this->daemonControl($this->Manufacturer->id, 'restart', $this->request->data);
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
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->Manufacturer->id = $id;
    if ( !$this->Manufacturer->exists() ) {
      throw new NotFoundException(__('Invalid manufacturer'));
    }
    $this->request->allowMethod('post', 'delete');

    #$this->daemonControl($this->Manufacturer->id, 'stop');

    # Monitors reference a manufacturer and its models by foreign key, so deleting one in
    # use is a database error, not a deletion. Its models go with it otherwise, or they
    # would stay behind pointing at nothing.
    $inUse = dbFetchOne('SELECT COUNT(*) AS Count FROM Monitors WHERE ManufacturerId=? OR ModelId IN (SELECT Id FROM Models WHERE ManufacturerId=?)', 'Count', array($id, $id))
      + dbFetchOne('SELECT COUNT(*) AS Count FROM MonitorPresets WHERE ModelId IN (SELECT Id FROM Models WHERE ManufacturerId=?)', 'Count', array($id));
    if ( $inUse ) {
      throw new HttpException(__('The manufacturer is in use by monitors or presets.'), 409);
    }
    dbQuery('DELETE FROM `Models` WHERE `ManufacturerId`=?', array($id));

    if ( $this->Manufacturer->delete() ) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
    } else {
      throw new InternalErrorException(__('The manufacturer could not be deleted.'));
    }
  }
}
