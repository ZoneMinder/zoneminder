<?php

App::uses('Controller', 'Controller');
App::uses('CrudPanel', 'Crud.Panel');
App::uses('CrudTestCase', 'Crud.Test/Support');

class CrudPanelTest extends CrudTestCase {

/**
 * setUp
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		if (!CakePlugin::loaded('DebugKit')) {
			try {
				CakePlugin::load('DebugKit');
			} catch (MissingPluginException $e) {
				$this->markTestSkipped('DebugKit plugin not available');
			}
		}

		$this->Controller = new Controller();
		$this->Panel = new CrudPanel();

		$this->setReflectionClassInstance($this->Panel);
	}

	public function testGetCallbacks() {
		$listeners = array(
			array(
				'callable' => array(
					'SomeClass',
					'someStaticMethod'
				)
			),
			array(
				'callable' => array(
					$this,
					'someInstanceMethod'
				),
			),
			array(
				'callable' => function() {
					return 'Some Closure';
				}
			)
		);
		$line = __LINE__;
		$path = Debugger::trimPath(__FILE__);

		$expected = array(
			'SomeClass::someStaticMethod',
			'CrudPanelTest::someInstanceMethod',
			$path . ':' . ($line - 5)
		);
		$return = $this->callProtectedMethod('_getCallbacks', array($listeners), $this->Panel);
		$this->assertSame($expected, $return);
	}

/**
 * test_getUniqueName
 *
 * @return void
 */
	public function testGetUniqueName() {
		$name = 'name';
		$existing = array();
		$return = $this->callProtectedMethod('_getUniqueName', array($name, $existing), $this->Panel);
		$this->assertSame('name', $return, 'A unique name should not be modified');
	}

	public function testGetUniqueNameCollision() {
		$name = 'name';
		$existing = array('name' => array());
		$return = $this->callProtectedMethod('_getUniqueName', array($name, $existing), $this->Panel);
		$this->assertSame('name #2', $return, 'A collision should cause a suffix to be added');
	}

	public function testGetUniqueNameMultipleCollision() {
		$name = 'name';
		$existing = array('name' => array(), 'name #2' => array(), 'name #3' => array());
		$return = $this->callProtectedMethod('_getUniqueName', array($name, $existing), $this->Panel);
		$this->assertSame('name #4', $return, 'The suffix should always be one more than any existing defined names');
	}

}
