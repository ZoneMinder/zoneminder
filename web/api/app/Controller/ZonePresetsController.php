<?php
App::uses('AppController', 'Controller');
/**
 * ZonePresets Controller
 *
 * @property ZonePreset $ZonePreset
 * @property PaginatorComponent $Paginator
 */
class ZonePresetsController extends AppController {

  /**
   * Components
   *
   * @var array
   */
  public $components = array('RequestHandler');

  # Zone presets are shared templates, not tied to a monitor. Anyone signed in may read them,
  # changing them is system configuration.
  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    if ($user and !in_array($this->request->action, array('index', 'view')) and ($user->System() != 'Edit')) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  /**
   * index method
   *
   * @return void
   */
  public function index() {
    $options = array();
    if ($this->request->params['named']) {
      $this->FilterComponent = $this->Components->load('Filter');
      $options['conditions'] = $this->FilterComponent->buildFilter($this->request->params['named'], array_keys($this->ZonePreset->schema()));
    }
    $zonePresets = $this->ZonePreset->find('all', $options);
    $this->set(array(
      'zonePresets' => $zonePresets,
      '_serialize' => array('zonePresets')
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
    if ( !$this->ZonePreset->exists($id) ) {
      throw new NotFoundException(__('Invalid zone preset'));
    }
    $options = array('conditions' => array('ZonePreset.' . $this->ZonePreset->primaryKey => $id));
    $this->set(array(
      'zonePreset' => $this->ZonePreset->find('first', $options),
      '_serialize' => array('zonePreset')
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
    if ( $this->request->is('post') ) {
      $this->pinRequestId($this->ZonePreset, null);
      $this->ZonePreset->create();
      if ( $this->ZonePreset->save($this->request->data) ) {
        $this->set(array(
          'message' => 'Saved',
          'id' => $this->ZonePreset->id,
          '_serialize' => array('message', 'id')
        ));
        return;
      }
      $this->set(array(
        'message' => $this->ZonePreset->validationErrors ? $this->ZonePreset->validationErrors : 'Error',
        'id' => null,
        '_serialize' => array('message', 'id')
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
    if ( !$this->ZonePreset->exists($id) ) {
      throw new NotFoundException(__('Invalid zone preset'));
    }
    if ( $this->request->is(array('post', 'put')) ) {
      $this->pinRequestId($this->ZonePreset, $id);
      if ( $this->ZonePreset->save($this->request->data) ) {
        $this->set(array(
          'message' => 'Saved',
          '_serialize' => array('message')
        ));
        return;
      }
      $this->set(array(
        'message' => $this->ZonePreset->validationErrors ? $this->ZonePreset->validationErrors : 'Error',
        '_serialize' => array('message')
      ));
    } else {
      throw new BadRequestException(__('Invalid method. Should be post or put'));
    }
  }

  /**
   * delete method
   *
   * @throws NotFoundException
   * @param string $id
   * @return void
   */
  public function delete($id = null) {
    $this->ZonePreset->id = $id;
    if ( !$this->ZonePreset->exists() ) {
      throw new NotFoundException(__('Invalid zone preset'));
    }
    $this->request->allowMethod('post', 'delete');
    if ( $this->ZonePreset->delete() ) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
      return;
    } else {
      throw new InternalErrorException(__('The zone preset could not be deleted.'));
    }
  }
} // end class ZonePresetsController
