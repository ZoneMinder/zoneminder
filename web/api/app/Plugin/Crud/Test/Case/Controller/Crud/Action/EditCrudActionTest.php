<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('ComponentCollection', 'Controller');
App::uses('CrudTestCase', 'Crud.Test/Support');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('EditCrudAction', 'Crud.Controller/Crud/Action');
App::uses('CrudComponent', 'Crud.Controller/Component');
App::uses('RedirectionListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class EditCrudActionTest extends CrudTestCase {

/**
 * Test the normal HTTP GET flow of _get
 *
 * @return void
 */
	public function testActionGet() {
		$query = array('conditions' => array('Model.id' => 1));
		$data = array('Model' => array('id' => 1));

		$Request = $this->getMock('CakeRequest');

		$Model = $this
			->getMock('Model', array('create', 'find', 'escapeField'));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Model
			->expects($this->once())
			->method('find')
			->with('first', $query)
			->will($this->returnValue($data));

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_validateId', '_request', '_model', '_trigger', '_getFindMethod'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
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
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->will($this->returnValue('first'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array('findMethod' => 'first', 'query' => $query))
			->will($this->returnValue(new CrudSubject(array('query' => $query, 'findMethod' => 'first'))));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterFind', array('id' => 1, 'item' => $data))
			->will($this->returnValue(new CrudSubject(array('item' => $data))));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(1), $Action);
	}

/**
 * Test that calling HTTP PUT on an edit action
 * will trigger the appropriate events and try to
 * update a record in the database
 *
 * This test assumes the best possible case
 * The id provided, it's correct and it's in the db
 *
 * @return void
 */
	public function testActionPut() {
		$Action = $this->_actionSuccess();
		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(1), $Action);
	}

/**
 * Test that calling HTTP POST on an edit action
 * will trigger the appropriate events and try to
 * update a record in the database
 *
 * This test assumes the best possible case
 * The id provided, it's correct and it's in the db
 *
 * @return void
 */
	public function testActionPost() {
		$Action = $this->_actionSuccess();
		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_post', array(1), $Action);
	}

	protected function _actionSuccess() {
		$data = array('Model' => array('id' => 1));

		$CrudSubject = new CrudSubject();

		$Request = $this->getMock('CakeRequest');
		$Request->data = $data;

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId', '_request', '_model', '_trigger',
				'_redirect', 'setFlash', 'saveOptions'
			))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
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
			->method('_model')
			->will($this->returnValue($Model));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array('query' => array('conditions' => array('Model.id' => 1)), 'findMethod' => 'count'))
			->will($this->returnValue(new CrudSubject(array('query' => array('conditions' => array('Model.id' => 1)), 'findMethod' => 'count'))));
		$Model
			->expects($this->once())
			->method('find')
			->with('count', array('conditions' => array('Model.id' => 1)))
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeSave', array('id' => 1));
		$Action
			->expects($this->at($i++))
			->method('saveOptions')
			->will($this->returnValue(array('atomic' => true)));
		$Model
			->expects($this->once())
			->method('saveAssociated')
			->with($data)
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('setFlash')
			->with('success');
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterSave', array('success' => true, 'created' => false, 'id' => 1))
			->will($this->returnValue($CrudSubject));
		$Action
			->expects($this->at($i++))
			->method('_redirect')
			->with($CrudSubject, array('action' => 'index'));
		$Action
			->expects($this->exactly(3))
			->method('_trigger');
		return $Action;
	}

/**
 * Test that calling HTTP PUT on an edit action
 * will trigger the appropriate events and try to
 * update a record in the database
 *
 * This test assumes the saveAssociated() call fails
 * The id provided, it's correct and it's in the db
 *
 * @return void
 */
	public function testActionPutSaveError() {
		$data = array('Model' => array('id' => 1));

		$CrudSubject = new CrudSubject();

		$Request = $this->getMock('CakeRequest');
		$Request->data = $data;

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId', '_request', '_model', '_trigger',
				'_redirect', 'setFlash', 'saveOptions', '_findRecord'
			))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
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
			->method('_findRecord')
			->with(1, 'count')
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeSave', array('id' => 1));
		$Action
			->expects($this->at($i++))
			->method('saveOptions')
			->will($this->returnValue(array('atomic' => true)));
		$Model
			->expects($this->once())
			->method('saveAssociated')
			->with($data)
			->will($this->returnValue(false));
		$Action
			->expects($this->at($i++))
			->method('setFlash')
			->with('error');
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterSave', array('success' => false, 'created' => false, 'id' => 1))
			->will($this->returnValue($CrudSubject));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender');
		$Action
			->expects($this->never())
			->method('_redirect');
		$Action
			->expects($this->exactly(3))
			->method('_trigger');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(1), $Action);
	}

