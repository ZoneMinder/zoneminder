<?php
App::uses('AppController', 'Controller');
/**
 * Servers Controller
 *
 * @property Server $Server
 * @property PaginatorComponent $Paginator
 */
class ServersController extends AppController {

/**
 * Components
 *
 * @var array
 */
  public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
    /*
     * A user needs the server data to calculate how to view a monitor, and there really isn't anything sensitive in this data.
     * So it has been decided for now to just let everyone read it.
     
    global $user;
    $canView = (!$user) || ($user['System'] != 'None');
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
    $this->Server->recursive = 0;
    
    $options = '';
    $servers = $this->Server->find('all', $options);
		$this->set(array(
					'servers' => $servers,
					'_serialize' => array('servers')
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
    $this->Server->recursive = 0;
    if ( !$this->Server->exists($id) ) {
      throw new NotFoundException(__('Invalid server'));
    }
    $restricted = '';
    
    $options = array('conditions' => array( 
          array('Server.'.$this->Server->primaryKey => $id),
          $restricted
          )
        );
    $server = $this->Server->find('first', $options);
    $this->set(array(
      'server' => $server,
      '_serialize' => array('server')
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

      $this->Server->create();
      if ( $this->Server->save($this->request->data) ) {
				# Might be nice to send it a start request
        #$this->daemonControl($this->Server->id, 'start', $this->request->data);
        return $this->flash(__('The server has been saved.'), array('action' => 'index'));
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
    $this->Server->id = $id;

    global $user;
    $canEdit = (!$user) || ($user['System'] == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->Server->exists($id) ) {
      throw new NotFoundException(__('Invalid server'));
    }
    if ( $this->Server->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = 'Error';
    }

    $this->set(array(
      'message' => $message,
      '_serialize' => array('message')
    ));
    // - restart this server after change
    #$this->daemonControl($this->Server->id, 'restart', $this->request->data);
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

    $this->Server->id = $id;
    if ( !$this->Server->exists() ) {
      throw new NotFoundException(__('Invalid server'));
    }
    $this->request->allowMethod('post', 'delete');

    #$this->daemonControl($this->Server->id, 'stop');

    if ( $this->Server->delete() ) {
      return $this->flash(__('The server has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The server could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
}
