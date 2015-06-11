<?php

App::uses('CrudListener', 'Crud.Controller/Crud');
App::uses('CrudValidationException', 'Crud.Error/Exception');

/**
 * Enabled Crud to respond in a computer readable format like JSON or XML
 *
 * It tries to enforce some REST principles and keep some string conventions in the output format
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiListener extends CrudListener {

/**
 * Default configuration
 *
 * @var array
 */
	protected $_settings = array(
		'viewClasses' => array(
			'json' => 'Crud.CrudJson',
			'xml' => 'Crud.CrudXml'
		),
		'detectors' => array(
			'json' => array('ext' => 'json', 'accepts' => 'application/json'),
			'xml' => array('ext' => 'xml', 'accepts' => 'text/xml')
		),
		'exception' => array(
			'type' => 'default',
			'class' => 'BadRequestException',
			'message' => 'Unknown error',
			'code' => 0
		)
	);

/**
 * Returns a list of all events that will fire in the controller during its lifecycle.
 * You can override this function to add you own listener callbacks
 *
 * We attach at priority 10 so normal bound events can run before us
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Crud.beforeHandle' => array('callable' => 'beforeHandle', 'priority' => 10),
			'Crud.setFlash' => array('callable' => 'setFlash', 'priority' => 5),

			'Crud.beforeRender' => array('callable' => 'respond', 'priority' => 100),
			'Crud.beforeRedirect' => array('callable' => 'respond', 'priority' => 100)
		);
	}

/**
 * setup
 *
 * Called when the listener is created
 *
 * @return void
 */
	public function setup() {
		$this->setupDetectors();
		$this->registerExceptionHandler();
	}

/**
 * beforeHandle
 *
 * Called before the crud action is executed
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeHandle(CakeEvent $event) {
		parent::beforeHandle($event);

		if (!$this->_request()->is('api')) {
			$events = $this->implementedEvents();
			$eventManager = $this->_controller()->getEventManager();
			foreach (array_keys($events) as $name) {
				if ($name === 'Crud.beforeHandle') {
					continue;
				}
				$eventManager->detach($this, $name);
			}
			return;
		}

		$this->_checkRequestMethods();
	}

/**
 * Check for allowed HTTP request types
 *
 * @throws BadRequestException
 * @return boolean
 */
	protected function _checkRequestMethods() {
		$action = $this->_action();
		$apiConfig = $action->config('api');

		if (!isset($apiConfig['methods'])) {
			return false;
		}

		$request = $this->_request();
		foreach ($apiConfig['methods'] as $method) {
			if ($request->is($method)) {
				return true;
			}
		}

		throw new BadRequestException('Wrong request method');
	}

/**
 * Register the Crud exception handler
 *
 * @return void
 */
	public function registerExceptionHandler() {
		if (!$this->_request()->is('api')) {
			return;
		}

		App::uses('CrudExceptionRenderer', 'Crud.Error');
		Configure::write('Exception.renderer', 'Crud.CrudExceptionRenderer');
	}

/**
 * Handle response
 *
 * @param CakeEvent $event
 * @return CakeResponse
 */
	public function respond(CakeEvent $event) {
		$subject = $event->subject;
		$action = $this->_action();

		$key = $subject->success ? 'success' : 'error';
		$apiConfig = $action->config('api.' . $key);

		if (isset($apiConfig['exception'])) {
			return $this->_exceptionResponse($apiConfig['exception']);
		}

		$response = $this->render($event->subject);
		$response->statusCode($apiConfig['code']);
		return $response;
	}

/**
 * Throw an exception based on API configuration
 *
 * @throws CakeException
 * @param array $exceptionConfig
 * @return void
 */
	protected function _exceptionResponse($exceptionConfig) {
		$exceptionConfig = array_merge($this->config('exception'), $exceptionConfig);

		$class = $exceptionConfig['class'];

		if ($exceptionConfig['type'] === 'validate') {
			$errors = $this->_validationErrors();
			throw new $class($errors);
		}

		throw new $class($exceptionConfig['message'], $exceptionConfig['code']);
	}

/**
 * Selects an specific Crud view class to render the output
 *
 * @param CrudSubject $subject
 * @return CakeResponse
 */
	public function render(CrudSubject $subject) {
		$this->injectViewClasses();
		$this->_ensureSuccess($subject);
		$this->_ensureData($subject);
		$this->_ensureSerialize();

		$controller = $this->_controller();
		if (!empty($controller->RequestHandler->ext)) {
			$controller->RequestHandler->renderAs($controller, $controller->RequestHandler->ext);
		}

		return $controller->render();
	}

/**
 * Ensure _serialize is set in the view
 *
 * @return void
 */
	protected function _ensureSerialize() {
		$controller = $this->_controller();

		if (isset($controller->viewVars['_serialize'])) {
			return;
		}

		$action = $this->_action();

		$serialize = array();
		$serialize[] = 'success';

		if (method_exists($action, 'viewVar')) {
			$serialize['data'] = $action->viewVar();
		} else {
			$serialize[] = 'data';
		}

		$serialize = array_merge($serialize, (array)$action->config('serialize'));
		$controller->set('_serialize', $serialize);
	}

/**
 * Ensure success key is present in Controller::$viewVars
 *
 * @param CrudSubject $subject
 * @return void
 */
	protected function _ensureSuccess(CrudSubject $subject) {
		$controller = $this->_controller();

		if (isset($controller->viewVars['success'])) {
			return;
		}

		$controller->set('success', $subject->success);
	}

