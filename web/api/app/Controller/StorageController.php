<?php
App::uses('AppController', 'Controller');
/**
 * Storage Controller
 *
 * @property Storage $Storage
 * @property PaginatorComponent $Paginator
 */
class StorageController extends AppController {

/**
 * Components
 *
 * @var array
 */
  public $components = array('Paginator', 'RequestHandler');

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
    $this->Storage->recursive = -1;
    
    $named_params = $this->request->params['named'];
    if ( $named_params ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($named_params, array_keys($this->Storage->schema()));
    } else {
      $conditions = array();
    }
    $options = ['conditions'=>&$conditions];
    $storage_areas = $this->Storage->find('all', $options);
    require_once __DIR__ .'/../../../includes/Storage.php';

    foreach ($storage_areas as &$s) {
      $storage = new ZM\Storage($s['Storage']);
      $s['Storage']['DiskTotalSpace'] = $storage->disk_total_space();
      $s['Storage']['DiskUsedSpace'] = $storage->disk_used_space();
    }

		$this->set(array(
					'storage' => $storage_areas,
					'_serialize' => array('storage')
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
    $this->Storage->recursive = 0;
    if (!$this->Storage->exists($id)) {
      throw new NotFoundException(__('Invalid storage area'));
    }
    $restricted = '';
    
    $options = array('conditions' => array( 
          array('Storage.' . $this->Storage->primaryKey => $id),
          $restricted
          )
        );
    $storage = $this->Storage->find('first', $options);
    $this->set(array(
      'storage' => $storage,
      '_serialize' => array('storage')
    ));
  }

/**
 * add method
 *
 * @return void
 */
  public function add() {
    if ( $this->request->is('post') ) {

      global $user;
      $canEdit = (!$user) || ($user->System() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient privileges'));
        return;
      }

      $this->pinRequestId($this->Storage, null);
      $this->Storage->create();
      if ( $this->Storage->save($this->request->data) ) {
				# Might be nice to send it a start request
        #$this->daemonControl($this->Storage->id, 'start', $this->request->data);
        return $this->flash(__('The storage area has been saved.'), array('action' => 'index'));
      }
      $this->set(array(
        'message' => $this->Storage->validationErrors ? $this->Storage->validationErrors : 'Error',
        '_serialize' => array('message')
      ));
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
    $this->Storage->id = $id;

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->Storage->exists($id) ) {
      throw new NotFoundException(__('Invalid storage area'));
    }
    $this->pinRequestId($this->Storage, $id);
    if ( $this->Storage->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = $this->Storage->validationErrors ? $this->Storage->validationErrors : 'Error';
    }

    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));
    // - restart this storage area after change
    #$this->daemonControl($this->Storage->id, 'restart', $this->request->data);
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

    $this->Storage->id = $id;
    if ( !$this->Storage->exists() ) {
      throw new NotFoundException(__('Invalid storage area'));
    }
    $this->request->allowMethod('post', 'delete');

    #$this->daemonControl($this->Storage->id, 'stop');

    # The web UI refuses the same delete, which would orphan the events and monitors using this area.
    require_once __DIR__ .'/../../../includes/Storage.php';
    $storage = new ZM\Storage($id);
    if ( $storage->EventCount() or $storage->MonitorCount() ) {
      throw new BadRequestException(__('The storage area is in use by events or monitors'));
    }

    if ( $this->Storage->delete() ) {
      return $this->flash(__('The storage area has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The storage area could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
}
