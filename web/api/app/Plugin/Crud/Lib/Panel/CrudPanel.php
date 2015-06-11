<?php

App::uses('Debugger', 'Utility');
App::uses('DebugPanel', 'DebugKit.Lib');

/**
 * Crud debug panel in DebugKit
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class CrudPanel extends DebugPanel {

/**
 * Declare we are a plugin
 *
 * @var string
 */
	public $plugin = 'Crud';

/**
 * beforeRender callback
 *
 * @param Controller $controller
 * @return void
 */
	public function beforeRender(Controller $controller) {
		$component = $controller->Crud->config();

		if ($controller->Crud->isActionMapped()) {
			$Action = $controller->Crud->action();
			$action = $Action->config();
		}

		$eventManager = $controller->getEventManager();
		$eventLog = $controller->Crud->eventLog();
		$events = array();
		foreach ($eventLog as $event) {
			list($name, $data) = $event;

			$listeners = $eventManager->listeners($name);
			$callbacks = $this->_getCallbacks($listeners);
			$uName = $this->_getUniqueName($name, $events);
			$events[$uName] = array(
				'data' => $data,
				'callbacks' => $callbacks
			);
		}

		$listeners = array();
		foreach ($controller->Crud->config('listeners') as $listener => $value) {
			$listeners[$listener] = $controller->Crud->listener($listener)->config();
		}

		$controller->set('crudDebugKitData', compact('component', 'action', 'events', 'listeners'));
	}

/**
 * _getCallbacks
 *
 * Return all callbacks for a given event key
 *
 * @param array $listeners
 * @return array
 */
	protected function _getCallbacks($listeners) {
		foreach ($listeners as &$listener) {
			$listener = $listener['callable'];
			if (is_array($listener)) {
				$class = is_string($listener[0]) ? $listener[0] : get_class($listener[0]);
				$method = $listener[1];
				$listener = "$class::$method";
			} elseif ($listener instanceof Closure) {
				$listener = $this->_getClosureDefinition($listener);
			}
		}

		return $listeners;
	}

/**
 * Return where a closure has been defined
 *
 * If for some reason this doesn't work - it'll return the closure instance in the full knowledge
 * that it'll probably get dumped as the string "function"
 *
 * @param Closure $closure
 * @return mixed string or Closure
 */
	protected function _getClosureDefinition(Closure $closure) {
		$exported = ReflectionFunction::export($closure, true);
		preg_match('#@@ (.*) (\d+) - (\d+)#', $exported, $match);
		if (!$match) {
			return $closure;
		}

		list($m, $path, $start) = $match;

		$path = Debugger::trimPath($path);

		return "$path:$start";
	}

/**
 * _getUniqueName
 *
 * The name is used as an array key, ensure there are no collisions by adding a numerical
 * suffix if the given name already exists
 *
 * @param string $name
 * @param array $existing
 * @return string
 */
	protected function _getUniqueName($name, $existing) {
		$count = 1;
		$suffix = '';

		while (isset($existing[$name . $suffix])) {
			$suffix = ' #' . ++$count;
		}

		return $name . $suffix;
	}

}
