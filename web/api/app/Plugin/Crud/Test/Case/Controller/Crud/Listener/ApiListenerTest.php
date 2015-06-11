<?php

App::uses('CakeEvent', 'Event');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');
App::uses('RequestHandlerComponent', 'Controller/Component');
App::uses('CrudComponent', 'Crud.Controller/Component');
App::uses('ApiListener', 'Crud.Controller/Crud/Listener');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('IndexCrudAction', 'Crud.Controller/Crud/Action');
App::uses('CrudTestCase', 'Crud.Test/Support');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiListenerTest extends CrudTestCase {

	protected $_config;

	public function setUp() {
		parent::setUp();

		$this->_config = Configure::read();
	}

	public function tearDown() {
		parent::tearDown();
		Configure::write($this->_config);
		CakePlugin::unload('TestPlugin');
	}

/**
 * testBeforeHandle
 *
 * @return void
 */
	public function testBeforeHandle() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_request', 'registerExceptionHandler', '_checkRequestMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at(0))
			->method('_request')
			->will($this->returnValue($request));
		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));
		$listener
			->expects($this->at(1))
			->method('registerExceptionHandler');
		$listener
			->expects($this->at(1))
			->method('_checkRequestMethods');

		$listener->beforeHandle(new CakeEvent('Crud.beforeHandle'));
	}

/**
 * testSetup
 *
 * @return void
 */
	public function testSetup() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('setupDetectors', 'registerExceptionHandler'))
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at(0))
			->method('setupDetectors');

		$listener
			->expects($this->at(1))
			->method('registerExceptionHandler');

		$listener->setup();
	}

/**
 * testBeforeHandleNotApi
 *
 * @return void
 */
	public function testBeforeHandleNotApi() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_request', '_controller', 'registerExceptionHandler', '_checkRequestMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('getEventManager'))
			->getMock();

		$manager = $this
			->getMockBuilder('EventManager')
			->setMethods(array('detach'))
			->getMock();

		$listener
			->expects($this->at(0))
			->method('_request')
			->will($this->returnValue($request));
		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(false));
		$listener
			->expects($this->at(1))
			->method('_controller')
			->will($this->returnValue($controller));
		$controller
			->expects($this->at(0))
			->method('getEventManager')
			->will($this->returnValue($manager));
		$manager
			->expects($this->at(0))
			->method('detach')
			->with($listener, 'Crud.setFlash');
		$manager
			->expects($this->at(1))
			->method('detach')
			->with($listener, 'Crud.beforeRender');
		$manager
			->expects($this->at(2))
			->method('detach')
			->with($listener, 'Crud.beforeRedirect');

		$listener
			->expects($this->never())
			->method('registerExceptionHandler');
		$listener
			->expects($this->never())
			->method('_checkRequestMethods');

		$listener->beforeHandle(new CakeEvent('Crud.beforeHandle'));
	}

/**
 * Test response method
 *
 * @return void
 */
	public function testResponse() {
		$request = $this->getMock('CakeRequest', array('is'));
		$response = $this->getMock('CakeResponse');

		$action = $this->getMock('IndexCrudAction', array('config'), array(new CrudSubject()));

		$subject = $this->getMock('CrudSubject');
		$subject->success = true;

		$event = new CakeEvent('Crud.afterSave', $subject);

		$i = 0;

		$listener = $this->getMock('ApiListener', array('_request', '_action', 'render'), array($subject));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->with()
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api.success')
			->will($this->returnValue(array('code' => 200)));
		$listener
			->expects($this->at($i++))
			->method('render')
			->with($subject)
			->will($this->returnValue($response));
		$response
			->expects($this->at(0))
			->method('statusCode')
			->with(200);

		$result = $listener->respond($event);
		$this->assertSame($response, $result);
	}

/**
 * Test response method with exception config
 *
 * @return void
 */
	public function testResponseWithExceptionConfig() {
		$request = $this->getMock('CakeRequest', array('is'));
		$response = $this->getMock('CakeResponse');

		$action = $this->getMock('IndexCrudAction', array('config'), array(new CrudSubject()));

		$subject = $this->getMock('CrudSubject');
		$subject->success = true;

		$event = new CakeEvent('Crud.afterSave', $subject);

		$i = 0;

		$listener = $this->getMock('ApiListener', array('_request', '_action', 'render', '_exceptionResponse'), array($subject));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->with()
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api.success')
			->will($this->returnValue(array('exception' => true)));
		$listener
			->expects($this->at($i++))
			->method('_exceptionResponse')
			->with(true);
		$listener
			->expects($this->never())
			->method('render');
		$response
			->expects($this->never())
			->method('statusCode');

		$listener->respond($event);
	}