/**
 * Test that calling HTTP GET on an edit action
 * will trigger the appropriate events
 *
 * Given an ID, we test what happens if the ID doesn't
 * exist in the database
 *
 * @expectedException NotFoundException
 * @expectedExceptionMessage Not Found
 * @expectedExceptionCode 404
 * @return void
 */
	public function testActionGetWithNonexistingId() {
		$CrudSubject = new CrudSubject();

		$query = array('conditions' => array('Model.id' => 1));

		$Request = $this->getMock('CakeRequest');

		$Model = $this
			->getMock('Model', array('escapeField', 'find'));

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId', '_request', '_model', '_trigger',
				'_redirect', 'setFlash', 'saveOptions', 'message'
			))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
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
			->method('_model')
			->will($this->returnValue($Model));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array('findMethod' => 'first', 'query' => $query))
			->will($this->returnValue(new CrudSubject(array('query' => $query, 'findMethod' => 'first'))));
		$Model
			->expects($this->once())
			->method('find')
			->with('first', $query)
			->will($this->returnValue(array()));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('recordNotFound', array('id' => 1))
			->will($this->returnValue($CrudSubject));
		$Action
			->expects($this->at($i++))
			->method('message')
			->with('recordNotFound', array('id' => 1))
			->will($this->returnValue(array('class' => 'NotFoundException', 'text' => 'Not Found', 'code' => 404)));
		$Action
			->expects($this->exactly(2))
			->method('_trigger');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(1), $Action);
	}

/**
 * Test that calling HTTP GET on an edit action
 * will trigger the appropriate events
 *
 * Given an ID, we test what happens if the ID is invalid
 *
 * @return void
 */
	public function testActionGetWithInvalidId() {
		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId'
			))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(null)
			->will($this->returnValue(false));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(null), $Action);
	}

/**
 * Test that calling HTTP PUT on an edit action
 * will trigger the appropriate events
 *
 * Given an ID, we test what happens if the ID is invalid
 *
 * @return void
 */
	public function testActionPutWithInvalidId() {
		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId'
			))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(null)
			->will($this->returnValue(false));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(null), $Action);
	}

/**
 * Test that calling HTTP GET on an edit action
 * will trigger the appropriate events
 *
 * This test assumes the best possible case
 *
 * The id provided, it's correct and it's in the db
 * Additionally the `_getFindMethod` method returns
 * something not-default
 *
 * @return void
 */
	public function testGetWithCustomFindMethod() {
		$query = array('conditions' => array('Model.id' => 1));
		$data = array('Model' => array('id' => 1));

		$Request = $this->getMock('CakeRequest');

		$Model = $this
			->getMock('Model', array('create', 'find', 'escapeField'));

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_validateId', '_request', '_model', '_trigger', '_getFindMethod'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
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
			->method('_model')
			->will($this->returnValue($Model));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->will($this->returnValue('first'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array('findMethod' => 'first', 'query' => $query))
			->will($this->returnValue(new CrudSubject(array('query' => $query, 'findMethod' => 'myCustomQuery'))));
		$Model
			->expects($this->once())
			->method('find')
			->with('myCustomQuery', $query)
			->will($this->returnValue($data));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterFind', array('id' => 1, 'item' => $data))
			->will($this->returnValue(new CrudSubject(array('item' => $data))));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(1), $Action);
	}

/**
 * test_findRecordDefault
 *
 * @return void
 */
	public function test_findRecordDefault() {
		$query = array('conditions' => array('Model.id' => 1));
		$findParams = array('findMethod' => 'special', 'query' => $query);

		$i = 0;
		$Model = $this->getMock('Model', array('escapeField', 'find'));
		$Model
			->expects($this->at($i++))
			->method('escapeField')
			->will($this->returnValue('Model.id'));
		$Model
			->expects($this->at($i++))
			->method('find')
			->with('special', $query);

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_getFindMethod', '_trigger'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->will($this->returnValue('special'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', $findParams)
			->will($this->returnValue(new CrudSubject($findParams)));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_findRecord', array(1), $Action);
	}

/**
 * test_findRecordOverride
 *
 * @return void
 */
	public function test_findRecordOverride() {
		$query = array('conditions' => array('Model.id' => 1));
		$findParams = array('findMethod' => 'count', 'query' => $query);

		$i = 0;
		$Model = $this->getMock('Model', array('escapeField', 'find'));
		$Model
			->expects($this->at($i++))
			->method('escapeField')
			->will($this->returnValue('Model.id'));
		$Model
			->expects($this->at($i++))
			->method('find')
			->with('count', $query);

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_getFindMethod', '_trigger'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->never())
			->method('_getFindMethod');
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', $findParams)
			->will($this->returnValue(new CrudSubject($findParams)));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_findRecord', array(1, 'count'), $Action);
	}

