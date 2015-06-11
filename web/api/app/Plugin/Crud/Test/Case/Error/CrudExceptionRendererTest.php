<?php

App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('CrudExceptionRenderer', 'Crud.Error');
App::uses('CrudValidationException', 'Crud.Error/Exception');
App::uses('ConnectionManager', 'Model');

class CrudExceptionRendererTest extends CakeTestCase {

	public $fixtures = array('core.post');

	public function testNormalExceptionRendering() {
		Configure::write('debug', 1);
		$Exception = new CakeException('Hello World');

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		$Renderer->render();

		$viewVars = $Controller->viewVars;

		$this->assertTrue(!empty($viewVars['_serialize']));

		$expected = array('success', 'data');
		$actual = $viewVars['_serialize'];
		$this->assertEquals($expected, $actual);

		$expected = array(
			'code' => 500,
			'url' => $Controller->request->here(),
			'name' => 'Hello World',
			'exception' => array(
				'class' => 'CakeException',
				'code' => 500,
				'message' => 'Hello World',
			)
		);
		$actual = $viewVars['data'];
		unset($actual['exception']['trace']);
		$this->assertEquals($expected, $actual);

		$this->assertTrue(!isset($actual['queryLog']));

		$this->assertTrue(isset($viewVars['success']));
		$this->assertFalse($viewVars['success']);

		$this->assertTrue(isset($viewVars['code']));
		$this->assertSame(500, $viewVars['code']);

		$this->assertTrue(isset($viewVars['url']));
		$this->assertSame($Controller->request->here(), $viewVars['url']);

		$this->assertTrue(isset($viewVars['name']));
		$this->assertSame('Hello World', $viewVars['name']);

		$this->assertTrue(isset($viewVars['error']));
		$this->assertSame($Exception, $viewVars['error']);
	}

	public function testNormalExceptionRenderingQueryLog() {
		Configure::write('debug', 2);
		$Exception = new CakeException('Hello World');

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		$Renderer->render();

		$viewVars = $Controller->viewVars;

		$this->assertTrue(!empty($viewVars['_serialize']));

		$expected = array('success', 'data');
		$actual = $viewVars['_serialize'];
		$this->assertEquals($expected, $actual);

		$expected = array(
			'code' => 500,
			'url' => $Controller->request->here(),
			'name' => 'Hello World',
			'exception' => array(
				'class' => 'CakeException',
				'code' => 500,
				'message' => 'Hello World',
			)
		);

		$actual = $viewVars['data'];
		$queryLog = $actual['queryLog'];

		unset($actual['exception']['trace']);
		unset($actual['queryLog']);
		$this->assertEquals($expected, $actual);

		$this->assertTrue(!empty($queryLog));
		$this->assertTrue(isset($queryLog['test']));
		$this->assertTrue(isset($queryLog['test']['log']));
		$this->assertTrue(isset($queryLog['test']['count']));
		$this->assertTrue(isset($queryLog['test']['time']));

		$this->assertTrue(isset($viewVars['success']));
		$this->assertFalse($viewVars['success']);

		$this->assertTrue(isset($viewVars['code']));
		$this->assertSame(500, $viewVars['code']);

		$this->assertTrue(isset($viewVars['url']));
		$this->assertSame($Controller->request->here(), $viewVars['url']);

		$this->assertTrue(isset($viewVars['name']));
		$this->assertSame('Hello World', $viewVars['name']);

		$this->assertTrue(isset($viewVars['error']));
		$this->assertSame($Exception, $viewVars['error']);
	}

