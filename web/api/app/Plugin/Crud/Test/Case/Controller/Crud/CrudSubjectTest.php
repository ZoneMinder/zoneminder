<?php

App::uses('CrudSubject', 'Crud.Controller/Crud');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class CrudSubjectTest extends CakeTestCase {

	public function setup() {
		parent::setup();

		$this->Subject = new CrudSubject(array('action' => 'index'));
	}

	public function teardown() {
		parent::teardown();

		unset($this->Subject);
	}

/**
 * Test that shouldProcess works
 *
 * Our action is "index"
 *
 * @covers CrudSubject::shouldProcess
 * @return void
 */
	public function testShouldProcess() {
		$this->assertTrue($this->Subject->shouldProcess('only', 'index'));
		$this->assertFalse($this->Subject->shouldProcess('only', 'view'));
		$this->assertTrue($this->Subject->shouldProcess('only', array('index')));
		$this->assertFalse($this->Subject->shouldProcess('only', array('view')));

		$this->assertFalse($this->Subject->shouldProcess('not', array('index')));
		$this->assertTrue($this->Subject->shouldProcess('not', array('view')));

		$this->assertFalse($this->Subject->shouldProcess('not', 'index'));
		$this->assertTrue($this->Subject->shouldProcess('not', 'view'));
	}

/**
 * Test that event adding works
 *
 * @covers CrudSubject::addEvent
 * @covers CrudSubject::getEvents
 * @covers CrudSubject::hasEvent
 * @return void
 */
	public function testEventNames() {
		$this->assertFalse($this->Subject->hasEvent('test'));
		$this->assertFalse($this->Subject->hasEvent('test_two'));
		$this->assertFalse($this->Subject->hasEvent('test_three'));
		$this->assertFalse($this->Subject->hasEvent('invalid'));

		$this->Subject->addEvent('test');
		$this->Subject->addEvent('test_two');
		$this->Subject->addEvent('test_three');
		$this->assertTrue($this->Subject->hasEvent('test'));
		$this->assertTrue($this->Subject->hasEvent('test_two'));
		$this->assertTrue($this->Subject->hasEvent('test_three'));
		$this->assertFalse($this->Subject->hasEvent('invalid'));

		$expected = array('test', 'test_two', 'test_three');
		$this->assertEquals($expected, $this->Subject->getEvents());
	}

/**
 * testInvalidMode
 *
 * @covers CrudSubject::shouldProcess
 * @expectedException CakeException
 * @expectedExceptionMessage Invalid mode
 * @return void
 */
	public function testInvalidMode() {
		$this->Subject->shouldProcess('invalid');
	}

}