/**
 * Test default configuration
 *
 * @return void
 */
	public function testDefaultConfiguration() {
		$listener = new ApiListener(new CrudSubject());
		$expected = array(
			'viewClasses' => array(
				'json' => 'Crud.CrudJson',
				'xml' => 'Crud.CrudXml'
			),
			'detectors' => array(
				'json' => array('ext' => 'json', 'accepts' => 'application/json'),
				'xml' => array('ext' => 'xml', 'accepts' => 'text/xml')
			),
			'exception' => array(
				'type' => 'default',
				'class' => 'BadRequestException',
				'message' => 'Unknown error',
				'code' => 0
			)
		);
		$result = $listener->config();
		$this->assertEquals($expected, $result);
	}

/**
 * Tests implemented events
 *
 * @return void
 */
	public function testImplementeEvents() {
		$subject = $this->getMock('CrudSubject');
		$apiListener = new ApiListener($subject);
		$expected = array(
			'Crud.beforeHandle' => array('callable' => 'beforeHandle', 'priority' => 10),
			'Crud.setFlash' => array('callable' => 'setFlash', 'priority' => 5),

			'Crud.beforeRender' => array('callable' => 'respond', 'priority' => 100),
			'Crud.beforeRedirect' => array('callable' => 'respond', 'priority' => 100)
		);
		$this->assertEquals($expected, $apiListener->implementedEvents());
	}

/**
 * Data provider for test_exceptionResponse
 *
 * @return array
 */
	public function data_exceptionResponse() {
		return array(
			'default configuration' => array(
				array(),
				'BadRequestException',
				'Unknown error',
				0
			),

			'change exception class' => array(
				array('class' => 'CakeException'),
				'CakeException',
				'Unknown error',
				0
			),

			'change exception code' => array(
				array('code' => 10),
				'BadRequestException',
				'Unknown error',
				10
			),

			'change exception message' => array(
				array('message' => 'epic message'),
				'BadRequestException',
				'epic message',
				10
			),

			'Validate case #1 - no validation errors' => array(
				array('class' => 'CrudValidationException', 'type' => 'validate'),
				'CrudValidationException',
				'0 validation errors occurred',
				0
			),

			'Validate case #2 - one validation error' => array(
				array('class' => 'CrudValidationException', 'type' => 'validate'),
				'CrudValidationException',
				'A validation error occurred',
				0,
				array(array('id' => 'hello world'))
			),

			'Validate case #3 - two validation errors' => array(
				array('class' => 'CrudValidationException', 'type' => 'validate'),
				'CrudValidationException',
				'2 validation errors occurred',
				0,
				array(array('id' => 'hello world', 'name' => 'fail me'))
			)
		);
	}

/**
 * Test _exceptionResponse
 *
 * @dataProvider data_exceptionResponse
 * @param array $apiConfig
 * @param string $exceptionClass
 * @param string $exceptionMessage
 * @param integer $exceptionCode
 * @param array $validationErrors
 * @return void
 */
	public function test_exceptionResponse($apiConfig, $exceptionClass, $exceptionMessage, $exceptionCode, $validationErrors = array()) {
		$listener = $this->getMock('ApiListener', array('_validationErrors'), array(new CrudSubject()));

		if (isset($apiConfig['type']) && $apiConfig['type'] === 'validate') {
			$listener->expects($this->once())->method('_validationErrors')->with()->will($this->returnValue($validationErrors));
		} else {
			$listener->expects($this->never())->method('_validationErrors');
		}

		$this->expectException($exceptionClass, $exceptionMessage, $exceptionCode);

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_exceptionResponse', array($apiConfig), $listener);
	}

