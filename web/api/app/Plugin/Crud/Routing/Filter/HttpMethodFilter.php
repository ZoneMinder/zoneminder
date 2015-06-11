<?php

App::uses('DispatcherFilter', 'Routing');

/**
 * HttpMethodFilter
 *
 * Automatically handle OPTIONS and HEAD requests
 */
class HttpMethodFilter extends DispatcherFilter {

/**
 * priority
 *
 * Run last, allowing for another filter to add more headers to the response if necessary
 * by running with a lower priority
 *
 * @var int
 */
	public $priority = 11;

/**
 * defaultVerbs
 *
 * The list of verbs to check, if not overwritten by calling:
 *
 * 	Configure::write('Crud.HttpMethodFilter.verbs', ['list', 'of', 'verbs']);
 *
 * @var array
 */
	public $defaultVerbs = array(
		'GET',
		'HEAD',
		'POST',
		'PUT',
		'DELETE',
	);

/**
 * Handle OPTIONS requests
 *
 * If it's an options request, loop on the configured http verbs and add
 * an Access-Control-Allow-Methods header with the verbs the application
 * is configured to respond to.
 *
 * @param CakeEvent $event
 * @return CakeResponse|null
 */
	public function beforeDispatch(CakeEvent $event) {
		$request = $event->data['request'];

		if (!$request->is('options')) {
			return;
		}

		$event->stopPropagation();

		$url = $request->url;
		$verbs = Configure::read('Crud.HttpMethodFilter.verbs') ?: $this->defaultVerbs;
		$allowedMethods = array();

		foreach ($verbs as $verb) {
			$_SERVER['REQUEST_METHOD'] = $verb;
			if (Router::parse('/' . $url)) {
				$allowedMethods[] = $verb;
			}
		}
		$_SERVER['REQUEST_METHOD'] = 'OPTIONS';

		$response = $event->data['response'];
		$response->header('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
		return $response;
	}

/**
 * Handle HEAD requests
 *
 * A head request cannot have a body, if it hasn't been handled automatically it's
 * assumed to have been handled as a GET request. Remove the body before responding,
 * and add a content-length header
 *
 * @param CakeEvent $event
 * @return CakeResponse|null
 */
	public function afterDispatch(CakeEvent $event) {
		$request = $event->data['request'];

		if (!$request->is('head')) {
			return;
		}

		$response = $event->data['response'];

		$headers = $response->header();
		$length = isset($headers['Content-length']) ? $headers['Content-length'] : null;

		$bodyLength = strlen($response->body());
		if ($length === null && $bodyLength) {
			$response->header('Content-length', $bodyLength);
		}
		$response->body('');
		return $response;
	}
}