/**
 * testPutSetsIdFromUrl
 *
 * @return void
 */
	public function testPutSetsIdFromUrl() {
		$query = array('conditions' => array('Model.id' => 1));
		$findParams = array('findMethod' => 'count', 'query' => $query);

		$data = array('Model' => array('some' => 'data'));

		$Request = $this->getMock('CakeRequest');
		$Request->data = $data;
		$Request->params['pass'][0] = 1;

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->getMock();

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = $j = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_validateId', '_request', '_model', '_findRecord', '_trigger', 'setFlash'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->will($this->returnValue(true));
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
			->method('_findRecord')
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeSave', array('id' => 1))
			->will($this->returnValue(new CrudSubject(array('id' => 1))));
		$Model
			->expects($this->at($j++))
			->method('saveAssociated')
			->with(array('Model' => array('id' => 1, 'some' => 'data')), array('validate' => 'first', 'atomic' => true));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(1), $Action);
	}

/**
 * testPutSetsIdFromUrlWithAbreviatedData
 *
 * @return void
 */
	public function testPutSetsIdFromUrlWithAbreviatedData() {
		$query = array('conditions' => array('Model.id' => 1));
		$findParams = array('findMethod' => 'count', 'query' => $query);

		$data = array('some' => 'data');

		$Request = $this->getMock('CakeRequest');
		$Request->data = $data;
		$Request->params['pass'][0] = 1;

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->getMock();

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = $j = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_validateId', '_request', '_model', '_findRecord', '_trigger', 'setFlash'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->will($this->returnValue(true));
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
			->method('_findRecord')
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeSave', array('id' => 1))
			->will($this->returnValue(new CrudSubject(array('id' => 1))));
		$Model
			->expects($this->at($j++))
			->method('saveAssociated')
			->with(array('id' => 1, 'some' => 'data'), array('validate' => 'first', 'atomic' => true));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(1), $Action);
	}

/**
 * test_validateId
 *
 * @return void
 */
	public function test_validateId() {
		$Request = $this->getMock('CakeRequest');
		$Request->data = null;
		$Request->params['pass'][0] = 1;

		$i = $j = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger', 'message'))
			->getMock();
		$Action->config('validateId', false);
		$Action
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($Request));

		$this->setReflectionClassInstance($Action);
		$return = $this->callProtectedMethod('_validateId', array(1), $Action);
		$this->assertTrue($return, 'If there\'s no data, there should be no data check');
	}

/**
 * test_validateIdMatches
 *
 * @return void
 */
	public function test_validateIdMatches() {
		$Request = $this->getMock('CakeRequest');
		$Request->data = array('Model' => array('id' => '1'));
		$Request->params['pass'][0] = 1;

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = $j = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger', 'message'))
			->getMock();
		$Action->config('validateId', false);
		$Action
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($Request));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($Model));

		$this->setReflectionClassInstance($Action);
		$return = $this->callProtectedMethod('_validateId', array(1), $Action);
		$this->assertTrue($return, 'If there\'s data and it matches, there should be no exception');
	}

/**
 * test_validateIdManipulated
 *
 * @expectedException BadRequestException
 * @expectedExceptionMessage Invalid id
 * @expectedExceptionCode 400
 *
 * @return void
 */
	public function test_validateIdManipulated() {
		$data = array('Model' => array('id' => 'manipulated', 'some' => 'data'));

		$Request = new CakeRequest();
		$Request->data = $data;
		$Request->params['pass'][0] = 1;

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = $j = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger', 'message'))
			->getMock();
		$Action->config('validateId', false);
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
			->with('invalidId', array('id' => 'manipulated'));
		$Action
			->expects($this->at($i++))
			->method('message')
			->with('invalidId')
			->will($this->returnValue(array('class' => 'BadRequestException', 'code' => 400, 'text' => 'Invalid id')));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_validateId', array(1), $Action);
	}

/**
 * test_validateIdManipulatedShortData
 *
 * @expectedException BadRequestException
 * @expectedExceptionMessage Invalid id
 * @expectedExceptionCode 400
 *
 * @return void
 */
	public function test_validateIdManipulatedShortData() {
		$data = array('id' => 'manipulated', 'some' => 'data');

		$Request = new CakeRequest();
		$Request->data = $data;
		$Request->params['pass'][0] = 1;

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$i = $j = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_request', '_model', '_trigger', 'message'))
			->getMock();
		$Action->config('validateId', false);
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
			->with('invalidId', array('id' => 'manipulated'));
		$Action
			->expects($this->at($i++))
			->method('message')
			->with('invalidId')
			->will($this->returnValue(array('class' => 'BadRequestException', 'code' => 400, 'text' => 'Invalid id')));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_validateId', array(1), $Action);
	}