/**
 * Test render
 *
 * @return void
 */
	public function testRender() {
		$listener = $this->getMockBuilder('ApiListener')
			->setMethods(array('injectViewClasses', '_ensureSuccess', '_ensureData', '_ensureSerialize', '_controller'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject();

		$requestHandler = $this->getMockBuilder('RequestHandlerComponent')
			->setMethods(array('renderAs'))
			->disableOriginalConstructor()
			->getMock();
		$controller = $this->getMockBuilder('Controller')
			->setMethods(array('render'))
			->disableOriginalConstructor()
			->getMock();
		$controller->RequestHandler = $requestHandler;
		$controller->RequestHandler->ext = 'json';

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('injectViewClasses')
			->with();
		$listener
			->expects($this->at($i++))
			->method('_ensureSuccess')
			->with($subject);
		$listener
			->expects($this->at($i++))
			->method('_ensureData')
			->with($subject);
		$listener
			->expects($this->at($i++))
			->method('_ensureSerialize')
			->with();
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($controller));
		$requestHandler
			->expects($this->once())
			->method('renderAs')
			->with($controller, 'json');
		$controller
			->expects($this->once())
			->method('render')
			->with();

		$listener->render($subject);
	}

/**
 * test_ensureSerializeWithViewVar
 *
 * @return void
 */
	public function test_ensureSerializeWithViewVar() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_action', '_controller'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexCrudAction')
			->setMethods(array('config', 'viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('viewVar')
			->will($this->returnValue('items'));
		$controller
			->expects($this->once())
			->method('set')
			->with('_serialize', array('success', 'data' => 'items'));

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_ensureSerialize', array(), $listener);
	}

/**
 * test_ensureSerializeAlreadySet
 *
 * @return void
 */
	public function test_ensureSerializeAlreadySet() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_action', '_controller'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$controller->viewVars['_serialize'] = 'hello world';

		$action = $this
			->getMockBuilder('IndexCrudAction')
			->setMethods(array('config', 'viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->never())
			->method('_action');
		$action
			->expects($this->never())
			->method('viewVar');
		$controller
			->expects($this->never())
			->method('set');

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_ensureSerialize', array(), $listener);
	}

/**
 * test_ensureSerializeWithViewVarChanged
 *
 * @return void
 */
	public function test_ensureSerializeWithViewVarChanged() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_action', '_controller'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexCrudAction')
			->setMethods(array('config', 'viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('viewVar')
			->will($this->returnValue('helloWorld'));
		$controller
			->expects($this->once())
			->method('set')
			->with('_serialize', array('success', 'data' => 'helloWorld'));

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_ensureSerialize', array(), $listener);
	}

/**
 * test_ensureSerializeWithoutViewVar
 *
 * @return void
 */
	public function test_ensureSerializeWithoutViewVar() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_action', '_controller'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('AddCrudAction')
			->setMethods(array('config'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$controller
			->expects($this->once())
			->method('set')
			->with('_serialize', array('success', 'data'));

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_ensureSerialize', array(), $listener);
	}

/**
 * test_ensureSuccess
 *
 * @return void
 */
	public function test_ensureSuccess() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject(array('success' => true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$controller
			->expects($this->once())
			->method('set')
			->with('success', true);

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_ensureSuccess', array($subject), $listener);
	}

/**
 * test_ensureData
 *
 * @return void
 */
	public function test_ensureData() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller', '_action'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(array('config'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject(array('success' => true));

		$config = array();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api.success')
			->will($this->returnValue($config));
		$controller
			->expects($this->once())
			->method('set')
			->with('data', array());

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_ensureData', array($subject), $listener);
	}

/**
 * test_ensureDataSubject
 *
 * @return void
 */
	public function test_ensureDataSubject() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller', '_action'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(array('config'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject(array('success' => true, 'id' => 1, 'modelClass' => 'MyModel'));

		$config = array('data' => array(
			'subject' => array(
				'{modelClass}.id' => 'id',
				'modelClass'
			)
		));

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api.success')
			->will($this->returnValue($config));
		$controller
			->expects($this->once())
			->method('set')
			->with('data', array('modelClass' => 'MyModel', 'MyModel' => array('id' => 1)));

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_ensureData', array($subject), $listener);
	}

/**
 * test_ensureDataRaw
 *
 * @return void
 */
	public function test_ensureDataRaw() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller', '_action'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(array('config'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject(array('success' => true, 'id' => 1, 'modelClass' => 'MyModel'));

		$config = array('data' => array('raw' => array('{modelClass}.id' => 1)));

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api.success')
			->will($this->returnValue($config));
		$controller
			->expects($this->once())
			->method('set')
			->with('data', array('MyModel' => array('id' => 1)));

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_ensureData', array($subject), $listener);
	}

/**
 * test_ensureDataError
 *
 * @return void
 */
	public function test_ensureDataError() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller', '_action'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(array('config'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject(array('success' => false));

		$config = array();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api.error')
			->will($this->returnValue($config));
		$controller
			->expects($this->once())
			->method('set')
			->with('data', array());

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_ensureData', array($subject), $listener);
	}

