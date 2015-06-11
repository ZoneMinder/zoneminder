<?php

App::uses('Controller', 'Controller');
App::uses('CakeEvent', 'Event');
App::uses('CakeRequest', 'Network');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('ApiPaginationListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiPaginationListenerTest extends CakeTestCase {

/**
 * Test implemented events
 *
 * @covers ApiPaginationListener::implementedEvents
 * @return void
 */
	public function testImplementedEvents() {
		$Instance = new ApiPaginationListener(new CrudSubject());
		$result = $Instance->implementedEvents();
		$expected = array('Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 75));
		$this->assertEquals($expected, $result);
	}

/**
 * Test that non-API requests don't get processed
 *
 * @covers ApiPaginationListener::beforeRender
 * @return void
 */
	public function testBeforeRenderNotApi() {
		$Request = $this->getMock('CakeRequest', array('is'));
		$Request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(false));

		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->never())
			->method('action');

		$Instance = new ApiPaginationListener(new CrudSubject(array('request' => $Request, 'crud' => $Crud)));
		$Instance->beforeRender(new CakeEvent('something'));
	}

/**
 * Test that API requests do not get processed
 * if there is no pagination data
 *
 * @covers ApiPaginationListener::beforeRender
 * @return void
 */
	public function testBeforeRenderNoPaginationData() {
		$Request = $this->getMock('CakeRequest', array('is'));
		$Request->paging = array('MyModel' => array());
		$Request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->never())
			->method('action');

		$CrudSubject = new CrudSubject(array('request' => $Request, 'crud' => $Crud, 'modelClass' => 'AnotherModel'));

		$Instance = new ApiPaginationListener($CrudSubject);
		$Instance->beforeRender(new CakeEvent('something', $CrudSubject));
	}

/**
 * Test that API requests do not get processed
 * if there if pagination data is NULL
 *
 * @covers ApiPaginationListener::beforeRender
 * @return void
 */
	public function testBeforeRenderPaginationDataIsNull() {
		$Request = $this->getMock('CakeRequest', array('is'));
		$Request->paging = null;
		$Request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->never())
			->method('action');

		$CrudSubject = new CrudSubject(array('request' => $Request, 'crud' => $Crud, 'modelClass' => 'AnotherModel'));

		$Instance = new ApiPaginationListener($CrudSubject);
		$Instance->beforeRender(new CakeEvent('something', $CrudSubject));
	}

/**
 * Test that API requests do get processed
 * if there is pagination data
 *
 * @covers ApiPaginationListener::beforeRender
 * @return void
 */
	public function testBeforeRenderWithPaginationData() {
		$Request = $this->getMock('CakeRequest', array('is'));
		$Request->paging = array('MyModel' => array(
			'pageCount' => 10,
			'page' => 2,
			'nextPage' => true,
			'prevPage' => true,
			'count' => 100,
			'limit' => 10
		));

		$expected = array(
			'page_count' => 10,
			'current_page' => 2,
			'has_next_page' => true,
			'has_prev_page' => true,
			'count' => 100,
			'limit' => 10
		);

		$Request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$Controller = $this->getMock('stdClass', array('set'));
		$Controller
			->expects($this->once())
			->method('set')
			->with('pagination', $expected);

		$Action = $this->getMock('stdClass', array('config'));
		$Action
			->expects($this->once())
			->method('config')
			->with('serialize.pagination', 'pagination');

		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->once())
			->method('action')
			->will($this->returnValue($Action));

		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'crud' => $Crud,
			'controller' => $Controller,
			'modelClass' => 'MyModel'
		));

		$Instance = new ApiPaginationListener($CrudSubject);
		$Instance->beforeRender(new CakeEvent('something', $CrudSubject));
	}
}
