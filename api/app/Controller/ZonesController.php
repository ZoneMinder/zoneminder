<?php
App::uses('AppController', 'Controller');
/**
 * Zones Controller
 *
 * @property Zone $Zone
 * @property PaginatorComponent $Paginator
 */
class ZonesController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Zone->recursive = -1;
        	$zones = $this->Zone->find('all');
        	$this->set(array(
        	    'zones' => $zones,
        	    '_serialize' => array('zones')
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
		$this->Zone->recursive = -1;
		if (!$this->Zone->exists($id)) {
			throw new NotFoundException(__('Invalid zone'));
		}
		$options = array('conditions' => array('Zone.' . $this->Zone->primaryKey => $id));
		$zone = $this->Zone->find('first', $options);
		$this->set(array(
			'zone' => $zone,
			'_serialize' => array('zone')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Zone->create();
			if ($this->Zone->save($this->request->data)) {
				return $this->flash(__('The zone has been saved.'), array('action' => 'index'));
			}
		}
		$monitors = $this->Zone->Monitor->find('list');
		$this->set(compact('monitors'));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		$this->Zone->id = $id;

		if (!$this->Zone->exists($id)) {
			throw new NotFoundException(__('Invalid zone'));
		}
		if ($this->request->is(array('post', 'put'))) {
			if ($this->Zone->save($this->request->data)) {
				return $this->flash(__('The zone has been saved.'), array('action' => 'index'));
			}
		} else {
			$options = array('conditions' => array('Zone.' . $this->Zone->primaryKey => $id));
			$this->request->data = $this->Zone->find('first', $options);
		}
		$monitors = $this->Zone->Monitor->find('list');
		$this->set(compact('monitors'));
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
		if (!$this->Zone->exists()) {
			throw new NotFoundException(__('Invalid zone'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Zone->delete()) {
			return $this->flash(__('The zone has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The zone could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}}