/**
 * Verify that _injectPrimaryKey is called, and the result is passed to saveAssociated
 *
 * @return void
 */
	public function test_injectPrimaryKeyIsCalled() {
		$CrudSubject = new CrudSubject();

		$Request = $this->getMock('CakeRequest');
		$Request->data = array('fake', 'input', 'data');

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();
		$Model
			->expects($this->any())
			->method('saveAssociated')
			->with(array('id' => 1, '_injectPrimaryKey' => 'return'))
			->will($this->returnValue(false));

		$i = 0;
		$Action = $this
			->getMockBuilder('EditCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_validateId', '_request', '_model', '_findRecord', '_injectPrimaryKey', 'setFlash', '_trigger'))
			->getMock();
		$Action
			->expects($this->any())
			->method('_validateId')
			->will($this->returnValue(true));
		$Action
			->expects($this->any())
			->method('_request')
			->will($this->returnValue($Request));
		$Action
			->expects($this->any())
			->method('_model')
			->will($this->returnValue($Model));
		$Action
			->expects($this->any())
			->method('_findRecord')
			->will($this->returnValue(true));
		$Action
			->expects($this->once())
			->method('_injectPrimaryKey')
			->will($this->returnValue(array('id' => 1, '_injectPrimaryKey' => 'return')));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_put', array(1), $Action);
	}

/**
 * test_injectPrimaryKey
 *
 * Check that the model id is injected into the right place
 *
 * @dataProvider idInjectionProvider
 * @param array $data
 * @param array $expectation
 */
	public function test_injectPrimaryKey($data, $expectation = null) {
		if (!$expectation) {
			$expectation = $data;
		}

		$Model = $this
			->getMockBuilder('Model')
			->setMethods(array('saveAssociated', 'find', 'escapeField'))
			->setConstructorArgs(array(array('name' => 'Model')))
			->getMock();

		$Action = new EditCrudAction(new CrudSubject());

		$this->setReflectionClassInstance($Action);
		$return = $this->callProtectedMethod('_injectPrimaryKey', array($data, 1, $Model), $Action);
		$this->assertSame($expectation, $return, '"id" should be injected in the right place in the save data');
	}

/**
 * idInjectionProvider
 *
 * Returns sets of data to use in tests.
 * 	input
 * 	expected result (optional, uses input if absent)
 *
 * @return array
 */
	public function idInjectionProvider() {
		return array(
			array(
				array(),
				array('id' => 1)
			),
			array(
				array('Model' => array('id' => 1, 'some' => 'update'))
			),
			array(
				array('Model' => array('id' => 'cheating', 'some' => 'update')),
				array('Model' => array('id' => 1, 'some' => 'update'))
			),
			array(
				array('Model' => array('some' => 'update')),
				array('Model' => array('some' => 'update', 'id' => 1))
			),
			array(
				array('id' => 1, 'some' => 'update')
			),
			array(
				array('some' => 'update'),
				array('some' => 'update', 'id' => 1)
			),
			array(
				array('something' => 'else', 'Model' => array('some' => 'update')),
				array('something' => 'else', 'Model' => array('some' => 'update', 'id' => 1)),
			),
			array(
				array('Category' => array('Category' => array(1))),
				array(
					'Category' => array('Category' => array(1)),
					'Model' => array('id' => 1)
				),
			),

		);
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
		$Request->params['action'] = 'edit';
		$Request->data = array('_add' => 'something');

		$Controller->__construct($Request, new CakeResponse);

		$Crud->__construct(new ComponentCollection);
		$Crud->initialize($Controller);
		$Crud->mapAction('edit', 'edit');

		$Crud->addListener('redirect');
		$Crud->listener('redirect');

		$Action = $Crud->action('edit');

		$CrudSubject = $Crud->getSubject();
		$CrudSubject->success = true;
		$CrudSubject->created = true;
		$CrudSubject->id = 69;

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_redirect', array($CrudSubject, array('action' => 'index')), $Action);

		$expected = array('action' => 'add');
		$this->assertEquals($expected, $CrudSubject->url);
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
		$Request->params['action'] = 'edit';
		$Request->data = array('_edit' => 'something');

		$Controller->__construct($Request, new CakeResponse);

		$Crud->__construct(new ComponentCollection);
		$Crud->initialize($Controller);
		$Crud->mapAction('edit', 'edit');

		$Crud->addListener('redirect');
		$Crud->listener('redirect');

		$Action = $Crud->action('edit');

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
