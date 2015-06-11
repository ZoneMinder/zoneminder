<?php

App::uses('Controller', 'Controller');
App::uses('Component', 'Controller');
App::uses('ComponentCollection', 'Controller');
App::uses('Behavior', 'Model');
App::uses('BehaviorCollection', 'Model');
App::uses('CakeEvent', 'Event');
App::uses('CakeRequest', 'Network');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('SearchListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class SearchListenerTest extends CakeTestCase {

	public function setup() {
		parent::setup();

		if (!CakePlugin::loaded('Search')) {
			try {
				CakePlugin::load('Search');
			} catch (MissingPluginException $e) {
				$this->markTestSkipped('Search plugin not available');
			}
		}
	}

/**
 * Test implemented events
 *
 * @covers SearchListener::implementedEvents
 * @return void
 */
	public function testImplementedEvents() {
		$Instance = new SearchListener(new CrudSubject());
		$result = $Instance->implementedEvents();
		$expected = array(
			'Crud.beforeHandle' => array('callable' => 'beforeHandle', 'priority' => 50),
			'Crud.beforePaginate' => array('callable' => 'beforePaginate', 'priority' => 50)
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that scope returns instance of it self for chaining
 *
 * @covers SearchListener::scope
 * @return void
 */
	public function testScopeReturnsSelf() {
		$Instance = new SearchListener(new CrudSubject());
		$result = $Instance->scope('test', array('key' => 'value'));
		$this->assertTrue($Instance === $result);
	}

/**
 * Test that scope without filter works
 *
 * @covers SearchListener::scope
 * @return void
 */
	public function testScopeWithoutFilter() {
		$Instance = new SearchListener(new CrudSubject());
		$Instance->scope('test', array('key' => 'value'));

		$expected = array('query' => array('key' => 'value'), 'filter' => null);
		$result = $Instance->config('scope.test');
		$this->assertEquals($expected, $result);
	}

/**
 * Test that scope with filter works
 *
 * @covers SearchListener::scope
 * @return void
 */
	public function testScopeWithFilter() {
		$Instance = new SearchListener(new CrudSubject());
		$Instance->scope('test', array('key' => 'value'), array('epic' => 'value'));

		$expected = array('query' => array('key' => 'value'), 'filter' => array('epic' => 'value'));
		$result = $Instance->config('scope.test');
		$this->assertEquals($expected, $result);
	}

/**
 * Test beforePaginate
 *
 * All clean, no configuration and nothing loaded
 *
 * @covers SearchListener::beforePaginate
 * @return void
 */
	public function testBeforePaginate() {
		$Action = $this->getMock('stdClass', array('config'));
		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->once())
			->method('action')
			->will($this->returnValue($Action));

		$Model = new Model();
		$Request = new CakeRequest();
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'crud' => $Crud,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_checkRequiredPlugin',
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance
			->expects($this->once())
			->method('_checkRequiredPlugin');
		$Instance
			->expects($this->once())
			->method('_ensureComponent')
			->with($Controller);
		$Instance
			->expects($this->once())
			->method('_ensureBehavior')
			->with($Model);
		$Instance
			->expects($this->once())
			->method('_commonProcess')
			->with($Controller, 'Model');
		$Instance
			->expects($this->once())
			->method('_setFilterArgs')
			->with($Model, array());
		$Instance
			->expects($this->once())
			->method('_setPaginationOptions')
			->with($Controller, $Model, array());

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test beforePaginate
 *
 * All clean, no configuration and nothing loaded
 *
 * @covers SearchListener::beforePaginate
 * @return void
 */
	public function testBeforePaginateWithModelFilterArgs() {
		$Action = $this->getMock('stdClass', array('config'));
		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->once())
			->method('action')
			->will($this->returnValue($Action));

		$Model = new Model();
		$Model->filterArgs = array('sample' => 'test');
		$Request = new CakeRequest();
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'crud' => $Crud,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_checkRequiredPlugin',
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance
			->expects($this->once())
			->method('_checkRequiredPlugin');
		$Instance
			->expects($this->once())
			->method('_ensureComponent')
			->with($Controller);
		$Instance
			->expects($this->once())
			->method('_ensureBehavior')
			->with($Model);
		$Instance
			->expects($this->once())
			->method('_commonProcess')
			->with($Controller, 'Model');
		$Instance
			->expects($this->never())
			->method('_setFilterArgs', 'Should not be called when model got filterArgs already');
		$Instance
			->expects($this->once())
			->method('_setPaginationOptions')
			->with($Controller, $Model, array());

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test beforePaginate
 *
 * Test that query scope works without a defined
 * query scope in the listener
 *
 * @covers SearchListener::beforePaginate
 * @return void
 */
	public function testBeforePaginateWithUndefinedQueryScope() {
		$Action = $this->getMock('stdClass', array('config'));
		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->once())
			->method('action')
			->will($this->returnValue($Action));

		$Model = new Model();
		$Model->filterArgs = array('sample' => 'test');
		$Request = new CakeRequest();
		$Request->query['_scope'] = 'sample';
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'crud' => $Crud,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_checkRequiredPlugin',
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance
			->expects($this->once())
			->method('_checkRequiredPlugin');
		$Instance
			->expects($this->once())
			->method('_ensureComponent')
			->with($Controller);
		$Instance
			->expects($this->once())
			->method('_ensureBehavior')
			->with($Model);
		$Instance
			->expects($this->once())
			->method('_commonProcess')
			->with($Controller, 'Model');
		$Instance
			->expects($this->never())
			->method('_setFilterArgs', 'Should not be called when model got filterArgs already');
		$Instance
			->expects($this->once())
			->method('_setPaginationOptions')
			->with($Controller, $Model, null);

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test beforePaginate
 *
 * Test that query scope works with a defined
 * query scope in the listener
 *
 * @covers SearchListener::beforePaginate
 * @return void
 */
	public function testBeforePaginateWithDefinedQueryScope() {
		$Model = new Model();
		$Model->filterArgs = array('sample' => 'test');
		$Request = new CakeRequest();
		$Request->query['_scope'] = 'sample';
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_checkRequiredPlugin',
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance->scope('sample', array('test' => 1));
		$Instance
			->expects($this->once())
			->method('_checkRequiredPlugin');
		$Instance
			->expects($this->once())
			->method('_ensureComponent')
			->with($Controller);
		$Instance
			->expects($this->once())
			->method('_ensureBehavior')
			->with($Model);
		$Instance
			->expects($this->once())
			->method('_commonProcess')
			->with($Controller, 'Model');
		$Instance
			->expects($this->never())
			->method('_setFilterArgs', 'Should not be called when model got filterArgs already');
		$Instance
			->expects($this->once())
			->method('_setPaginationOptions')
			->with($Controller, $Model, array('test' => 1));

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test beforePaginate
 *
 * Test that query scope works with a defined
 * query scope in the listener and a filter
 *
 * @covers SearchListener::beforePaginate
 * @return void
 */
	public function testBeforePaginateWithDefinedQueryScopeAndFilter() {
		$Model = new Model();
		$Model->filterArgs = array('sample' => 'test');
		$Request = new CakeRequest();
		$Request->query['_scope'] = 'sample';
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_checkRequiredPlugin',
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance->scope('sample', array('test' => 1), array('filter' => true));
		$Instance
			->expects($this->once())
			->method('_checkRequiredPlugin');
		$Instance
			->expects($this->once())
			->method('_ensureComponent')
			->with($Controller);
		$Instance
			->expects($this->once())
			->method('_ensureBehavior')
			->with($Model);
		$Instance
			->expects($this->once())
			->method('_commonProcess')
			->with($Controller, 'Model');
		$Instance
			->expects($this->once())
			->method('_setFilterArgs')
			->with($Model, array('filter' => true));
		$Instance
			->expects($this->once())
			->method('_setPaginationOptions')
			->with($Controller, $Model, array('test' => 1));

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test that _checkRequiredPlugin doesn't throw an exception
 *
 * @covers SearchListener::_checkRequiredPlugin
 * @return void
 */
	public function testCheckRequiredPlugins() {
		$Action = $this->getMock('stdClass', array('config'));
		$Crud = $this->getMock('stdClass', array('action'));
		$Crud
			->expects($this->once())
			->method('action')
			->will($this->returnValue($Action));

		$Model = new Model();
		$Model->filterArgs = array('sample' => 'test');
		$Request = new CakeRequest();
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'crud' => $Crud,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance
			->expects($this->once())
			->method('_ensureComponent')
			->with($Controller);
		$Instance
			->expects($this->once())
			->method('_ensureBehavior')
			->with($Model);
		$Instance
			->expects($this->once())
			->method('_commonProcess')
			->with($Controller, 'Model');
		$Instance
			->expects($this->never())
			->method('_setFilterArgs');
		$Instance
			->expects($this->once())
			->method('_setPaginationOptions')
			->with($Controller, $Model, array());

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test that _checkRequiredPlugin doesn't throw an exception
 *
 * @covers SearchListener::_checkRequiredPlugin
 * @return void
 */
	public function testCheckRequiredPluginsWithoutPlugin() {
		CakePlugin::unload('Search');

		$this->setExpectedException(
			'CakeException',
			'SearchListener requires the CakeDC/search plugin. Please install it from https://github.com/CakeDC/search'
		);

		$Model = new Model();
		$Model->filterArgs = array('sample' => 'test');
		$Request = new CakeRequest();
		$Controller = new Controller();
		$CrudSubject = new CrudSubject(array(
			'request' => $Request,
			'controller' => $Controller,
			'model' => $Model
		));

		$mocked = array(
			'_ensureComponent',
			'_ensureBehavior',
			'_commonProcess',
			'_setFilterArgs',
			'_setPaginationOptions'
		);

		$Instance = $this->getMock('SearchListener', $mocked, array($CrudSubject));
		$Instance
			->expects($this->never())
			->method('_ensureComponent');
		$Instance
			->expects($this->never())
			->method('_ensureBehavior');
		$Instance
			->expects($this->never())
			->method('_commonProcess');
		$Instance
			->expects($this->never())
			->method('_setFilterArgs');
		$Instance
			->expects($this->never())
			->method('_setPaginationOptions');

		$Instance->beforePaginate(new CakeEvent('beforePaginate', $CrudSubject));
	}

/**
 * Test that the Prg component is automatically initialized
 * if its not loaded by the controller directly
 *
 * @covers SearchListener::_ensureComponent
 * @return void
 */
	public function testEnsureComponent() {
		$Controller = new Controller(new CakeRequest());

		$Component = $this->getMock('Component', array('initialize', 'startup'), array(), '', false);
		$Component
			->expects($this->once())
			->method('initialize')
			->with($Controller);
		$Component
			->expects($this->once())
			->method('startup')
			->with($Controller);

		$Controller->Components = $this->getMock('ComponentCollection', array('loaded', 'load'));
		$Controller->Components
			->expects($this->once())
			->method('loaded')
			->with('Prg')
			->will($this->returnValue(false));
		$Controller->Components
			->expects($this->once())
			->method('load')
			->with('Search.Prg')
			->will($this->returnValue($Component));

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_ensureComponent');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Controller);
	}

/**
 * Test that nothing is done if the Prg component is already loaded
 *
 * @covers SearchListener::_ensureComponent
 * @return void
 */
	public function testEnsureComponentAlreadyLoaded() {
		$Controller = new Controller(new CakeRequest());
		$Controller->Components = $this->getMock('ComponentCollection', array('loaded', 'load'));
		$Controller->Components
			->expects($this->once())
			->method('loaded')
			->with('Prg')
			->will($this->returnValue(true));
		$Controller->Components
			->expects($this->never())
			->method('load');

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_ensureComponent');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Controller);
	}

/**
 * Test that the Searchable behavior is automatically initialized
 * if its not loaded by the model directly
 *
 * @covers SearchListener::_ensureBehavior
 * @return void
 */
	public function testEnsureBehavior() {
		$Model = new Model();

		$Behavior = $this->getMock('Behavior', array('setup'), array(), '', false);
		$Behavior
			->expects($this->once())
			->method('setup')
			->with($Model);

		$Model->Behaviors = $this->getMock('BehaviorCollection', array('loaded', 'load'));
		$Model->Behaviors->Searchable = $Behavior;
		$Model->Behaviors
			->expects($this->once())
			->method('loaded')
			->with('Searchable')
			->will($this->returnValue(false));
		$Model->Behaviors
			->expects($this->once())
			->method('load')
			->with('Search.Searchable');

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_ensureBehavior');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Model);
	}

/**
 * Test that nothing is done if the Searchable behavior is already loaded
 *
 * @covers SearchListener::_ensureBehavior
 * @return void
 */
	public function testEnsureBehaviorAlreadyLoaded() {
		$Model = new Model();

		$Behavior = $this->getMock('Behavior', array('setup'), array(), '', false);
		$Behavior
			->expects($this->never())
			->method('setup');

		$Model->Behaviors = $this->getMock('BehaviorCollection', array('loaded', 'load'));
		$Model->Behaviors->Searchable = $Behavior;
		$Model->Behaviors
			->expects($this->once())
			->method('loaded')
			->with('Searchable')
			->will($this->returnValue(true));
		$Model->Behaviors
			->expects($this->never())
			->method('load');

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_ensureBehavior');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Model);
	}

	public function test_setPaginationOptions() {
		$Controller = new Controller();
		$Controller->Paginator = new StdClass();
		$Controller->Paginator->settings = array();
		$Model = $this->getMock('Model', array('parseCriteria'));

		$Model
			->expects($this->once())
			->method('parseCriteria')
			->will($this->returnValue(array('some' => 'conditions')));

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_setPaginationOptions');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Controller, $Model, array());

		$expected = array(
			'some' => 'conditions'
		);
		$result = $Controller->Paginator->settings['conditions'];
		$this->assertSame($expected, $result, 'Conditions should match what the model says');
	}

	public function test_setPaginationOptionsMerge() {
		$Controller = new Controller();
		$Controller->Paginator = new StdClass();
		$Controller->Paginator->settings = array(
			'conditions' => array(
				'existing' => 'conditions'
			)
		);
		$Model = $this->getMock('Model', array('parseCriteria'));

		$Model
			->expects($this->once())
			->method('parseCriteria')
			->will($this->returnValue(array('some' => 'conditions')));

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_setPaginationOptions');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Controller, $Model, array());

		$expected = array(
			'existing' => 'conditions',
			'some' => 'conditions'
		);
		$result = $Controller->Paginator->settings['conditions'];
		$this->assertSame($expected, $result, 'Existing conditions should not be removed');
	}

	public function test_setPaginationOptionsClobber() {
		$Controller = new Controller();
		$Controller->Paginator = new StdClass();
		$Controller->Paginator->settings = array(
			'conditions' => array(
				'some' => 'other conditions'
			)
		);
		$Model = $this->getMock('Model', array('parseCriteria'));

		$Model
			->expects($this->once())
			->method('parseCriteria')
			->will($this->returnValue(array('some' => 'conditions')));

		$Instance = new SearchListener(new CrudSubject());

		$Method = new ReflectionMethod('SearchListener', '_setPaginationOptions');
		$Method->setAccessible(true);
		$Method->invoke($Instance, $Controller, $Model, array());

		$expected = array(
			'some' => 'conditions'
		);
		$result = $Controller->Paginator->settings['conditions'];
		$this->assertSame($expected, $result, 'Existing conditions should be overwritten');
	}

}
