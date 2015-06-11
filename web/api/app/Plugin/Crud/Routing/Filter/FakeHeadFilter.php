<?php

App::uses('DispatcherFilter', 'Routing');

/**
 * Make HEAD requests be treated as GET requests
 *
 * This filter is intended to be used with the HttpMethod filter
 */
class FakeHeadFilter extends DispatcherFilter {

/**
 * priority
 *
 * Run before any default-priority filters
 *
 * @var int
 */
	public $priority = 9;

/**
 * Is the request really a HEAD request?
 *
 * @var mixed
 */
	protected $_isHead;

/**
 * Make application see a GET request instead of a HEAD request
 *
 * If the application considers HEAD an unsupported method, this allows
 * the application to see/treat them as GET requests
 *
 * @param CakeEvent $event
 * @return CakeResponse|null
 */
	public function beforeDispatch(CakeEvent $event) {
		$request = $event->data['request'];

		$this->_isHead = $request->is('head');
		if ($this->_isHead) {
			$this->_requestMethod = $request->method();
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}
	}

/**
 * Rewrite the REQUEST_METHOD if it was a head request
 *
 * So that any subsequent dispatch filter logic knows it was a head request
 *
 * @param CakeEvent $event
 * @return CakeResponse|null
 */
	public function afterDispatch(CakeEvent $event) {
		if ($this->_isHead) {
			$_SERVER['REQUEST_METHOD'] = 'HEAD';
		}
	}
}
