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
    $this->Server->recursive = 0;
    
    $named_params = $this->request->params['named'];
    if ( $named_params ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($named_params, array_keys($this->Server->schema()));
    } else {
      $conditions = array();
    }
    $servers = $this->Server->find('all', array('conditions' => $conditions));
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
    if ( !$this->request->is('post') ) {
      throw new BadRequestException(__('Invalid method. Should be post'));
    }

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->pinRequestId($this->Server, null);
    $this->Server->create();
    if ( $this->Server->save($this->request->data) ) {
      $this->set(array(
        'message' => 'Saved',
        'id' => $this->Server->id,
        '_serialize' => array('message', 'id')
      ));
      return;
    }
    $this->response->statusCode(400);
    $this->set(array(
      'message' => $this->Server->validationErrors ? $this->Server->validationErrors : 'Error',
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
    $this->Server->id = $id;

    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    if ( !$this->Server->exists($id) ) {
      throw new NotFoundException(__('Invalid server'));
    }
    $this->request->allowMethod('post', 'put');
    $this->pinRequestId($this->Server, $id);
    if ( $this->Server->save($this->request->data) ) {
      $message = 'Saved';
    } else {
      $message = $this->Server->validationErrors ? $this->Server->validationErrors : 'Error';
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
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient privileges'));
      return;
    }

    $this->Server->id = $id;
    if ( !$this->Server->exists() ) {
      throw new NotFoundException(__('Invalid server'));
    }
    $this->request->allowMethod('post', 'delete');

    require_once __DIR__ .'/../../../includes/Server.php';
    $inUse = ZM\Server::InUseSummary((int)$id);
    if ( $inUse ) {
      throw new BadRequestException(__('The server is still used by %s', $inUse));
    }

    if ( $this->Server->delete() ) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
    } else {
      throw new InternalErrorException(__('The server could not be deleted.'));
    }
  }
}
