<?php
/**
 * Crud subject
 *
 * All Crud.* events passes this object as subject
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class CrudSubject {

/**
 * Instance of the crud component
 *
 * @var CrudComponent
 */
	public $crud;

/**
 * Instance of the controller
 *
 * @var Controller
 */
	public $controller;

/**
 * Name of the default controller model class
 *
 * @var string
 */
	public $modelClass;

/**
 * The default action model instance
 *
 * @var Model
 */
	public $model;

/**
 * Request object instance
 *
 * @var CakeRequest
 */
	public $request;

/**
 * Response object instance
 *
 * @var CakeResponse
 */
	public $response;

/**
 * The name of the action object associated with this dispatch
 *
 * @var string
 */
	public $action;

/**
 * Optional arguments passed to the controller action
 *
 * @var array
 */
	public $args;

/**
 * List of events this subject has passed through
 *
 * @var array
 */
	protected $_events = array();

/**
 * Constructor
 *
 * @param array $fields
 * @return void
 */
	public function __construct($fields = array()) {
		$this->set($fields);
	}

/**
 * Add an event name to the list of events this subject has passed through
 *
 * @param string $name name of event
 * @return void
 */
	public function addEvent($name) {
		$this->_events[] = $name;
	}

/**
 * Returns the list of events this subject has passed through
 *
 * @return array
 */
	public function getEvents() {
		return $this->_events;
	}

/**
 * Returns whether the specified event is in the list of events
 * this subject has passed through
 *
 * @param string $name name of event
 * @return array
 */
	public function hasEvent($name) {
		return in_array($name, $this->_events);
	}

/**
 * Set a list of key / values for this object
 *
 * @param array $fields
 * @return void
 */
	public function set($fields) {
		foreach ($fields as $k => $v) {
			$this->{$k} = $v;
		}
	}

/**
 * Check if the called action is white listed or blacklisted
 * depending on the mode
 *
 * Modes:
 * only => only if in array (white list)
 * not	=> only if NOT in array (blacklist)
 *
 * @param string $mode
 * @param mixed $actions
 * @return boolean
 * @throws CakeException In case of invalid mode
 */
	public function shouldProcess($mode, $actions = array()) {
		if (is_string($actions)) {
			$actions = array($actions);
		}

		switch ($mode) {
			case 'only':
				return in_array($this->action, $actions);

			case 'not':
				return !in_array($this->action, $actions);

			default:
				throw new CakeException('Invalid mode');
		}
	}

}
