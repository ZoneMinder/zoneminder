<?php

App::uses('ConnectionManager', 'Model');
App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * When loaded Crud API will include query logs in the response
 *
 * Very much like the DebugKit version, the SQL log will only be appended
 * if the following conditions is true:
 *  1) The request must be 'api' (.json/.xml)
 *  2) The debug level must be 2 or above
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiQueryLogListener extends CrudListener {

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
			'Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 75)
		);
	}

/**
 * Appends the query log to the JSON or XML output
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeRender(CakeEvent $event) {
		if (Configure::read('debug') < 2) {
			return;
		}

		if (!$this->_request()->is('api')) {
			return;
		}

		$this->_action()->config('serialize.queryLog', 'queryLog');

		$queryLog = $this->_getQueryLogs();
		$this->_controller()->set('queryLog', $queryLog);
	}

/**
 * Get the query logs for all sources
 *
 * @return array
 */
	protected function _getQueryLogs() {
		if (!class_exists('ConnectionManager', false)) {
			return array();
		}

		$sources = $this->_getSources();
		$queryLog = array();
		foreach ($sources as $source) {
			$db = $this->_getSource($source);

			if (!method_exists($db, 'getLog')) {
				continue;
			}

			$queryLog[$source] = $db->getLog(false, false);
		}

		return $queryLog;
	}

/**
 * Get a list of sources defined in database.php
 *
 * @codeCoverageIgnore
 * @return array
 */
	protected function _getSources() {
		return ConnectionManager::sourceList();
	}

/**
 * Get a specific data source
 *
 * @codeCoverageIgnore
 * @param string $source Datasource name
 * @return DataSource
 */
	protected function _getSource($source) {
		return ConnectionManager::getDataSource($source);
	}

}
