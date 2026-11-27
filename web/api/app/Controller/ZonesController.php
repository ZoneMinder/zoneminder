<?php
App::uses('AppController', 'Controller');
require_once __DIR__ .'/../../../includes/Monitor.php';
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
    $canView = (!$user) || ($user->Monitors() != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

  // Zones belong to a monitor, so changing one needs edit on that monitor (requireMonitorEdit()
  // in AppController), not just the global Monitors permission.

  // The monitor that the zone being edited or deleted currently belongs to.
  private function zoneMonitorId($id) {
    $this->Zone->recursive = -1;
    $zone = $this->Zone->find('first', array('conditions' => array('Zone.'.$this->Zone->primaryKey => $id)));
    return $zone['Zone']['MonitorId'];
  }

  // The MonitorId given in request data, if any.
  private function requestMonitorId() {
    $data = $this->request->data;
    if (isset($data['Zone']) and is_array($data['Zone'])) $data = $data['Zone'];
    if (!isset($data['MonitorId'])) return null;
    // An array or object here would be checked as a made-up monitor and then saved as the zone's MonitorId.
    if (!is_scalar($data['MonitorId'])) throw new BadRequestException(__('MonitorId must be a number'));
    return $data['MonitorId'];
  }

  // Find all zones which belong to a MonitorId
  public function forMonitor($id = null) {
    $this->loadModel('Monitor');
    if ( !$this->Monitor->exists($id) ) {
      throw new NotFoundException(__('Invalid monitor'));
    }

    # Monitors=View is coarse. Enforce the per-monitor ACL so zones of a monitor
    # the user is denied are not listed by direct monitor Id.
    $this->Monitor->recursive = -1;
    $monitor = $this->Monitor->find('first', array(
      'conditions' => array('Monitor.Id' => $id)
    ));
    if ( $monitor['Monitor']['Deleted'] ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    $MonitorObj = new ZM\Monitor($monitor['Monitor']);
    if ( !$MonitorObj->canView() ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
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

  public function view($id = null) {
    $this->Zone->recursive = -1;
    $zone = $this->Zone->find('first', array('conditions' => array('Zone.'.$this->Zone->primaryKey => $id)));
    if (!$zone) {
      throw new NotFoundException(__('Invalid zone'));
    }
    $monitor = new ZM\Monitor($zone['Zone']['MonitorId']);
    if (!$monitor->canView()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    $this->set(array(
      'zone' => $zone,
      '_serialize' => array('zone')
    ));
  }

  public function index() {
    $this->Zone->recursive = -1;

    global $user;
    # null means no restriction. An empty list means the user may see no monitor, so no zone.
    $allowedMonitors = ($user and $user->unviewableMonitorIds()) ? $user->viewableMonitorIds() : null;
    $conditions = array();
    if ($this->request->params['named']) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named'], array_keys($this->Zone->schema()));
    }
    $options = array();
    if ($allowedMonitors !== null) {
      $conditions[] = array('Zone.MonitorId' => $allowedMonitors);
    }
    if (count($conditions)) $options['conditions'] = $conditions;
    $zones = $this->Zone->find('all', $options);
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
    $canEdit = (!$user) || $user->Monitors() == 'Edit' || $user->Monitors() == 'Create';
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

    $monitorId = $this->requestMonitorId();
    if ($monitorId === null) {
      throw new BadRequestException(__('MonitorId is required'));
    }
    $this->requireMonitorEdit($monitorId);
    require_once __DIR__ .'/../../../includes/Monitor.php';
    $monitor = new ZM\Monitor($monitorId);
    if ( $monitor->Deleted() ) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    $this->pinRequestId($this->Zone, null);

    $zone = null;

    $this->Zone->create();
    $zone = $this->Zone->save($this->request->data);
    if ( $zone ) {
      require_once __DIR__ .'/../../../includes/Monitor.php';
      $monitor = new ZM\Monitor($zone['Zone']['MonitorId']);
      if ($monitor->Type() != 'WebSite' and !$monitor->Deleted()) $monitor->zmcControl('restart');
      $message = 'Saved';
      //$zone = $this->Zone->find('first', array('conditions' => array( array('Zone.' . $this->Zone->primaryKey => $this->Zone),
    } else {
      $message = 'Error: ';
      $this->response->statusCode(400);
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
      $canEdit = (!$user) || $user->Monitors() == 'Edit' || $user->Monitors() == 'Create';
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->pinRequestId($this->Zone, $id);
      $oldMonitorId = $this->zoneMonitorId($id);
      $this->requireMonitorEdit($oldMonitorId);
      $monitorId = $this->requestMonitorId();
      if ($monitorId !== null) $this->requireMonitorEdit($monitorId);
      if ( $this->Zone->save($this->request->data) ) {
        $message = 'The zone has been saved.';
        $monitorIds = array($oldMonitorId);
        if ($monitorId !== null and $monitorId != $oldMonitorId) $monitorIds[] = $monitorId;
        foreach ($monitorIds as $reloadId) {
          $monitor = new ZM\Monitor($reloadId);
          if ($monitor->Type() != 'WebSite' and !$monitor->Deleted()) $monitor->zmcControl('reload');
        }
      } else {
        $this->response->statusCode(400);
        $message = 'Error ' . print_r($this->Zone->invalidFields(), true);
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
    $canEdit = (!$user) || $user->Monitors() == 'Edit' || $user->Monitors() == 'Create';
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    $monitorId = $this->zoneMonitorId($id);
    $this->requireMonitorEdit($monitorId);
    if ( $this->Zone->delete() ) {
      $monitor = new ZM\Monitor($monitorId);
      if ($monitor->Type() != 'WebSite' and !$monitor->Deleted()) $monitor->zmcControl('restart');
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
    } else {
      throw new InternalErrorException(__('The zone could not be deleted.'));
    }
  }
} // end class
