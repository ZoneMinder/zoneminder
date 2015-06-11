<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('PaginatorComponent', 'Controller/Component');
App::uses('CakeRequest', 'Network');
App::uses('CakeEvent', 'Event');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('IndexCrudAction', 'Crud.Controller/Crud/Action');
App::uses('ApiFieldFilterListener', 'Crud.Controller/Crud/Listener');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiFieldFilterListenerTest extends CakeTestCase {

	public function setUp() {
		parent::setUp();

		$this->ModelMock = $this->getMockBuilder('Model');
		$this->ControllerMock = $this->getMockBuilder('Controller');
		$this->RequestMock = $this->getMockBuilder('CakeRequest');
		$this->CrudMock = $this->getMockBuilder('CrudComponent');
		$this->PaginatorMock = $this->getMockBuilder('PaginatorComponent');
		$this->ActionMock = $this->getMockBuilder('IndexCrudAction');
	}

	public function tearDown() {
		parent::tearDown();

		unset(
			$this->ModelMock,
			$this->Controller,
			$this->RequestMock,
			$this->CrudMock,
			$this->PaginatorMock,
			$this->ActionMock
		);
	}

/**
 * Helper method to generate and mock all the required
 * classes
 *
 * `$hasField` is a field => bool array with what
 * fields should exist according to 'hasField' model check
 *
 * @param array $hasField
 * @return array
 */
	protected function _mockClasses($hasField = array()) {
		$CrudSubject = new CrudSubject();

		$Crud = $this->CrudMock
			->disableOriginalConstructor()
			->setMethods(array('action'))
			->getMock();

		$Model = $this->ModelMock
			->setConstructorArgs(array(
				array('table' => 'models', 'name' => 'Model', 'ds' => 'test')
			))
			->setMethods(array('hasField', 'getAssociated'))
			->getMock();
		$Model
			->expects($this->any())
			->method('getAssociated')
			->will($this->returnValue(array('Sample' => array(), 'Demo' => array(), 'User' => array())));
		$Model->alias = 'Model';

		$Controller = $this->ControllerMock
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$Controller->Components = new StdClass;

		$Request = new CakeRequest();
		$Request->addDetector('api', array('callback' => function() {
			return true;
		}));

		$Paginator = $this->PaginatorMock
			->disableOriginalConstructor()
			->setMethods(null)
			->getMock();
		$Controller->Paginator = $Paginator;

		$CrudSubject->set(array(
			'crud' => $Crud,
			'request' => $Request,
			'controller' => $Controller,
			'action' => 'add',
			'model' => $Model,
			'modelClass' => $Model->name,
			'args' => array(),
			'query' => array(
				'fields' => null,
				'contain' => null
			)
		));

		$Action = $this->ActionMock
			->setConstructorArgs(array($CrudSubject))
			->setMethods(null)
			->getMock();

		$Listener = new ApiFieldFilterListener($CrudSubject);
		$Event = new CakeEvent('Test', $CrudSubject);

		$Crud
			->expects($this->any())
			->method('action')
			->will($this->returnValue($Action));

		$i = 0;
		foreach ($hasField as $field => $has) {
			$Model
				->expects($this->at($i))
				->method('hasField')
				->with($field)
				->will($this->returnValue($has));

			$i++;
		}

		return compact('Crud', 'Model', 'Controller', 'Paginator', 'Request', 'CrudSubject', 'Listener', 'Action', 'Event');
	}

/**
 * Test that the listener listen to the correct
 * events with the correct priority
 *
 * @return void
 */
	public function testImplementedEvents() {
		extract($this->_mockClasses());

		$expected = array(
			'Crud.beforePaginate' => array('callable' => 'beforePaginate', 'priority' => 50),
			'Crud.beforeFind' => array('callable' => 'beforeFind', 'priority' => 50)
		);

		$actual = $Listener->implementedEvents();
		$this->assertEquals($expected, $actual);
	}

/**
 * Test that a beforeFind with no fields in the query
 * will not inject any fields or contain into the query
 *
 * @return void
 */
	public function testRequestWithoutFieldsWithNoFilterOn() {
		extract($this->_mockClasses());
		$Listener->allowNoFilter(true);
		$Listener->beforeFind($Event);

		$this->assertNull($CrudSubject->query['fields']);
	}

/**
 * Test that a beforeFind with no fields in the query
 * will throw an exception by default
 *
 * @expectedException CakeException
 * @expectedExceptionMessage Please specify which fields you would like to select
 * @return void
 */
	public function testRequestWithoutFieldsWithNoFilterDefault() {
		extract($this->_mockClasses());
		$Listener->beforeFind($Event);

		$this->assertNull($CrudSubject->query['fields']);
	}

/**
 * Test that a beforeFind with no fields in the query
 * will throw an exception if 'allowNofilter' is set to false
 *
 * @expectedException CakeException
 * @expectedExceptionMessage Please specify which fields you would like to select
 * @return void
 */
	public function testRequestWithoutFieldsWithNoFilterOff() {
		extract($this->_mockClasses());
		$Action->config('apiFieldFilter.allowNoFilter', false);
		$Listener->beforeFind($Event);

		$this->assertNull($CrudSubject->query['fields']);
	}

/**
 * Test that a beforeFind with 3 fields
 * will inject them into the fields array
 *
 * @return void
 */
	public function testRequestWithFields() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name', 'Model.password');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that a beforeFind with 3 fields
 * will inject two into the fields array
 * since they exist in the model, but the 3rd
 * field (password) will be removed
 *
 * @return void
 */
	public function testGetFieldsIncludeFieldNotInModel() {
		$hasField = array('id' => true,	'name' => true,	'password' => false);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that whitelisting only will allow
 * fields in the whitelist to be included
 * in the fieldlist
 *
 * Password exist as a column, but is not
 * whitelisted, and thus should be removed
 *
 * @return void
 */
	public function testWhitelistFields() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->whitelistfields(array('Model.id', 'Model.name'));

		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that blacklisting a field will ensure
 * that it will be removed from list of fields
 *
 * Password exist as a column, but is
 * blacklisted, and thus should be removed
 *
 * @return void
 */
	public function testBlacklistFields() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->blacklistFields(array('Model.password'));

		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that the field Sample.my_fk gets rejected since there is no
 * whitelist for the associated model "Sample"
 *
 * @return void
 */
	public function testAssociatedModelGetsRejectedByDefault() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password,Sample.my_fk';

		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name', 'Model.password');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that the field Sample.my_fk gets rejected since there is no
 * whitelist for the associated model "Sample"
 *
 * @return void
 */
	public function testAssociatedModelWhitelist() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password,Sample.my_fk';

		$Model->Sample = $this->getMock('Model', array('hasField'), array(array('Sample' => array(), 'Demo' => array(), 'User' => array())));
		$Model->Sample
			->expects($this->at(0))
			->method('hasField')
			->with('my_fk')
			->will($this->returnValue(true));

		$Listener->whitelistModels(array('Sample'));
		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name', 'Model.password', 'Sample.my_fk');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that blacklisting always will win
 * in the filtering.
 *
 * If a field is both white and blacklisted
 * it will end up being removed
 *
 * @return void
 */
	public function testBlacklistingWinOverWhitelist() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->whitelistFields(array('Model.id', 'Model.name', 'Model.password'));
		$Listener->blacklistFields(array('Model.password'));

		$Listener->beforeFind($Event);

		$expected = array('Model.id', 'Model.name');
		$actual = $CrudSubject->query['fields'];
		$this->assertSame($expected, $actual);
	}

/**
 * Test that a beforePaginate with no fields in the query
 * will not inject any fields or contain into the query
 *
 * @return void
 */
	public function testBeforePaginateRequestWithoutFieldsWithNoFilterOn() {
		extract($this->_mockClasses());
		$Listener->allowNoFilter(true);
		$Listener->beforePaginate($Event);

		$this->assertFalse(isset($Paginator->settings['fields']));
		$this->assertFalse(isset($Paginator->settings['contain']));
	}

/**
 * Test that a beforePaginate with no fields in the query
 * will throw an exception by default
 *
 * @expectedException CakeException
 * @expectedExceptionMessage Please specify which fields you would like to select
 * @return void
 */
	public function testBeforePaginateRequestWithoutFieldsWithNoFilterDefault() {
		extract($this->_mockClasses());
		$Listener->beforePaginate($Event);

		$this->assertFalse(isset($Paginator->settings['fields']));
		$this->assertFalse(isset($Paginator->settings['contain']));
	}

/**
 * Test that a beforePaginate with no fields in the query
 * will throw an exception if 'allowNofilter' is set to false
 *
 * @expectedException CakeException
 * @expectedExceptionMessage Please specify which fields you would like to select
 * @return void
 */
	public function testBeforePaginateRequestWithoutFieldsWithNoFilterOff() {
		extract($this->_mockClasses());
		$Action->config('apiFieldFilter.allowNoFilter', false);
		$Listener->beforePaginate($Event);

		$this->assertFalse(isset($Paginator->settings['fields']));
		$this->assertFalse(isset($Paginator->settings['contain']));
	}

/**
 * Test that a beforePaginate with 3 fields
 * will inject them into the fields array
 *
 * @return void
 */
	public function testBeforePaginateRequestWithFields() {
		$hasField = array('id' => true,	'name' => true,	'password' => true);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->beforePaginate($Event);

		$expected = array('Model.id', 'Model.name', 'Model.password');
		$actual = $Paginator->settings['fields'];
		$this->assertSame($expected, $actual);

		$this->assertTrue(isset($Paginator->settings['contain']));
		$this->assertEmpty($Paginator->settings['contain']);
	}

/**
 * Test that a beforePaginate with 3 fields
 * will inject two into the fields array
 * since they exist in the model, but the 3rd
 * field (password) will be removed
 *
 * @return void
 */
	public function testBeforePaginateGetFieldsIncludeFieldNotInModel() {
		$hasField = array('id' => true,	'name' => true,	'password' => false);
		extract($this->_mockClasses($hasField));
		$Request->query['fields'] = 'id,name,password';

		$Listener->beforePaginate($Event);

		$expected = array('Model.id', 'Model.name');
		$actual = $Paginator->settings['fields'];
		$this->assertSame($expected, $actual);

		$this->assertTrue(isset($Paginator->settings['contain']));
		$this->assertEmpty($Paginator->settings['contain']);
	}
}
