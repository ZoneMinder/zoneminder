<?php

App::uses('Controller', 'Controller');
App::uses('CakeEvent', 'Event');
App::uses('CakeRequest', 'Network');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('ApiQueryLogListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiQueryLogListenerTest extends CakeTestCase {

	protected $_debug;

	public function setUp() {
		parent::setUp();
		$this->_debug = Configure::read('debug');
	}

	public function tearDown() {
		parent::tearDown();
		Configure::write('debug', $this->_debug);
	}

/**
 * Test implemented events
 *
 * @covers ApiQueryLogListener::implementedEvents
 * @return void
 */
	public function testImplementedEvents() {
		$Instance = new ApiQueryLogListener(new CrudSubject());
		$result = $Instance->implementedEvents();
		$expected = array('Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 75));
		$this->assertEquals($expected, $result);
	}

/**
 * Test that calling beforeRender with debug 0
 * will not ask for request type
 *
 * @covers ApiQueryLogListener::beforeRender
 * @return void
 */
	public function testBeforeRenderDebugZero() {
		Configure::write('debug', 0);

		$Request = $this->getMock('CakeRequest', array('is'));
		$Request
			->expects($this->never())
			->method('is');

		$Instance = new ApiQueryLogListener(new CrudSubject(array('request' => $Request)));
		$Instance->beforeRender(new CakeEvent('something'));
	}

/**
 * Test that calling beforeRender with debug 1
 * will not ask for request type
 *
 * @covers ApiQueryLogListener::beforeRender
 * @return void
 */
	public function testBeforeRenderDebugOne() {
		Configure::write('debug', 1);

		$Request = $this->getMock('CakeRequest', array('is'));
		$Request
			->expects($this->never())
			->method('is');

		$Instance = new ApiQueryLogListener(new CrudSubject(array('request' => $Request)));
		$Instance->beforeRender(new CakeEvent('something'));
	}

/**
 * Test that calling beforeRender with debug 2
 * will ask for request type but won't ask for serialize configuration
 * since it's not an API request
 *
 * @covers ApiQueryLogListener::beforeRender
 * @return void
 */
	public function testBeforeRenderDebugTwo() {
		Configure::write('debug', 2);

		$Request = $this->getMock('CakeRequest', array('is'));
		$Request
			->expects($this->once())
			->method('is')
			->will($this->returnValue(false));

		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->never())
			->method('action');

		$Instance = new ApiQueryLogListener(new CrudSubject(array('request' => $Request, 'crud' => $Crud)));
		$Instance->beforeRender(new CakeEvent('something'));
	}

/**
 * Test that calling beforeRender with debug 2
 * will ask for request type and set the serialize configuration
 * since it's an API request
 *
 * @covers ApiQueryLogListener::beforeRender
 * @return void
 */
	public function testBeforeRenderDebugTwoAsApi() {
		Configure::write('debug', 2);

		$Request = $this->getMock('CakeRequest', array('is'));
		$Request
			->expects($this->once())
			->method('is')
			->will($this->returnValue(true));

		$Controller = $this->getMock('stdClass', array('set'));
		$Controller
			->expects($this->once())
			->method('set')
			->with('queryLog');

		$Action = $this->getMock('stdClass', array('config'));
		$Action
			->expects($this->once())
			->method('config')
			->with('serialize.queryLog', 'queryLog');

		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->once())
			->method('action')
			->will($this->returnValue($Action));

		$CrudSubject = new CrudSubject(array('request' => $Request, 'crud' => $Crud, 'controller' => $Controller));

		$Instance = $this->getMock('ApiQueryLogListener', array('_getQueryLogs'), array($CrudSubject));
		$Instance
			->expects($this->once())
			->method('_getQueryLogs');

		$Instance->beforeRender(new CakeEvent('something'));
	}

/**
 * Check if get query logs method works as expected
 *
 * @covers ApiQueryLogListener::_getQueryLogs
 * @return void
 */
	public function testGetQueryLogs() {
		// Implements getLog, should be called
		$defaultSource = $this->getMock('stdClass', array('getLog'));
		$defaultSource
			->expects($this->once())
			->method('getLog')
			->with(false, false)
			->will($this->returnValue(array()));

		// Does not implement getLog, should not be called
		$testSource = $this->getMock('stdClass', array());
		$testSource
			->expects($this->never())
			->method('getLog');

		$Instance = $this->getMock('ApiQueryLogListener', array('_getSources', '_getSource'), array(new CrudSubject()));
		$Instance
			->expects($this->at(0))
			->method('_getSources')
			->will($this->returnValue(array('default', 'test')));
		$Instance
			->expects($this->at(1))
			->method('_getSource')
			->with('default')
			->will($this->returnValue($defaultSource));
		$Instance
			->expects($this->at(2))
			->method('_getSource')
			->with('test')
			->will($this->returnValue($testSource));

		$Method = new ReflectionMethod($Instance, '_getQueryLogs');
		$Method->setAccessible(true);

		$result = $Method->invoke($Instance);
		$expected = array('default' => array());

		$this->assertEquals($expected, $result);
	}

}
