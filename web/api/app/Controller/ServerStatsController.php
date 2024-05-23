<?php
App::uses('AppController', 'Controller');
/**
 * ServerStats Controller
 *
 * @property Server $Server
 * @property PaginatorComponent $Paginator
 */
class ServerStatsController extends AppController {

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
    $this->ServerStat->recursive = 0;
    
    $named_params = $this->request->params['named'];
    if ( $named_params ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($named_params);
    } else {
      $conditions = array();
    }

    $stats = $this->ServerStat->find('all', ['conditions'=>$conditions,
      'order' => array('TimeStamp ASC'),
    ]);
		$this->set(array(
					'serverstats' => $stats,
					'_serialize' => array('serverstats')
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
    $this->ServerStat->recursive = 0;
    if ( !$this->ServerStat->exists($id) ) {
      throw new NotFoundException(__('Invalid server stat entry'));
    }
    $restricted = '';
    
    $options = array('conditions' => array( 
          array('Server_Stats.'.$this->ServerStat->primaryKey => $id),
          $restricted
          )
        );
    $serverstat = $this->ServerStat->find('first', $options);
    $this->set(array(
      'serverstat' => $serverstat,
      '_serialize' => array('serverstat')
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
      if (!$canEdit) {
        throw new UnauthorizedException(__('Insufficient privileges'));
        return;
      }

      $this->ServerStat->create();
      if ( $this->ServerStat->save($this->request->data) ) {
        return $this->flash(__('The server stat has been saved.'), array('action' => 'index'));
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
    $this->ServerStat->id = $id;

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->ServerStat->exists($id) ) {
      throw new NotFoundException(__('Invalid server stat'));
    }
    if ( $this->ServerStat->save($this->request->data) ) {
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
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->ServerStat->id = $id;
    if ( !$this->ServerStat->exists() ) {
      throw new NotFoundException(__('Invalid server stat'));
    }
    $this->request->allowMethod('post', 'delete');

    if ( $this->ServerStat->delete() ) {
      return $this->flash(__('The server stat has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The server stat could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
}
