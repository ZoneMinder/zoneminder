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
    
    $options = '';
    $manufacturers = $this->Manufacturer->find('all', $options);
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
    if ( $this->request->is('post') ) {

      global $user;
      $canEdit = (!$user) || ($user->System() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient privileges'));
        return;
      }

      $this->Manufacturer->create();
      if ( $this->Manufacturer->save($this->request->data) ) {
				# Might be nice to send it a start request
        #$this->daemonControl($this->Manufacturer->id, 'start', $this->request->data);
        return $this->flash(__('The manufacturer has been saved.'), array('action' => 'index'));
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
    if ( $this->Manufacturer->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = 'Error';
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

    if ( $this->Manufacturer->delete() ) {
      return $this->flash(__('The manufacturer has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The manufacturer could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
}
