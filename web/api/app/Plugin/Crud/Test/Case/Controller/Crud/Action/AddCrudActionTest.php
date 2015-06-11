<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('ComponentCollection', 'Controller');
App::uses('CrudTestCase', 'Crud.Test/Support');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('AddCrudAction', 'Crud.Controller/Crud/Action');
App::uses('CrudComponent', 'Crud.Controller/Component');
App::uses('RedirectionListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class AddCrudActionTest extends CrudTestCase {

/**
 * Test the normal HTTP GET flow of _get
 *
 * @covers AddCrudAction::_get
 * @return void
 */
	public function testActionGet() {
		$Request = $this->getMock('CakeRequest');

		$Model = $this->getMock('Model', array('create'));
		$Model
			->expects($this->once())
			->method('create');

		$Action = $this
			->getMockBuilder('AddCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger'))
			->getMock();

		$i = 0;
		$Action
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($Request));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender', array('success' => false));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(), $Action);
	}

/**
 * Test that calling HTTP POST on an add action
 * will trigger multiple events on success
 *
 * @covers AddCrudAction::_post
 * @return void
 */
	public function testActionPostSuccess() {
		$Action = $this->_actionSuccess();
		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_post', array(), $Action);
	}

/**
 * Test that calling HTTP PUT on an add action
 * will trigger multiple events on success
 *
 * @covers AddCrudAction::_put
 * @return void
 */
	public function testActionPutSuccess() {
		$Action = $this->_actionSuccess();
		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(), $Action);
	}

/**
 * Test that calling HTTP PUT on an add action
 * will trigger multiple events on success
 *
 * @covers AddCrudAction::_put
 * @return void
 */
	public function testActionPutSuccessWithDifferentSaveMethod() {
		$Action = $this->_actionSuccess('saveAll');
		$Action->saveMethod('saveAll');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(), $Action);
	}

	protected function _actionSuccess($saveMethod = 'saveAssociated') {
		$Request = $this->getMock('CakeRequest');
		$Request->data = array('Post' => array('name' => 'Hello World'));

		$Model = $this->getMock('Model', array($saveMethod));
		$Model
			->expects($this->once())
			->method($saveMethod)
			->with($Request->data)
			->will($this->returnCallback(function() use ($Model) {
				$Model->id = 1;
				return true;
			}));

		$Action = $this
			->getMockBuilder('AddCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger', 'setFlash', '_redirect'))
			->getMock();

		$AfterSaveSubject = new CrudSubject();

		$i = 0;
		$Action
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($Request));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeSave');
		$Action
			->expects($this->at($i++))
			->method('setFlash')
			->with('success');
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterSave', array('success' => true, 'created' => true, 'id' => 1))
			->will($this->returnValue($AfterSaveSubject));
		$Action
			->expects($this->at($i++))
			->method('_redirect')
			->with($AfterSaveSubject, array('action' => 'index'));
		return $Action;
	}

/**
 * Test that calling HTTP POST on an add action
 * will trigger multiple events on error
 *
 * @covers AddCrudAction::_post
 * @return void
 */
	public function testActionPostError() {
		$Request = $this->getMock('CakeRequest');
		$Request->data = array('Post' => array('name' => 'Hello World'));

		$Model = $this->getMock('Model', array('saveAssociated'));
		$Model->data = array('model' => true);

		$Action = $this
			->getMockBuilder('AddCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger', 'setFlash'))
			->getMock();

		$AfterSaveSubject = new CrudSubject();

		$i = 0;
		$Action
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($Request));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeSave');
		$Model
			->expects($this->once())
			->method('saveAssociated')
			->with($Request->data)
			->will($this->returnValue(false));
		$Action
			->expects($this->at($i++))
			->method('setFlash')
			->with('error');
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterSave', array('success' => false, 'created' => false))
			->will($this->returnValue($AfterSaveSubject));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender', $AfterSaveSubject);

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_post', array(), $Action);

		$result = $Request->data;
		$expected = $Request->data;
		$expected['model'] = true;
		$this->assertEquals($expected, $result, 'The Request::$data and Model::$data was not merged');
	}

/**
 * Test redirection logic for "add"
 *
 * @return void
 */
	public function testRedirectListenerWithAdd() {
		$Crud = $this
			->getMockBuilder('CrudComponent')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('redirect'))
			->getMock();

		$Request = new CakeRequest;
		$Request->params['action'] = 'add';
		$Request->data = array('_add' => 'something');

		$Controller->__construct($Request, new CakeResponse);

		$Crud->__construct(new ComponentCollection);
		$Crud->initialize($Controller);
		$Crud->mapAction('add', 'add');

		$Crud->addListener('redirect');
		$Crud->listener('redirect');

		$Action = $Crud->action('add');

		$CrudSubject = $Crud->getSubject();
		$CrudSubject->success = true;
		$CrudSubject->created = true;
		$CrudSubject->id = 69;

		$Controller
			->expects($this->once())
			->method('redirect')
			->with(array('action' => 'add'));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_redirect', array($CrudSubject, array('action' => 'index')), $Action);
	}

/**
 * Test redirection logic for "edit"
 *
 * @return void
 */
	public function testRedirectListenerWithEdit() {
		$Crud = $this
			->getMockBuilder('CrudComponent')
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('redirect'))
			->getMock();

		$Request = new CakeRequest;
		$Request->params['action'] = 'add';
		$Request->data = array('_edit' => 'something');

		$Controller->__construct($Request, new CakeResponse);

		$Crud->__construct(new ComponentCollection);
		$Crud->initialize($Controller);
		$Crud->mapAction('add', 'add');

		$Crud->addListener('redirect');
		$Crud->listener('redirect');

		$Action = $Crud->action('add');

		$CrudSubject = $Crud->getSubject();
		$CrudSubject->success = true;
		$CrudSubject->created = true;
		$CrudSubject->id = 69;

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_redirect', array($CrudSubject, array('action' => 'index')), $Action);

		$expected = array('action' => 'edit', 69);
		$this->assertEquals($expected, $CrudSubject->url);
	}

}