	public function testNormalNestedExceptionRendering() {
		Configure::write('debug', 1);
		$Exception = new CakeException('Hello World');

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		$Renderer->render();

		$viewVars = $Controller->viewVars;

		$this->assertTrue(!empty($viewVars['_serialize']));

		$expected = array('success', 'data');
		$actual = $viewVars['_serialize'];
		$this->assertEquals($expected, $actual);

		$expected = array(
			'code' => 500,
			'url' => $Controller->request->here(),
			'name' => 'Hello World',
			'exception' => array(
				'class' => 'CakeException',
				'code' => 500,
				'message' => 'Hello World',
			)
		);
		$actual = $viewVars['data'];
		unset($actual['exception']['trace']);
		$this->assertEquals($expected, $actual);

		$this->assertTrue(isset($viewVars['success']));
		$this->assertFalse($viewVars['success']);

		$this->assertTrue(isset($viewVars['code']));
		$this->assertSame(500, $viewVars['code']);

		$this->assertTrue(isset($viewVars['url']));
		$this->assertSame($Controller->request->here(), $viewVars['url']);

		$this->assertTrue(isset($viewVars['name']));
		$this->assertSame('Hello World', $viewVars['name']);

		$this->assertTrue(isset($viewVars['error']));
		$this->assertSame($Exception, $viewVars['error']);
	}

	public function testMissingViewExceptionDuringRendering() {
		Configure::write('debug', 1);
		$Exception = new CakeException('Hello World');

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = $this->getMock('CakeResponse', array('send'));
		$Controller->response
			->expects($this->at(0))
			->method('send')
			->will($this->throwException(new MissingViewException('boo')));

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		$Renderer->render();

		$viewVars = $Controller->viewVars;

		$this->assertTrue(!empty($viewVars['_serialize']));

		$expected = array('success', 'data');
		$actual = $viewVars['_serialize'];
		$this->assertEquals($expected, $actual);

		$expected = array(
			'code' => 500,
			'url' => $Controller->request->here(),
			'name' => 'Hello World',
			'exception' => array(
				'class' => 'CakeException',
				'code' => 500,
				'message' => 'Hello World',
			)
		);
		$actual = $viewVars['data'];
		unset($actual['exception']['trace']);
		$this->assertEquals($expected, $actual);

		$this->assertTrue(isset($viewVars['success']));
		$this->assertFalse($viewVars['success']);

		$this->assertTrue(isset($viewVars['code']));
		$this->assertSame(500, $viewVars['code']);

		$this->assertTrue(isset($viewVars['url']));
		$this->assertSame($Controller->request->here(), $viewVars['url']);

		$this->assertTrue(isset($viewVars['name']));
		$this->assertSame('Hello World', $viewVars['name']);

		$this->assertTrue(isset($viewVars['error']));
		$this->assertSame($Exception, $viewVars['error']);
	}

	public function testGenericExceptionDuringRendering() {
		Configure::write('debug', 1);

		$Exception = new CakeException('Hello World');
		$NestedException = new CakeException('Generic Exception Description');

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = $this->getMock('CakeResponse', array('send'));
		$Controller->response
			->expects($this->at(0))
			->method('send')
			->will($this->throwException($NestedException));

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		$Renderer->render();

		$viewVars = $Controller->viewVars;

		$this->assertTrue(!empty($viewVars['_serialize']));

		$expected = array('success', 'data');
		$actual = $viewVars['_serialize'];
		$this->assertEquals($expected, $actual);

		$expected = array(
			'code' => 500,
			'url' => $Controller->request->here(),
			'name' => 'Hello World',
			'exception' => array(
				'class' => 'CakeException',
				'code' => 500,
				'message' => 'Hello World',
			)
		);
		$actual = $viewVars['data'];
		unset($actual['exception']['trace']);
		$this->assertEquals($expected, $actual);

		$this->assertTrue(isset($viewVars['success']));
		$this->assertFalse($viewVars['success']);

		$this->assertTrue(isset($viewVars['code']));
		$this->assertSame(500, $viewVars['code']);

		$this->assertTrue(isset($viewVars['url']));
		$this->assertSame($Controller->request->here(), $viewVars['url']);

		$this->assertTrue(isset($viewVars['name']));
		$this->assertSame('Generic Exception Description', $viewVars['name']);

		$this->assertTrue(isset($viewVars['error']));
		$this->assertSame($NestedException, $viewVars['error']);
	}

