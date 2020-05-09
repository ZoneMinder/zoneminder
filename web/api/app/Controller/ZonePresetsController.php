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

  /**
   * index method
   *
   * @return void
   */
  public function index() {
    $zonePresets = $this->ZonePreset->find('all');
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
    $this->set('zonePreset', $this->ZonePreset->find('first', $options));
  }

  /**
   * add method
   *
   * @return void
   */
  public function add() {
    if ( $this->request->is('post') ) {
      $this->ZonePreset->create();
      if ( $this->ZonePreset->save($this->request->data) ) {
        return $this->flash(__('The zone preset has been saved.'), array('action' => 'index'));
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
    if ( !$this->ZonePreset->exists($id) ) {
      throw new NotFoundException(__('Invalid zone preset'));
    }
    if ( $this->request->is(array('post', 'put')) ) {
      if ( $this->ZonePreset->save($this->request->data) ) {
        return $this->flash(__('The zone preset has been saved.'), array('action' => 'index'));
      }
    } else {
      $options = array('conditions' => array('ZonePreset.' . $this->ZonePreset->primaryKey => $id));
      $this->request->data = $this->ZonePreset->find('first', $options);
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
      return $this->flash(__('The zone preset has been deleted.'), array('action' => 'index'));
    } else {
      return $this->flash(__('The zone preset could not be deleted. Please, try again.'), array('action' => 'index'));
    }
  }
} // end class ZonePresetsController
