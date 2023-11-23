<?php
App::uses('AppController', 'Controller');
/**
 * Floorplans Controller
 *
 * @property Floorplans $Floorplans
 */
class FloorplansController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
  }

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Floorplan->recursive = -1;
    $conditions = array();

    $find_array = array(
      'conditions' => &$conditions,
    );
		$Floorplans = $this->Floorplan->find('all', $find_array);
    $this->set(array(
      'Floorplans' => $Floorplans,
      '_serialize' => array('Floorplans')
    ));
	}

}
