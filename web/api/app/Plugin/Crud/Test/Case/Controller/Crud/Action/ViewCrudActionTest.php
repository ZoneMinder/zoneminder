<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('ViewCrudAction', 'Crud.Controller/Crud/Action');
App::uses('CrudTestCase', 'Crud.Test/Support');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ViewCrudActionTest extends CrudTestCase {

/**
 * test_getGet
 *
 * Test that calling HTTP GET on an view action
 * will trigger the appropriate events
 *
 * This test assumes the best possible case
 *
 * The id provided, it's correct and it's in the db
 *
 * @return void
 */
	public function test_getGet() {
		$query = array('conditions' => array('Model.id' => 1));
		$data = array('Model' => array('id' => 1));

		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('escapeField', 'find'))
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();

		$i = 0;

		$Action = $this
			->getMockBuilder('ViewCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId', '_controller', '_model',
				'_trigger', 'viewVar', '_getFindMethod'
					))
					->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->with('first')
			->will($this->returnValue('first'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array(
				'findMethod' => 'first',
				'query' => $query,
				'id' => 1
			))
			->will($this->returnValue(new CrudSubject(array('query' => $query, 'findMethod' => 'first'))));
		$Model
			->expects($this->once())
			->method('find')
			->with('first', $query)
			->will($this->returnValue($data));
		$Action
			->expects($this->at($i++))
			->method('viewVar')
			->with()
			->will($this->returnValue('example'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterFind', array(
				'id' => 1,
				'item' => $data,
				'viewVar' => 'example',
				'success' => true
			))
			->will($this->returnValue(new CrudSubject(array(
				'success' => true,
				'viewVar' => 'example',
				'id' => 1,
				'item' => $data
			))));
		$Controller
			->expects($this->once())
			->method('set')
			->with(array('example' => $data, 'success' => true));
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(1), $Action);
	}

/**
 * test_getGetCustomViewVar
 *
 * Test that calling HTTP GET on an view action
 * will trigger the appropriate events
 *
 * Testing that setting a different viewVar actually works
 *
 * @return void
 */
	public function test_getGetCustomViewVar() {
		$query = array('conditions' => array('Model.id' => 1));
		$data = array('Model' => array('id' => 1));

		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('escapeField', 'find'))
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();

		$i = 0;

		$Action = $this
			->getMockBuilder('ViewCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
				'_validateId', '_controller', '_model',
				'_trigger', 'viewVar', '_getFindMethod'
			))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->with('first')
			->will($this->returnValue('first'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array(
				'findMethod' => 'first',
				'query' => $query,
				'id' => 1
			))
			->will($this->returnValue(new CrudSubject(array('query' => $query, 'findMethod' => 'first'))));
		$Model
			->expects($this->once())
			->method('find')
			->with('first', $query)
			->will($this->returnValue($data));
		$Action
			->expects($this->at($i++))
			->method('viewVar')
			->with()
			->will($this->returnValue('item'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterFind', array(
				'id' => 1,
				'item' => $data,
				'viewVar' => 'item',
				'success' => true
			))
			->will($this->returnValue(new CrudSubject(array(
				'item' => $data,
				'success' => true,
				'viewVar' => 'item'
			))));
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Controller
			->expects($this->once())
			->method('set')
			->with(array('item' => $data, 'success' => true));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRender');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(1), $Action);
	}

/**
 * test_getGetNotFound
 *
 * Test that calling HTTP GET on an view action
 * will trigger the appropriate events
 *
 * The ID provided is valid, but does not exist in the database
 *
 * @expectedException NotFoundException
 * @exepctedExceptionMessage Not Found
 * @exepctedExceptionCode 404
 * @return void
 */
	public function test_getGetNotFound() {
		$query = array('conditions' => array('Model.id' => 1));
		$data = array('Model' => array('id' => 1));

		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('escapeField', 'find'))
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();

		$i = 0;

		$Action = $this
			->getMockBuilder('ViewCrudAction')
			->disableOriginalConstructor()
			->setMethods(array(
					'_validateId', '_controller', '_model',
					'_trigger', 'viewVar', '_getFindMethod',
					'message'
				))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_validateId')
			->with(1)
			->will($this->returnValue(true));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Model
			->expects($this->once())
			->method('escapeField')
			->with()
			->will($this->returnValue('Model.id'));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->with('first')
			->will($this->returnValue('first'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeFind', array(
				'findMethod' => 'first',
				'query' => $query,
				'id' => 1
			))
			->will($this->returnValue(new CrudSubject(array('query' => $query, 'findMethod' => 'first'))));
		$Model
			->expects($this->once())
			->method('find')
			->with('first', $query)
			->will($this->returnValue(false));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('recordNotFound', array('id' => 1));
		$Action
			->expects($this->at($i++))
			->method('message')
			->with('recordNotFound', array('id' => 1))
			->will($this->returnValue(array('class' => 'NotFoundException', 'text' => 'NotFound', 'code' => 404)));
		$Action
			->expects($this->never())
			->method('_controller');
		$Action
			->expects($this->never())
			->method('viewVar');
		$Controller
			->expects($this->never())
			->method('set');

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(1), $Action);
	}

/**
 * test_getGetInvalidId
 *
 * Test that calling HTTP GET on an view action
 * will trigger the appropriate events
 *
 * This test assumes that the id for the view
 * action does not exist in the database
 *
 * @return void
 */
	public function test_getGetInvalidId() {
		$Action = $this
			->getMockBuilder('ViewCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_validateId', '_model', 'beforeRender', '_trigger'))
			->getMock();
		$Action
			->expects($this->once())
			->method('_validateId')
			->with(1)
			->will($this->returnValue(false));
		$Action
			->expects($this->never())
			->method('_model');
		$Action
			->expects($this->never())
			->method('_trigger');

		$this->setReflectionClassInstance($Action);
		$result = $this->callProtectedMethod('_get', array(1), $Action);
		$this->assertFalse($result);
	}

}
