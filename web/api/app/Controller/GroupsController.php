<?php
App::uses('AppController', 'Controller');
/**
 * Groups Controller
 *
 * @property Group $Group
 * @property PaginatorComponent $Paginator
 */
class GroupsController extends AppController {


/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');


public function beforeFilter() {
	parent::beforeFilter();
        $canView = $this->Session->Read('groupsPermission');
	if ($canView =='None')
	{
		throw new UnauthorizedException(__('Insufficient Privileges'));
		return;
	}

}


/**
 * index method
 *
 * @return void
 */
	public function index() {
                $this->Group->recursive = 0;
        	$groups = $this->Group->find('all');
        	$this->set(array(
        	    'groups' => $groups,
        	    '_serialize' => array('groups')
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
		$this->Group->recursive = 0;
		if (!$this->Group->exists($id)) {
			throw new NotFoundException(__('Invalid group'));
		}
		
		$group = $this->Group->find('first');
		$this->set(array(
			'group' => $group,
			'_serialize' => array('group')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {

			if ($this->Session->Read('groupPermission') != 'Edit')
			{
				 throw new UnauthorizedException(__('Insufficient privileges'));
				return;
			}

			$this->Group->create();
			if ($this->Group->save($this->request->data)) {
				return $this->flash(__('The group has been saved.'), array('action' => 'index'));
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
		$this->Group->id = $id;

		if (!$this->Group->exists($id)) {
			throw new NotFoundException(__('Invalid group'));
		}
		if ($this->Session->Read('groupPermission') != 'Edit')
		{
			 throw new UnauthorizedException(__('Insufficient privileges'));
			return;
		}
		if ($this->Group->save($this->request->data)) {
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
		$this->Group->id = $id;
		if (!$this->Group->exists()) {
			throw new NotFoundException(__('Invalid group'));
		}
		if ($this->Session->Read('groupPermission') != 'Edit')
		{
			 throw new UnauthorizedException(__('Insufficient privileges'));
			return;
		}
		$this->request->allowMethod('post', 'delete');


		if ($this->Group->delete()) {
			return $this->flash(__('The group has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The group could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}

}