/**
 * test_ensureDataExists
 *
 * @return void
 */
	public function test_ensureDataExists() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller', '_action'))
			->disableOriginalConstructor()
			->getMock();

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$controller->viewVars['data'] = true;

		$subject = new CrudSubject();

		$config = array();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$listener
			->expects($this->never())
			->method('_action');
		$controller
			->expects($this->never())
			->method('set');

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_ensureData', array($subject), $listener);
	}

/**
 * test_ensureSuccessAlreadySet
 *
 * @return void
 */
	public function test_ensureSuccessAlreadySet() {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_controller'))
			->disableOriginalConstructor()
			->getMock();

		$subject = new CrudSubject(array('success' => true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$controller->viewVars['success'] = true;

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));
		$controller
			->expects($this->never())
			->method('set');

		$this->setReflectionClassInstance($listener);
		$this->callProtectedMethod('_ensureSuccess', array($subject), $listener);
	}

/**
 * testFlashMessageSupressed
 *
 * The API listener should suppress flash messages
 * if the request is "API"
 *
 * @return void
 */
	public function testFlashMessageSupressed() {
		$Request = new CakeRequest();
		$Request->addDetector('api', array('callback' => function() {
			return true;
		}));

		$subject = new CrudSubject(array('request' => $Request));

		$apiListener = new ApiListener($subject);

		$event = new CakeEvent('Crud.setFlash', $subject);
		$apiListener->setFlash($event);

		$stopped = $event->isStopped();
		$this->assertTrue($stopped, 'Set flash event is expected to be stopped');
	}

/**
 * Data provider for test_expandPath
 *
 * @return array
 */
	public function data_expandPath() {
		return array(
			'simple string' => array(
				new CrudSubject(array('modelClass' => 'MyModel')),
				'{modelClass}.id',
				'MyModel.id'
			),

			'string and integer' => array(
				new CrudSubject(array('modelClass' => 'MyModel', 'id' => 1)),
				'{modelClass}.{id}',
				'MyModel.1'
			),

			'ignore non scalar' => array(
				new CrudSubject(array('modelClass' => 'MyModel', 'complex' => new StdClass)),
				'{modelClass}.{id}',
				'MyModel.{id}'
			),
		);
	}

/**
 * test_expandPath
 *
 * @dataProvider data_expandPath
 * @return void
 */
	public function test_expandPath($subject, $path, $expected) {
		$listener = new ApiListener(new CrudSubject());

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_expandPath', array($subject, $path), $listener);
		$this->assertSame($expected, $result);
	}

/**
 * testSetupDetectors
 *
 * @return void
 */
	public function testSetupDetectors() {
		$detectors = array('xml' => array(), 'json' => array());

		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_request', 'config'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('addDetector'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));
		$listener
			->expects($this->at($i++))
			->method('config')
			->with('detectors')
			->will($this->returnValue($detectors));

		$r = 0;
		foreach ($detectors as $name => $config) {
			$request
				->expects($this->at($r++))
				->method('addDetector')
				->with($name);
		}

		$request
			->expects($this->at($r++))
			->method('addDetector')
			->with('api');

		$listener->setupDetectors();
	}