	public function testValidationErrorSingleKnownError() {
		$Model = ClassRegistry::init(array('class' => 'Model', 'alias' => 'Alias', 'table' => false));
		$Model->validate = array(
			'field' => array(
				array(
					'rule' => 'custom',
					'message' => 'boom'
				)
			)
		);
		$Model->invalidate('field', 'boom');

		$Exception = new CrudValidationException(array(
			'Alias' => array(
				'field' => array(
					'boom'
				)
			)
		));

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		Configure::write('debug', 0);
		$Renderer->render();
		Configure::write('debug', 1);

		$expected = array(
			'code' => 412,
			'url' => $Controller->request->here(),
			'name' => 'Alias.field : boom',
			'errorCount' => 1,
			'errors' => array(
				'Alias' => array(
					'field' => array(
						'boom'
					)
				)
			),
			'exception' => array(
				'class' => 'CrudValidationException',
				'code' => 412,
				'message' => 'Alias.field : boom'
			)
		);
		$this->assertEquals($expected, $Controller->viewVars['data']);
	}

	public function testValidationErrorSingleKnownErrorWithCode() {
		$Model = ClassRegistry::init(array('class' => 'Model', 'alias' => 'Alias', 'table' => false));
		$Model->validate = array(
			'field' => array(
				array(
					'rule' => 'custom',
					'message' => 'boom',
					'code' => 1000
				)
			)
		);
		$Model->invalidate('field', 'boom');

		$Exception = new CrudValidationException(array(
			'Alias' => array(
				'field' => array(
					'boom'
				)
			)
		));

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		Configure::write('debug', 0);
		$Renderer->render();
		Configure::write('debug', 1);

		$expected = array(
			'code' => 1000,
			'url' => $Controller->request->here(),
			'name' => 'Alias.field : boom',
			'errorCount' => 1,
			'errors' => array(
				'Alias' => array(
					'field' => array(
						'boom'
					)
				)
			),
			'exception' => array(
				'class' => 'CrudValidationException',
				'code' => 1000,
				'message' => 'Alias.field : boom'
			)
		);
		$this->assertEquals($expected, $Controller->viewVars['data']);
	}

	public function testValidationErrorMultipleMessages() {
		$Exception = new CrudValidationException(array(
			'Alias' => array(
				'field' => array(
					'something wrong with this field'
				),
				'another_field' => array(
					'something wrong with this field'
				)
			)
		));

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		Configure::write('debug', 0);
		$Renderer->render();
		Configure::write('debug', 1);

		$expected = array(
			'code' => 412,
			'url' => $Controller->request->here(),
			'name' => '2 validation errors occurred',
			'errorCount' => 2,
			'errors' => array(
				'Alias' => array(
					'field' => array(
						'something wrong with this field'
					),
					'another_field' => array(
						'something wrong with this field'
					)
				)
			),
			'exception' => array(
				'class' => 'CrudValidationException',
				'code' => 412,
				'message' => '2 validation errors occurred',
			)
		);
		$this->assertEquals($expected, $Controller->viewVars['data']);
	}

	public function testValidationErrorUnknownModel() {
		$Exception = new CrudValidationException(array(
			'Alias' => array(
				'field' => array(
					'something wrong with this field'
				)
			)
		));

		$Controller = $this->getMock('Controller', array('render'));
		$Controller->request = new CakeRequest();
		$Controller->response = new CakeResponse();

		$Renderer = $this->getMock('CrudExceptionRenderer', array('_getController'), array(), '', false);
		$Renderer
			->expects($this->once())
			->method('_getController')
			->with($Exception)
			->will($this->returnValue($Controller));

		$Renderer->__construct($Exception);
		Configure::write('debug', 0);
		$Renderer->render();
		Configure::write('debug', 1);

		$expected = array(
			'code' => 412,
			'url' => $Controller->request->here(),
			'name' => 'A validation error occurred',
			'errorCount' => 1,
			'errors' => array(
				'Alias' => array(
					'field' => array(
						'something wrong with this field'
					)
				)
			),
			'exception' => array(
				'class' => 'CrudValidationException',
				'code' => 412,
				'message' => 'A validation error occurred',
			)
		);
		$this->assertEquals($expected, $Controller->viewVars['data']);
	}
}