/**
 * Ensure data key is present in Controller:$viewVars
 *
 * @param CrudSubject $subject
 * @return void
 */
	protected function _ensureData(CrudSubject $subject) {
		$controller = $this->_controller();

		// Don't touch existing data properties
		if (isset($controller->viewVars['data'])) {
			return;
		}

		$key = $subject->success ? 'success' : 'error';

		// Load configuration
		$config = $this->_action()->config('api.' . $key);

		// New, empty, data array
		$data = array();

		// If fields should be extracted from the subject
		if (isset($config['data']['subject'])) {
			$config['data']['subject'] = Hash::normalize((array)$config['data']['subject']);

			$subjectArray = (array)$subject;

			foreach ($config['data']['subject'] as $keyPath => $valuePath) {
				if ($valuePath === null) {
					$valuePath = $keyPath;
				}

				$keyPath = $this->_expandPath($subject, $keyPath);
				$valuePath = $this->_expandPath($subject, $valuePath);

				$data = Hash::insert($data, $keyPath, Hash::get($subjectArray, $valuePath));
			}
		}

		// Raw (hardcoded) key/values
		if (isset($config['data']['raw'])) {

			foreach ($config['data']['raw'] as $path => $value) {
				$path = $this->_expandPath($subject, $path);
				$data = Hash::insert($data, $path, $value);
			}

		}

		// Publish the new data
		$controller->set('data', $data);
	}

/**
 * Expand all scalar values from a CrudSubject
 * and use them for a String::insert() interpolation
 * of a path
 *
 * @param CrudSubject $subject
 * @param string $path
 * @return string
 */
	protected function _expandPath(CrudSubject $subject, $path) {
		$keys = array();
		$subjectArray = (array)$subject;

		foreach (array_keys($subjectArray) as $key) {
			if (!is_scalar($subjectArray[$key])) {
				continue;
			}

			$keys[$key] = $subjectArray[$key];
		}

		return String::insert($path, $keys, array('before' => '{', 'after' => '}'));
	}

/**
 * Inject view classes into RequestHandler
 *
 * @see http://book.cakephp.org/2.0/en/core-libraries/components/request-handling.html#using-custom-viewclasses
 * @return void
 */
	public function injectViewClasses() {
		$controller = $this->_controller();
		foreach ($this->config('viewClasses') as $type => $class) {
			$controller->RequestHandler->viewClassMap($type, $class);
		}
	}

/**
 * Get or set a viewClass
 *
 * `$type` could be `json`, `xml` or any other valid type
 * 		defined by the `RequestHandler`
 *
 * `$class` could be any View class capable of handling
 * 		the response format for the `$type`. Normal
 * 		CakePHP plugin "dot" notation is supported
 *
 * @see http://book.cakephp.org/2.0/en/core-libraries/components/request-handling.html#using-custom-viewclasses
 * @param string $type
 * @param string $class
 * @return mixed
 */
	public function viewClass($type, $class = null) {
		if ($class === null) {
			return $this->config('viewClasses.' . $type);
		}

		return $this->config('viewClasses.' . $type, $class);
	}

/**
 * setFlash
 *
 * An API request doesn't need flash messages - so stop them being processed
 *
 * @param CakeEvent $event
 */
	public function setFlash(CakeEvent $event) {
		$event->stopPropagation();
	}

/**
 * Setup detectors
 *
 * Both detects on two signals:
 *  1) The extension in the request (e.g. /users/index.$ext)
 *  2) The accepts header from the client
 *
 * There is a combined request detector for all detectors called 'api'
 *
 * @return void
 */
	public function setupDetectors() {
		$request = $this->_request();
		$detectors = $this->config('detectors');

		foreach ($detectors as $name => $config) {

			$request->addDetector($name, array('callback' => function(CakeRequest $request) use ($config) {
				if (isset($request->params['ext']) && $request->params['ext'] === $config['ext']) {
					return true;
				}

				return $request->accepts($config['accepts']);
			}));

		}

		$request->addDetector('api', array('callback' => function(CakeRequest $request) use ($detectors) {
			foreach ($detectors as $name => $config) {
				if ($request->is($name)) {
					return true;
				}
			}

			return false;
		}));
	}

/**
 * Automatically create REST resource routes for all controllers found in your main
 * application or in a specific plugin to provide access to your resources
 * using /controller/id.json instead of the default /controller/view/id.json.
 *
 * If called with no arguments, all controllers in the main application will be mapped.
 * If called with a valid plugin name all controllers in that plugin will be mapped.
 * If combined both controllers from the application and the plugin(s) will be mapped.
 *
 * This function needs to be called from your application's app/Config/routes.php:
 *
 * ```
 *     App::uses('ApiListener', 'Crud.Controller/Crud/Listener');
 *
 *     ApiListener::mapResources();
 *     ApiListener::mapResources('DebugKit');
 *     Router::setExtensions(array('json', 'xml'));
 *     Router::parseExtensions();
 * ```
 *
 * @static
 * @param string $plugin
 * @return void
 */
	public static function mapResources($plugin = null) {
		$key = 'Controller';
		if ($plugin) {
			$key = $plugin . '.Controller';
		}

		$controllers = array();
		foreach (App::objects($key) as $controller) {
			if ($controller !== $plugin . 'AppController') {
				if ($plugin) {
					$controller = $plugin . '.' . $controller;
				}

				array_push($controllers, str_replace('Controller', '', $controller));
			}
		}

		Router::mapResources($controllers);
	}
}