/**
 * testSetupDetectorsIntigration
 *
 * @return void
 */
	public function testSetupDetectorsIntigration() {
		$detectors = array(
			'json' => array('ext' => 'json', 'accepts' => 'application/json'),
			'xml' => array('ext' => 'xml', 'accepts' => 'text/xml')
		);

		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_request', 'config'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('accepts'))
			->disableOriginalConstructor()
			->getMock();

		$i = 0;
		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));
		$listener
			->expects($this->at($i++))
			->method('config')
			->with('detectors')
			->will($this->returnValue($detectors));

		$listener->setupDetectors();

		// Test with "ext"
		foreach ($detectors as $name => $configuration) {
			$request->params['ext'] = $configuration['ext'];
			$this->assertTrue($request->is($name));
		}

		$request->params['ext'] = null;

		// Test with "accepts"
		$r = 0;
		foreach ($detectors as $name => $configuration) {
			$request
				->expects($this->at($r++))
				->method('accepts')
				->with($configuration['accepts'])
				->will($this->returnValue(true));
		}

		foreach ($detectors as $name => $config) {
			$this->assertTrue($request->is($name));
		}

		$request->params['ext'] = 'xml';
		$this->assertTrue($request->is('api'));

		$request->params['ext'] = null;
		$this->assertFalse($request->is('api'));
	}

