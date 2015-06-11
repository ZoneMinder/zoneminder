<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('CakeRequest', 'Network');
App::uses('PaginatorComponent', 'Controller/Component');
App::uses('ComponentCollection', 'Controller');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('IndexCrudAction', 'Crud.Controller/Crud/Action');
App::uses('CrudTestCase', 'Crud.Test/Support');

class TestController extends Controller {

	public $paginate = array();
}

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class IndexCrudActionTest extends CrudTestCase {

/**
 * Tests that calling index action will paginate the main model
 *
 * @covers IndexCrudAction::_get
 * @return void
 */
	public function test_get() {
		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('paginate', 'set'))
			->getMock();
		$Controller->Paginator = $this
			->getMockBuilder('PaginatorComponent')
			->disableOriginalConstructor()
			->getMock();

		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$i = 0;

		$Action = $this
			->getMockBuilder('IndexCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('paginationConfig', '_controller', '_model', '_trigger', 'viewVar'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('paginationConfig')
			->with();
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Action
			->expects($this->at($i++))
			->method('viewVar')
			->with()
			->will($this->returnValue('items'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforePaginate', array('paginator' => $Controller->Paginator, 'success' => true, 'viewVar' => 'items'))
			->will($this->returnValue(new CrudSubject(array('success' => true, 'viewVar' => 'items'))));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Controller
			->expects($this->once())
			->method('paginate')
			->with($Model)
			->will($this->returnValue(array('foo', 'bar')));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterPaginate', array('success' => true, 'viewVar' => 'items', 'items' => array('foo', 'bar')))
			->will($this->returnValue(new CrudSubject(array('success' => true, 'viewVar' => 'items', 'items' => array('foo', 'bar')))));
		$Controller
			->expects($this->once())
			->method('set')
			->with(array('success' => true, 'items' => array('foo', 'bar')));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(), $Action);
	}

/**
 * Tests that iterators are casted to arrays
 *
 * @covers IndexCrudAction::_get
 * @return void
 */
	public function testPaginatorReturningIterator() {
				$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('paginate', 'set'))
			->getMock();
		$Controller->Paginator = $this
			->getMockBuilder('PaginatorComponent')
			->disableOriginalConstructor()
			->getMock();

		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$i = 0;

		$Action = $this
			->getMockBuilder('IndexCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('paginationConfig', '_controller', '_model', '_trigger', 'viewVar'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('paginationConfig')
			->with();
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Action
			->expects($this->at($i++))
			->method('viewVar')
			->with()
			->will($this->returnValue('items'));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforePaginate', array('paginator' => $Controller->Paginator, 'success' => true, 'viewVar' => 'items'))
			->will($this->returnValue(new CrudSubject(array('success' => true, 'viewVar' => 'items'))));
		$Action
			->expects($this->at($i++))
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Controller
			->expects($this->once())
			->method('paginate')
			->with($Model)
			->will($this->returnValue(array('foo', 'bar')));
		$Action
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterPaginate', array('success' => true, 'viewVar' => 'items', 'items' => array('foo', 'bar')))
			->will($this->returnValue(new CrudSubject(array('success' => true, 'viewVar' => 'items', 'items' => new ArrayIterator(array('foo', 'bar'))))));
		$Controller
			->expects($this->once())
			->method('set')
			->with(array('success' => true, 'items' => array('foo', 'bar')));

		$this->setReflectionClassInstance($Action);
		$this->callProtectedMethod('_get', array(), $Action);
	}

/**
 * Tests that $controller->paginate is copied to Paginator->settings
 *
 * @covers IndexCrudAction::paginationConfig
 * @return void
 */
	public function testPaginateSettingsAreMerged() {
		$Controller = $this
			->getMockBuilder('TestController')
			->disableOriginalConstructor()
			->setMethods(array('foo'))
			->getMock();
		$Controller->paginate = array(
			'limit' => 50,
			'paramType' => 'querystring'
		);
		$Paginator = $this
			->getMockBuilder('PaginatorComponent')
			->disableOriginalConstructor()
			->getMock();
		$Controller->Components = $this
			->getMockBuilder('ComponentCollection')
			->disableOriginalConstructor()
			->setMethods(array('load'))
			->getMock();
		$Controller->Components
			->expects($this->at(0))
			->method('load')
			->with('Paginator')
			->will($this->returnValue($Paginator));

		$i = 0;
		$Action = $this
			->getMockBuilder('IndexCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_controller', '_getFindMethod'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Controller->Components
			->expects($this->once())
			->method('load')
			->with('Paginator', array('limit' => 50, 'paramType' => 'querystring'))
			->will($this->returnCallback(function() use ($Paginator) {
				$Paginator->settings = array('limit' => 50, 'paramType' => 'querystring');
				return $Paginator;
			}));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->with('all')
			->will($this->returnValue('all'));

		$result = $Action->paginationConfig();
		$expected = array(
			'findType' => 'all',
			'limit' => 50,
			'paramType' => 'querystring'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that no findMethod is executed when a findType
 * already is defined for a Model key
 *
 * @covers IndexCrudAction::paginationConfig
 * @return void
 */
	public function testPaginationConfigExistingFindType() {
		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('foo'))
			->getMock();
		$Paginator = $this
			->getMockBuilder('PaginatorComponent')
			->disableOriginalConstructor()
			->getMock();
		$Paginator->settings['MyModel'] = array(
			'limit' => 5,
			'findType' => 'another'
		);
		$Controller->Paginator = $Paginator;
		$Controller->modelClass = 'MyModel';

		$i = 0;
		$Action = $this
			->getMockBuilder('IndexCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_controller', '_getFindMethod'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Action
			->expects($this->never())
			->method('_getFindMethod');

		$result = $Action->paginationConfig();
		$expected = array(
			'MyModel' => array(
				'limit' => 5,
				'findType' => 'another'
			),
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named'
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that `all` findMethod is executed when a findType
 * already is defined for a Model key
 *
 * @covers IndexCrudAction::paginationConfig
 * @return void
 */
	public function testPaginationConfigNonexistingFindType() {
		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('foo'))
			->getMock();
		$Paginator = $this
			->getMockBuilder('PaginatorComponent')
			->disableOriginalConstructor()
			->getMock();
		$Paginator->settings['MyModel'] = array(
			'limit' => 5,
			'findType' => null
		);
		$Controller->Paginator = $Paginator;
		$Controller->modelClass = 'MyModel';

		$i = 0;
		$Action = $this
			->getMockBuilder('IndexCrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_controller', '_getFindMethod'))
			->getMock();
		$Action
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Action
			->expects($this->at($i++))
			->method('_getFindMethod')
			->with('all')
			->will($this->returnValue('all'));

		$result = $Action->paginationConfig();
		$expected = array(
			'MyModel' => array(
				'limit' => 5,
				'findType' => 'all'
			),
			'page' => 1,
			'limit' => 20,
			'maxLimit' => 100,
			'paramType' => 'named'
		);
		$this->assertEquals($expected, $result);
	}

}
