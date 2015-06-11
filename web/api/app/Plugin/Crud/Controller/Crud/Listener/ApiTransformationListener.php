<?php
/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */

App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * PublicApiListener
 *
 * Listener to format data consistent with most public
 * APIs out there such as Twitter, GitHub and Google.
 *
 * - https://dev.twitter.com/docs/api/1.1/get/statuses/mentions_timeline
 * - http://developer.github.com/v3/
 * - https://developers.google.com/custom-search/v1/using_rest
 */
class ApiTransformationListener extends CrudListener {

/**
 * Default settings.
 *
 * - changeNesting boolean
 *   Removes the top level model name and nests the associated
 *   records inside the primary record.
 *
 * - changeKeys boolean
 *   Changes keys to lowercase model names using the associations
 *   or if not empty the replaceMap setting.
 *
 * - changeTime boolean
 *   Changes datetime strings to unix time integers.
 *
 * - castNumbers boolean
 *   Casts numeric strings to the right datatype.
 *
 * - keyMethods array
 *   List of function names, closures or callback arrays to call
 *   for the keys when recursing through the data.
 *
 * - valueMethods array
 *   List of function names, closures or callback arrays to call
 *   for the values when recursing through the data.
 *
 * - replaceMap array
 *   List of key-value pairs to use for key replacement. Keep
 *   this empty to have it derive from the model associations.
 *
 * @var array
 */
	protected $_settings = array(
		'changeNesting' => true,
		'changeKeys' => true,
		'changeTime' => true,
		'castNumbers' => true,

		'keyMethods' => array(),
		'valueMethods' => array(),
		'replaceMap' => array()
	);

/**
 * Adds the Crud.beforeRender event. It has a high priority
 * number to make sure it is called late/last.
 *
 * @return array
 */
	public function implementedEvents() {
		return array('Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 200));
	}

/**
 * After everything is done and before anything is rendered change
 * the data format.
 *
 * @return boolean
 */
	public function beforeRender() {
		if (!$this->_request()->is('api')) {
			return true;
		}

		$viewVars = $this->_controller()->viewVars;
		$viewVar = $this->_action()->viewVar();

		if (empty($viewVars[$viewVar])) {
			return true;
		}

		$this->_setMethods();

		$alias = $this->_model()->alias;
		$data = $viewVars[$viewVar];
		$wrapped = false;

		if (isset($data[$alias])) {
			$data = array($data);
			$wrapped = true;
		}

		$formatted = array();
		foreach ($data as $index => &$record) {
			$new = &$record;
			if ($this->_settings['changeNesting']) {
				$new = $this->_changeNesting($new, $alias);
			}
			unset($data[$index]);
			$this->_recurse($new, $index);
			$formatted[] = $new;
		}
		$formatted = $wrapped ? $formatted[0] : $formatted;

		$this->_controller()->set($viewVar, $formatted);

		return true;
	}

/**
 * Merge in the internal methods based on the settings.
 *
 * @return void
 */
	protected function _setMethods() {
		$keyMethods = $valueMethods = array();

		if ($this->_settings['changeKeys']) {
			$keyMethods[] = '_replaceKeys';
		}

		if ($this->_settings['castNumbers']) {
			$valueMethods[] = '_castNumbers';
		}

		if ($this->_settings['changeTime']) {
			$valueMethods[] = '_changeDateToUnix';
		}

		$this->_settings['keyMethods'] = array_merge($keyMethods, $this->_settings['keyMethods']);
		$this->_settings['valueMethods'] = array_merge($valueMethods, $this->_settings['valueMethods']);
	}

/**
 * Calls a method. Optimizes where possible because of the
 * large number of calls through this method.
 *
 * @param string|Closure|array $method
 * @param mixed $variable
 * @param mixed $key
 * @return mixed
 */
	protected function _call($method, &$variable, $key) {
		if (is_string($method) && method_exists($this, $method)) {
			return $this->$method($variable, $key);
		}

		if ($method instanceof Closure) {
			return $method($variable, $key);
		}

		return call_user_func($method, $variable, $key);
	}

/**
 * Recurse through an array and apply key changes and casts.
 *
 * @param mixed $variable
 * @param mixed $key
 * @return void
 */
	protected function _recurse(&$variable, $key = null) {
		if (is_array($variable)) {
			foreach ($this->_settings['keyMethods'] as $method) {
				$variable = $this->_call($method, $variable, $key);
			}

			foreach ($variable as $k => &$value) {
				$this->_recurse($value, $key === null ? $k : "$key.$k");
			}

			return;
		}

		foreach ($this->_settings['valueMethods'] as $method) {
			$variable = $this->_call($method, $variable, $key);
		}
	}

/**
 * Nests the secondary models in the array of the
 * primary model.
 *
 * Might overwrite array keys if model field names have the
 * same name as the secondary model.
 *
 * @param array $record
 * @param string $primaryAlias
 * @return array
 */
	protected function _changeNesting(array $record, $primaryAlias) {
		$new = $record[$primaryAlias];
		unset($record[$primaryAlias]);
		$new += $record;
		return $new;
	}

/**
 * Replaces array keys for associated records.
 *
 * Might overwrite array keys if model field names have the
 * same name as the secondary model.
 *
 * Example
 * =======
 *
 * Replacing the array keys for the following associations:
 *
 * User hasMany Comment
 * Comment belongsTo Post
 *
 * The array keys that will replaced:
 *
 * Comment -> comments (plural)
 *   Post -> post (singular)
 *
 * @param array $variable
 * @param string|integer $key
 * @param mixed $value
 * @return void
 */
	protected function _replaceKeys(array $variable) {
		if (empty($this->_settings['replaceMap'])) {
			$this->_settings['replaceMap'] = $this->_getReplaceMapFromAssociations();
		}

		$keys = array_keys($variable);
		$replaced = false;

		foreach ($keys as &$key) {
			if (!is_string($key) || !is_array($variable[$key])) {
				continue;
			}

			if (!isset($this->_settings['replaceMap'][$key])) {
				continue;
			}

			$key = $this->_settings['replaceMap'][$key];
			$replaced = true;
		}

		if (!$replaced) {
			return $variable;
		}

		return array_combine($keys, array_values($variable));
	}

/**
 * Get a key-value map with replacements for the model keys.
 * The replacements are derived from the associations.
 *
 * @param Model $model
 * @param array $map
 * @return boolean|array
 */
	protected function _getReplaceMapFromAssociations(Model $model = null, array $map = null) {
		if ($model === null) {
			$model = $this->_model();
		}

		if ($map === null) {
			$map = array($model->alias => Inflector::singularize(Inflector::tableize($model->alias)));
		}

		foreach ($model->associations() as $type) {
			foreach ($model->{$type} as $alias => &$association) {
				if (isset($map[$alias]) || !property_exists($model, $alias)) {
					continue;
				}

				$key = Inflector::tableize($alias);
				if ($type === 'belongsTo' || $type === 'hasOne') {
					$key = Inflector::singularize($key);
				}

				$map[$alias] = $key;
				$map = $this->_getReplaceMapFromAssociations($model->{$alias}, $map);
			}
		}

		return $map;
	}

/**
 * Change "1" to 1, and "123.456" to 123.456.
 *
 * @param mixed $variable
 * @return void
 */
	protected function _castNumbers($variable) {
		if (!is_numeric($variable)) {
			return $variable;
		}
		return $variable + 0;
	}

/**
 * Converts database dates to unix times.
 *
 * @param mixed $variable
 * @return integer
 */
	protected function _changeDateToUnix($variable) {
		if (!is_string($variable)) {
			return $variable;
		}

		if (!preg_match('@^\d{4}-\d{2}-\d{2}@', $variable)) {
			return $variable;
		}

		return strtotime($variable);
	}
}