/**
 * testRegisterExceptionHandler with Api request
 *
 * @return void
 */
	public function testRegisterExceptionHandlerWithApi() {
		$listener = $this->getMockBuilder('ApiListener')
			->setMethods(array('_request'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();
		$request
			->expects($this->at(0))
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$listener
			->expects($this->once())
			->method('_request')
			->with()
			->will($this->returnValue($request));

		$listener->registerExceptionHandler();

		$expected = 'Crud.CrudExceptionRenderer';
		$result = Configure::read('Exception.renderer');
		$this->assertEquals($expected, $result);
	}


/**
 * testRegisterExceptionHandler without Api request
 *
 * @return void
 */
	public function testRegisterExceptionHandlerWithoutApi() {
		$listener = $this->getMockBuilder('ApiListener')
			->setMethods(array('_request'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();
		$request
			->expects($this->at(0))
			->method('is')
			->with('api')
			->will($this->returnValue(false));

		$listener
			->expects($this->once())
			->method('_request')
			->with()
			->will($this->returnValue($request));

		$listener->registerExceptionHandler();

		$expected = 'ExceptionRenderer';
		$result = Configure::read('Exception.renderer');
		$this->assertEquals($expected, $result);
	}
/**
 * data provider for test_checkRequestMethods
 *
 * @return array
 */
	public function data_checkRequestMethods() {
		return array(
			'defaults' => array(
				array(),
				false,
				array()
			),
			'valid get' => array(
				array('methods' => array('get')),
				true,
				array('get' => true)
			),
			'invalid post' => array(
				array('methods' => array('post')),
				'BadRequestException',
				array('post' => false)
			),
			'valid put' => array(
				array('methods' => array('post', 'get', 'put')),
				true,
				array('post' => false, 'get' => false, 'put' => true)
			)
		);
	}

/**
 * test_checkRequestMethods
 *
 * @dataProvider data_checkRequestMethods
 * @return void
 */
	public function test_checkRequestMethods($apiConfig, $exception, $requestMethods) {
		$listener = $this
			->getMockBuilder('ApiListener')
			->setMethods(array('_action', '_request'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(array('config'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at(0))
			->method('_action')
			->will($this->returnValue($action));
		$action
			->expects($this->at(0))
			->method('config')
			->with('api')
			->will($this->returnValue($apiConfig));

		if (!empty($apiConfig['methods'])) {
			$listener
				->expects($this->at(1))
				->method('_request')
				->will($this->returnValue($request));

			$r = 0;
			foreach ($requestMethods as $method => $bool) {
				$request
					->expects($this->at($r++))
					->method('is')
					->with($method)
					->will($this->returnValue($bool));
			}
		} else {
			$listener
				->expects($this->never())
				->method('_request');
		}

		if (is_string($exception)) {
			$this->expectException($exception);
		}

		$this->setReflectionClassInstance($listener);
		$result = $this->callProtectedMethod('_checkRequestMethods', array(), $listener);

		if (is_bool($exception)) {
			$this->assertEquals($exception, $result);
		}
	}

/**
 * testMapResources
 *
 * Passing no argument, should map all of the app's controllers
 *
 * @return void
 */
	public function testMapResources() {
		$path = CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS;
		App::build(array(
			'Controller' => array($path)
		), App::RESET);

		Router::reload();
		Router::$routes = array();

		ApiListener::mapResources();

		$expected = $this->_getRouteExpectations();
		$return = $this->_currentRoutes();

		$this->assertSame($expected, $return, 'test_app contains several controllers - there should be rest routes for all of them');
	}

/**
 * _getRouteExpectations
 *
 * A little helper function which returns routes expectations for all app controllers
 *
 * @return array
 */
	protected function _getRouteExpectations() {
		$routePatterns = array(
			'GET index /{name}',
			'GET view /{name}/:id',
			'POST add /{name}',
			'PUT edit /{name}/:id',
			'DELETE delete /{name}/:id',
			'POST edit /{name}/:id',
		);

		$expected = array();
		$controllers = App::objects('Controller');
		foreach ($controllers as $controller) {
			$controller = substr($controller, 0, - strlen('Controller'));
			$controller = Inflector::underscore($controller);
			if ($controller === 'app') {
				continue;
			}

			$routes = $routePatterns;
			foreach ($routes as &$route) {
				$route = str_replace('{name}', $controller, $route);
			}

			$expected = array_merge($expected, $routes);
		}

		return $expected;
	}

/**
 * Passing a plugin name should map only for that plugin
 *
 * @return void
 */
	public function testMapResourcesPlugin() {
		$path = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS;
		App::build(array(
			'Plugin' => array($path)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		Router::reload();
		Router::$routes = array();

		ApiListener::mapResources('TestPlugin');

		$expected = array(
			'GET index /test_plugin/test_plugin',
			'GET view /test_plugin/test_plugin/:id',
			'POST add /test_plugin/test_plugin',
			'PUT edit /test_plugin/test_plugin/:id',
			'DELETE delete /test_plugin/test_plugin/:id',
			'POST edit /test_plugin/test_plugin/:id',
			'GET index /test_plugin/tests',
			'GET view /test_plugin/tests/:id',
			'POST add /test_plugin/tests',
			'PUT edit /test_plugin/tests/:id',
			'DELETE delete /test_plugin/tests/:id',
			'POST edit /test_plugin/tests/:id',
		);
		$return = $this->_currentRoutes();

		$this->assertSame($expected, $return, 'test plugin contains a test plugin and tests controller');
	}

/**
 * _currentRoutes
 *
 * Return current route definitions in a very simple format for comparison purposes
 *
 * @return array
 */
	protected function _currentRoutes() {
		$return = array();

		foreach (Router::$routes as $route) {
			$return[] = $route->defaults['[method]'] .
				' ' . $route->defaults['action'] .
				' ' . $route->template;
		}

		return $return;
	}

/**
 * testViewClass
 *
 * Test that both set and get works
 *
 * @return void
 */
	public function testViewClass() {
		$apiListener = new ApiListener(new CrudSubject());

		$result = $apiListener->viewClass('json', 'Sample.ViewClass');
		$this->assertEquals($apiListener, $result, 'Setting a viewClass did not return the listener itself');

		$result = $apiListener->viewClass('json');
		$this->assertEquals('Sample.ViewClass', $result, 'The changed viewClass was not returned');
	}

/**
 * testViewClassDefaults
 *
 * Test that the default viewClasses are as expected
 *
 * @return void
 */
	public function testViewClassDefaults() {
		$apiListener = new ApiListener(new CrudSubject());

		$result = $apiListener->config('viewClasses');
		$expected = array(
			'json' => 'Crud.CrudJson',
			'xml' => 'Crud.CrudXml'
		);
		$this->assertEquals($expected, $result, 'The default viewClasses setting has changed');
	}

/**
 * testInjectViewClasses
 *
 * @return void
 */
	public function testInjectViewClasses() {
		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('foo')) // need to mock *something* to make Controller::__set work
			->disableOriginalConstructor()
			->getMock();

		$controller->RequestHandler = $this->getMock('RequestHandler', array('viewClassMap'));
		$controller->RequestHandler->expects($this->at(0))->method('viewClassMap')->with('json', 'Crud.CrudJson');
		$controller->RequestHandler->expects($this->at(1))->method('viewClassMap')->with('xml', 'Crud.CrudXml');

		$apiListener = $this->getMock('ApiListener', array('_controller'), array(new CrudSubject()));
		$apiListener->expects($this->once())->method('_controller')->will($this->returnValue($controller));
		$apiListener->injectViewClasses();
	}

}
