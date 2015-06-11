<?php

App::uses('View', 'View');
App::uses('JsonView', 'View');

/**
 * CrudApiView
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 * @codeCoverageIgnore Backport of 2.4 JsonView aliasing
 */
class CrudJsonView extends JsonView {

/**
 * Serialize view vars
 *
 * @param array $serialize The viewVars that need to be serialized
 * @return string The serialized data
 */
	protected function _serialize($serialize) {
		if (is_array($serialize)) {
			$data = array();

			foreach ($serialize as $alias => $key) {
				if (is_numeric($alias)) {
					$alias = $key;
				}

				if (array_key_exists($key, $this->viewVars)) {
					$data[$alias] = $this->viewVars[$key];
				}
			}

			$data = !empty($data) ? $data : null;
		} else {
			$data = isset($this->viewVars[$serialize]) ? $this->viewVars[$serialize] : null;
		}

		if (version_compare(PHP_VERSION, '5.4.0', '>=') && Configure::read('debug')) {
			return json_encode($data, JSON_PRETTY_PRINT);
		}

		return json_encode($data);
	}

}
