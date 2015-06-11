<?php

App::uses('HttpMethodFilter', 'Crud.Routing/Filter');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');

/**
 * HttpMethodFilterTest
 *
 */
class HttpMethodFilterTest extends CakeTestCase {

/**
 * testNoop
 *
 * @return void
 */
	public function testNoop() {
		Router::reload();
		Router::connect('/:controller/:action/*');

		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$request = new CakeRequest('controller/action/1');
		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertNull($filter->beforeDispatch($event), 'The HttpMethod filter should return null if it does nothing');
		$this->assertFalse($event->isStopped(), 'The HttpMethod filter should not stop the event for !OPTIONS requests');
		$this->assertNull($filter->afterDispatch($event), 'The HttpMethod filter should return null if it does nothing');
	}

/**
 * testOptions
 *
 * @return void
 */
	public function testOptions() {
		Router::reload();
		Router::connect('/:controller/:action/*');

		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		$request = new CakeRequest('controller/action/1');
		$request->addDetector('options', array(
			'callback' => function() {
				return true;
			}
		));

		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->beforeDispatch($event), 'The HttpMethod filter should return a response');
		$this->assertTrue($event->isStopped(), 'The HttpMethod filter should stop the event');

		$expected = array(
			'Access-Control-Allow-Methods' => 'GET, HEAD, POST, PUT, DELETE'
		);
		$this->assertSame($expected, $response->header(), 'A standard route accepts all verbs');
	}

/**
 * testOptionsRestrictedVerbs
 *
 * @return void
 */
	public function testOptionsRestrictedVerbs() {
		Router::reload();
		Router::connect('/:controller/:action/*', array('[method]' => 'GET'));

		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		$request = new CakeRequest('controller/action/1');
		$request->addDetector('options', array(
			'callback' => function() {
				return true;
			}
		));

		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->beforeDispatch($event), 'The HttpMethod filter should return a response');
		$this->assertTrue($event->isStopped(), 'The HttpMethod filter should stop the event');

		$expected = array(
			'Access-Control-Allow-Methods' => 'GET'
		);
		$this->assertSame($expected, $response->header(), 'Only verbs for matching routes should be returned');
	}

/**
 * testOptionsCustomVerbs
 *
 * @return void
 */
	public function testOptionsCustomVerbs() {
		Router::reload();
		Router::connect('/:controller/:action/*', array('[method]' => 'TICKLE'));
		Router::connect('/:controller/:action/*', array('[method]' => 'ANNOY'));

		Configure::write('Crud.HttpMethodFilter.verbs', array('GET', 'TICKLE', 'ANNOY'));

		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		$request = new CakeRequest('controller/action/1');
		$request->addDetector('options', array(
			'callback' => function() {
				return true;
			}
		));

		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->beforeDispatch($event), 'The HttpMethod filter should return a response');
		$this->assertTrue($event->isStopped(), 'The HttpMethod filter should stop the event');

		$expected = array(
			'Access-Control-Allow-Methods' => 'TICKLE, ANNOY'
		);
		$this->assertSame($expected, $response->header(), 'A verbs for matching routes should be returned');
	}

/**
 * testHead
 *
 * Simulate a get request, return a head response
 *
 * @return void
 */
	public function testHead() {
		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$response->body('some content');

		$request = new CakeRequest('controller/action/1');
		$request->addDetector('head', array(
			'callback' => function() {
				return true;
			}
		));

		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->afterDispatch($event), 'The HttpMethod filter should return a response');
		$expected = array(
			'Content-length' => '12'
		);
		$this->assertSame($expected, $response->header(), 'The content header should be set');
		$this->assertSame('', $response->body(), 'The body should be removed');
	}

/**
 * testHeadEmpty
 *
 * If there's no body, don't assume a GET request for it would be empty
 *
 * @return void
 */
	public function testHeadEmpty() {
		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));

		$request = new CakeRequest('controller/action/1');
		$request->addDetector('head', array(
			'callback' => function() {
				return true;
			}
		));

		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->afterDispatch($event), 'The HttpMethod filter should return a response');
		$expected = array();
		$this->assertSame($expected, $response->header(), 'There is no body, the content-length header should be empty');
		$this->assertSame('', $response->body(), 'The body should be removed');
	}
/**
 * testHeadHandled
 *
 * Simulate app code having handled the head request appropriately
 *
 * @return void
 */
	public function testHeadHandled() {
		$filter = new HttpMethodFilter();
		$response = $this->getMock('CakeResponse', array('_sendHeader'));
		$response->header('Content-length', 123);

		$request = new CakeRequest('controller/action/1');
		$request->addDetector('head', array(
			'callback' => function() {
				return true;
			}
		));

		$event = new CakeEvent('HttpMethodFilterTest', $this, compact('request', 'response'));

		$this->assertSame($response, $filter->afterDispatch($event), 'The HttpMethod filter should return a response');
		$expected = array(
			'Content-length' => '123'
		);
		$this->assertSame($expected, $response->header(), 'The content header should be set');
		$this->assertSame('', $response->body(), 'The body should remain empty');
	}
}
