<?php

App::uses('FakeHeadFilter', 'Crud.Routing/Filter');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * FakeHeadFilterTest
 *
 */
class FakeHeadFilterTest extends CakeTestCase {

/**
 * testNoop
 *
 * @return void
 */
	public function testNoop() {
		$filter = new FakeHeadFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$request = new CakeRequest('/');
		$event = new CakeEvent('FakeHeadFilterTest', $this, compact('request', 'response'));

		$_SERVER['REQUEST_METHOD'] = 'ORIGINAL';

		$this->assertNull($filter->beforeDispatch($event), 'No action should be taken, nothing returned');
		$this->assertSame('ORIGINAL', $_SERVER['REQUEST_METHOD'], 'Request method should be unmodified');
		$this->assertNull($filter->afterDispatch($event), 'No action should be taken, nothing returned');
		$this->assertSame('ORIGINAL', $_SERVER['REQUEST_METHOD'], 'Request method should be unmodified');
	}

/**
 * testHead
 *
 * Simulate a get request, return a head response
 *
 * @return void
 */
	public function testHead() {
		$filter = new FakeHeadFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$request = new CakeRequest('/');
		$event = new CakeEvent('FakeHeadFilterTest', $this, compact('request', 'response'));
		$_SERVER['REQUEST_METHOD'] = 'HEAD';

		$this->assertNull($filter->beforeDispatch($event), 'No action should be taken, nothing returned');
		$this->assertSame('GET', $_SERVER['REQUEST_METHOD'], 'Request method should now be GET');
		$this->assertNull($filter->afterDispatch($event), 'No action should be taken, nothing returned');
		$this->assertSame('HEAD', $_SERVER['REQUEST_METHOD'], 'Request method should now be back to HEAD');
	}
}
