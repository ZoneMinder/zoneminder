<?php

App::uses('CakeEventListener', 'Event');
App::uses('Hash', 'Utility');

/**
 * Crud Base Class
 *
 * Implement base methods used in CrudAction and CrudListener classes
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
abstract class CrudBaseObject extends Object implements CakeEventListener {

/**
 * Container with reference to all objects
 * needed within the CrudListener and CrudAction
 *
 * @var CrudSubject
 */
	protected $_container;

/**
 * Instance configuration
 *
 * @var array
 */
	protected $_settings = array();

/**
 * Constructor
 *
 * @param CrudSubject $subject
 * @param array $defaults Default settings
 * @return void
 */
	public function __construct(CrudSubject $subject, $defaults = array()) {
		$this->_container = $subject;

		if (!empty($defaults)) {
			$this->config($defaults);
		}
	}

/**
 * initialize callback
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeHandle(CakeEvent $event) {
		$this->_container = $event->subject;
	}

/**
 * Sets a configuration variable into this action
 *
 * If called with no arguments, all configuration values are
 * returned.
 *
 * $key is interpreted with dot notation, like the one used for
 * Configure::write()
 *
 * If $key is string and $value is not passed, it will return the
 * value associated with such key.
 *
 * If $key is an array and $value is empty, then $key will
 * be interpreted as key => value dictionary of settings and
 * it will be merged directly with $this->settings
 *
 * If $key is a string, the value will be inserted in the specified
 * slot as indicated using the dot notation
 *
 * @param mixed $key
 * @param mixed $value
 * @param boolean $merge
 * @return mixed|CrudAction
 */
	public function config($key = null, $value = null, $merge = true) {
		if ($key === null && $value === null) {
			return $this->_settings;
		}

		if ($value === null) {
			if (is_array($key)) {
				if ($merge) {
					$this->_settings = Hash::merge($this->_settings, $key);
				} else {
					foreach (Hash::flatten($key) as $k => $v) {
						$this->_settings = Hash::insert($this->_settings, $k, $v);
					}
				}

				return $this;
			}

			return Hash::get($this->_settings, $key);
		}

		if (is_array($value)) {
			if ($merge) {
				$value = array_merge((array)Hash::get($this->_settings, $key), $value);
			} else {
				foreach ($value as $k => $v) {
					$this->_settings = Hash::insert($this->_settings, $k, $v);
				}
			}
		}

		$this->_settings = Hash::insert($this->_settings, $key, $value);
		return $this;
	}

/**
 * Returns a list of all events that will fire during the objects lifecycle.
 * You can override this function to add you own listener callbacks
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Crud.initialize' => 'initialize'
		);
	}

/**
 * Proxy method for `$this->_crud()->action()`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @param string $name
 * @return CrudAction
 */
	protected function _action($name = null) {
		return $this->_crud()->action($name);
	}

/**
 * Proxy method for `$this->_crud()->trigger()`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @param string $eventName
 * @param array $data
 * @return CrudSubject
 */
	protected function _trigger($eventName, $data = array()) {
		return $this->_crud()->trigger($eventName, $data);
	}

/**
 * Proxy method for `$this->_crud()->listener()`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @param string $name
 * @return CrudListener
 */
	protected function _listener($name) {
		return $this->_crud()->listener($name);
	}

/**
 * Proxy method for `$this->_crud()->Session`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @return SessionComponent
 */
	protected function _session() {
		return $this->_crud()->Session;
	}

/**
 * Proxy method for `$this->_container->_controller`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @return Controller
 */
	protected function _controller() {
		return $this->_container->controller;
	}

/**
 * Proxy method for `$this->_container->_request`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @return CakeRequest
 */
	protected function _request() {
		return $this->_container->request;
	}

/**
 * Proxy method for `$this->_container->_model`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @return Model
 */
	protected function _model() {
		return $this->_container->model;
	}

/**
 * Proxy method for `$this->_crud()->getSubject()`
 *
 * @codeCoverageIgnore
 * @param array $additional
 * @return CrudSUbject
 */
	protected function _subject($additional = array()) {
		return $this->_crud()->getSubject($additional);
	}

/**
 * Proxy method for `$this->_container->_crud`
 *
 * @return CrudComponent
 */
	protected function _crud() {
		return $this->_container->crud;
	}

/**
 * Proxy method for `$this->_crud()->validationErrors()`
 *
 * Primarily here to ease unit testing
 *
 * @codeCoverageIgnore
 * @return array
 */
	protected function _validationErrors() {
		return $this->_crud()->validationErrors();
	}

/**
 * Returns the redirect_url for this request, with a fallback to the referring page
 *
 * @param string $default Default URL to use redirect_url is not found in request or data
 * @param boolean $local If true, restrict referring URLs to local server
 * @return mixed
 */
	protected function _refererRedirectUrl($default = null) {
		$controller = $this->_controller();
		return $this->_redirectUrl($controller->referer($default, true));
	}

/**
 * Returns the redirect_url for this request.
 *
 * @param string $default Default URL to use redirect_url is not found in request or data
 * @return mixed
 */
	protected function _redirectUrl($default = null) {
		$url = $default;
		$request = $this->_request();
		if (!empty($request->data['redirect_url'])) {
			$url = $request->data['redirect_url'];
		} elseif (!empty($request->query['redirect_url'])) {
			$url = $request->query['redirect_url'];
		}

		return $url;
	}

}
