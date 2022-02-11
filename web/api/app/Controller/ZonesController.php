<?php
App::uses('AppController', 'Controller');
/**
 * Zones Controller
 *
 * @property Zone $Zone
 */
class ZonesController extends AppController {

  /**
   * Components
   *      
   * @var array
   */     
  public $components = array('RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();

    global $user;
    $canView = (!$user) || ($user['Monitors'] != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

  // Find all zones which belong to a MonitorId
  public function forMonitor($id = null) {
    $this->loadModel('Monitor');
    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    $this->Zone->recursive = -1;
    $zones = $this->Zone->find('all', array(
      'conditions' => array('MonitorId' => $id)
    ));
    $this->set(array(
      'zones' => $zones,
      '_serialize' => array('zones')
    ));
  }

  public function index() {
    $this->Zone->recursive = -1;

    global $user;
    $allowedMonitors = $user ? preg_split('@,@', $user['MonitorIds'],NULL, PREG_SPLIT_NO_EMPTY) : null;
    if ( $allowedMonitors ) {
      $mon_options = array('Zones.MonitorId' => $allowedMonitors);
    } else {
      $mon_options = '';
    }
    $zones = $this->Zone->find('all',$mon_options);
    $this->set(array(
      'zones' => $zones,
      '_serialize' => array('zones')
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
      return;
    }

    global $user;
    $canEdit = (!$user) || $user['Monitors'] == 'Edit';
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    $zone = null;

    $this->Zone->create();
    $zone = $this->Zone->save($this->request->data);
    if ( $zone ) {
      require_once __DIR__ .'/../../../includes/Monitor.php';
      $monitor = new ZM\Monitor($zone['Zone']['MonitorId']);
      $monitor->zmcControl('restart');
      $message = 'Saved';
      //$zone = $this->Zone->find('first', array('conditions' => array( array('Zone.' . $this->Zone->primaryKey => $this->Zone),
    } else {
      $message = 'Error: ';
      // if there is a validation message, use it
      if ( !$this->Zone->validates() ) {
        $message = $this->Zone->validationErrors;
      }
    }

    $this->set(array(
      'message' => $message,
      'zone' => $zone,
      '_serialize' => array('message','zone')
    ));
  } // end function add()

  /**
   * edit method
   *
   * @throws NotFoundException
   * @param string $id
   * @return void
   */
  public function edit($id = null) {
    $this->Zone->id = $id;

    if ( !$this->Zone->exists($id) ) {
      throw new NotFoundException(__('Invalid zone'));
    }
    $message = '';
    if ( $this->request->is(array('post', 'put')) ) {
      global $user;
      $canEdit = (!$user) || $user['Monitors'] == 'Edit';
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      if ( $this->Zone->save($this->request->data) ) {
        $message = 'The zone has been saved.';
      } else {
        $message = 'Error ' . print_r($this->Zone->invalidFields());
      }
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
    $this->Zone->id = $id;
    if ( !$this->Zone->exists() ) {
      throw new NotFoundException(__('Invalid zone'));
    }
    $this->request->allowMethod('post', 'delete');
    global $user;
    $canEdit = (!$user) || $user['Monitors'] == 'Edit';
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    if ( $this->Zone->delete() ) {
      return $this->flash(__('The zone has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The zone could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
} // end class
