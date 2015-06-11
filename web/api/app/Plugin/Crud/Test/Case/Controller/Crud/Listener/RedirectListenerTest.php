<?php

App::uses('CakeRequest', 'Network');
App::uses('Model', 'Model');
App::uses('CrudTestCase', 'Crud.Test/Support');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('RedirectListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class RedirectListenerTest extends CrudTestCase {

/**
 * Test the correct events is bound
 *
 * @covers RedirectListener::implementedEvents
 * @return void
 */
	public function testImplementedEvents() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$result = $listener->implementedEvents();
		$expected = array(
			'Crud.beforeRedirect' => array('callable' => 'beforeRedirect', 'priority' => 90)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that we got the default readers bound on setup
 *
 * @covers RedirectListener::setup
 * @return void
 */
	public function testSetup() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$result = $listener->config('readers');

		$result['request'] = array_keys($result['request']);
		$result['model'] = array_keys($result['model']);
		$result['subject'] = array_keys($result['subject']);

		$expected = array(
			'request' => array(
				'key',
				'data',
				'query'
			),
			'model' => array(
				'key',
				'data',
				'field'
			),
			'subject' => array(
				'key'
			)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test getting an existing reader by name works
 *
 * @covers RedirectListener::reader
 * @return void
 */
	public function testReaderGetWorks() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$closure = $listener->reader('request.key');

		$this->assertNotNull($closure);
		$this->assertInstanceOf('Closure', $closure);
	}

/**
 * Test getting a non-existing reader by name fails
 *
 * @covers RedirectListener::reader
 * @return void
 */
	public function testReaderGetFails() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$closure = $listener->reader('something_invalid');

		$this->assertNull($closure);
	}

/**
 * Test setting a reader by name works
 *
 * @covers RedirectListener::reader
 * @return void
 */
	public function testReaderSetWorks() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$closure = function() {

		};

		$actual = $listener->reader('my.reader', $closure);
		$this->assertSame($listener, $actual);

		$actual = $listener->reader('my.reader');
		$this->assertSame($closure, $actual);
	}

/**
 * Test the reader "request.key"
 *
 * @return void
 */
	public function testReaderRequestKey() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->request = new CakeRequest();
		$subject->request->params['action'] = 'index';

		$reader = $listener->reader('request.key');
		$result = $reader($subject, 'action');
		$this->assertEquals('index', $result);

		$result = $reader($subject, 'something_wrong');
		$this->assertNull($result);
	}

/**
 * Test the reader "request.data"
 *
 * @return void
 */
	public function testReaderRequestData() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->request = new CakeRequest();
		$subject->request->data = array('hello' => 'world');

		$reader = $listener->reader('request.data');
		$result = $reader($subject, 'hello');
		$this->assertEquals('world', $result);

		$result = $reader($subject, 'something_wrong');
		$this->assertNull($result);
	}

/**
 * Test the reader "request.query"
 *
 * @return void
 */
	public function testReaderRequestQuery() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->request = new CakeRequest();
		$subject->request->query = array('hello' => 'world');

		$reader = $listener->reader('request.query');
		$result = $reader($subject, 'hello');
		$this->assertEquals('world', $result);

		$result = $reader($subject, 'something_wrong');
		$this->assertNull($result);
	}

/**
 * Test the reader "model.key"
 *
 * @return void
 */
	public function testReaderModelKey() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->model = new Model();
		$subject->model->data = array('hello' => 'world');

		$reader = $listener->reader('model.key');
		$result = $reader($subject, 'data');
		$this->assertEquals(array('hello' => 'world'), $result);

		$result = $reader($subject, 'something_wrong');
		$this->assertNull($result);
	}

/**
 * Test the reader "model.data"
 *
 * @return void
 */
	public function testReaderModelData() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->model = new Model();
		$subject->model->data = array('hello' => 'world');

		$reader = $listener->reader('model.data');
		$result = $reader($subject, 'hello');
		$this->assertEquals('world', $result);

		$result = $reader($subject, 'something_wrong');
		$this->assertNull($result);
	}

/**
 * Test the reader "model.field"
 *
 * @return void
 */
	public function testReaderModelField() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->model = $this
			->getMockBuilder('Model')
			->setMethods(array('field'))
			->disableoriginalConstructor()
			->getMock();
		$subject->model
			->expects($this->once())
			->method('field')
			->with('slug')
			->will($this->returnValue('ok-slug-is-ok'));

		$reader = $listener->reader('model.field');
		$result = $reader($subject, 'slug');
		$this->assertEquals('ok-slug-is-ok', $result);
	}

/**
 * Test the reader "subject.key"
 *
 * @return void
 */
	public function testReaderSubjectKey() {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$subject = new CrudSubject();
		$subject->welcome = 'hello world';

		$reader = $listener->reader('subject.key');
		$result = $reader($subject, 'welcome');
		$this->assertEquals('hello world', $result);

		$result = $reader($subject, 'something_invalid');
		$this->assertNull($result);
	}

