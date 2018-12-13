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
    $canView = (!$user) || ($user['System'] != 'None');
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
    
    $options = '';
    $storage_areas = $this->Storage->find('all',$options);
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
      $canEdit = (!$user) || ($user['System'] == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient privileges'));
        return;
      }

      $this->Storage->create();
      if ( $this->Storage->save($this->request->data) ) {
				# Might be nice to send it a start request
        #$this->daemonControl($this->Storage->id, 'start', $this->request->data);
        return $this->flash(__('The storage area has been saved.'), array('action' => 'index'));
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
    $this->Storage->id = $id;

    global $user;
    $canEdit = (!$user) || ($user['System'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->Storage->exists($id) ) {
      throw new NotFoundException(__('Invalid storage area'));
    }
    if ( $this->Storage->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = 'Error';
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
    $canEdit = (!$user) || ($user['System'] == 'Edit');
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

    if ( $this->Storage->delete() ) {
      return $this->flash(__('The storage area has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The storage area could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
}