/**
 * Test how `redirect` handles an action without any
 * redirect configuration
 *
 * @covers RedirectListener::beforeRedirect
 * @return void
 */
	public function testRedirectWithNoConfig() {
		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(array('_action', '_getKey'))
			->disableoriginalConstructor()
			->getMock();
		$listener
			->expects($this->once())
			->method('_action')
			->will($this->returnValue($action));
		$listener
			->expects($this->never())
			->method('_getKey');

		$subject = new CrudSubject();

		$listener->beforeRedirect(new CakeEvent('Crud.beforeRedirect', $subject));
	}

/**
 * Test how `redirect` handles an action with action redirect
 * configuration
 *
 * @covers RedirectListener::beforeRedirect
 * @return void
 */
	public function testRedirectWithConfigButNoValidKey() {
		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$action->redirectConfig('add', array('reader' => 'request.key', 'key' => 'hello'));

		$subject = new CrudSubject();

		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(array('_action', '_getKey', '_getUrl'))
			->disableoriginalConstructor()
			->getMock();
		$listener
			->expects($this->once())
			->method('_action')
			->will($this->returnValue($action));
		$listener
			->expects($this->once())
			->method('_getKey')
			->with($subject, 'request.key', 'hello')
			->will($this->returnValue(false));
		$listener
			->expects($this->never())
			->method('_getUrl');

		$listener->beforeRedirect(new CakeEvent('Crud.beforeRedirect', $subject));
	}

/**
 * Test how `redirect` handles an action with action redirect
 * configuration
 *
 * @covers RedirectListener::beforeRedirect
 * @return void
 */
	public function testRedirectWithConfigAndValidKey() {
		$action = $this
			->getMockBuilder('CrudAction')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$action->redirectConfig('add', array(
			'reader' => 'request.key',
			'key' => 'hello',
			'url' => array('action' => 'index')
		));

		$subject = new CrudSubject();

		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(array('_action', '_getKey', '_getUrl'))
			->disableoriginalConstructor()
			->getMock();
		$listener
			->expects($this->once())
			->method('_action')
			->will($this->returnValue($action));
		$listener
			->expects($this->once())
			->method('_getKey')
			->with($subject, 'request.key', 'hello')
			->will($this->returnValue(true));
		$listener
			->expects($this->once())
			->method('_getUrl')
			->with($subject, array('action' => 'index'))
			->will($this->returnValue(array('action' => 'index')));

		$listener->beforeRedirect(new CakeEvent('Crud.beforeRedirect', $subject));

		$this->assertSame(array('action' => 'index'), $subject->url);
	}

	public function dataProvider_getUrl() {
		$CakeRequest = new CakeRequest;
		$CakeRequest->params['action'] = 'index';
		$CakeRequest->query['parent_id'] = 10;
		$CakeRequest->data['epic'] = 'jippi';

		$Model = new Model;
		$Model->id = 69;
		$Model->slug = 'jippi-is-awesome';
		$Model->data = array('name' => 'epic', 'slug' => 'epic');

		return array(
			array(
				new CrudSubject(),
				array('action' => 'index'),
				array('action' => 'index')
			),
			array(
				new CrudSubject(),
				array('controller' => 'posts', 'action' => 'index'),
				array('controller' => 'posts', 'action' => 'index')
			),
			array(
				new CrudSubject(array('request' => $CakeRequest)),
				array('action' => array('request.key', 'action')),
				array('action' => 'index')
			),
			array(
				new CrudSubject(array('request' => $CakeRequest)),
				array('action' => array('request.data', 'epic')),
				array('action' => 'jippi')
			),
			array(
				new CrudSubject(array('request' => $CakeRequest)),
				array('action' => array('request.query', 'parent_id')),
				array('action' => 10)
			),
			array(
				new CrudSubject(array('model' => $Model)),
				array('action' => 'edit', array('model.key', 'id')),
				array('action' => 'edit', 69)
			),
			array(
				new CrudSubject(array('model' => $Model)),
				array('action' => 'edit', array('model.data', 'slug')),
				array('action' => 'edit', 'epic')
			),
			array(
				new CrudSubject(array('model' => $Model)),
				array('action' => 'edit', '?' => array('name' => array('model.data', 'slug'))),
				array('action' => 'edit', '?' => array('name' => 'epic'))
			),
			array(
				new CrudSubject(array('id' => 69)),
				array('action' => 'edit', array('subject.key', 'id')),
				array('action' => 'edit', 69)
			)
		);
	}

/**
 * Test _getUrl
 *
 * @dataProvider dataProvider_getUrl
 * @covers RedirectListener::_getUrl
 * @covers RedirectListener::_getKey
 * @return void
 */
	public function test_getUrl(CrudSubject $subject, $url, $expected) {
		$listener = $this
			->getMockBuilder('RedirectListener')
			->setMethods(null)
			->disableoriginalConstructor()
			->getMock();

		$listener->setup();

		$this->setReflectionClassInstance($listener);

		$result = $this->callProtectedMethod('_getUrl', array($subject, $url), $listener);
		$this->assertEquals($expected, $result);
	}

}
