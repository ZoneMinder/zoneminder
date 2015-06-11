<?php

App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * Field Filter Listener
 *
 * Allow the requester to decide what fields and relations that should be
 * returned by providing a `fields` GET argument with a comma separated list of fields.
 *
 * For a relation automatically to be joined, it has to be whitelisted first.
 * If no whitelist exists, no relations will be added automatically
 * `$this->_action()->config('apiFieldFilter.models', array('list', 'of', 'models'))`
 *
 * You can also whitelist fields, if no whitelist exists for fields, all fields are allowed
 * If whitelisting exists, only those fields will be allowed to be selected.
 * The fields must be in `Model.field` format
 * `$this->_action()->config('apiFieldFilter.fields.whitelist', array('Model.id', 'Model.name', 'Model.created'))`
 *
 * You can also blacklist fields, if no blacklist exists, no blacklisting is done
 * If blacklisting exists, the field will be removed from the field list if present
 * The fields must be in `Model.field` format
 * `$this->_action()->config('apiFieldFilter.fields.blacklist', array('Model.password', 'Model.auth_token', 'Model.created'))`
 *
 * This is probably only useful if it's used in conjunction with the ApiListener
 *
 * Limitation: Related models is only supported in 1 level away from the primary model at
 * this time. E.g. "Blog" => Auth, Tag, Posts
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiFieldFilterListener extends CrudListener {

/**
 * Returns a list of all events that will fire in the controller during its lifecycle.
 * You can override this function to add you own listener callbacks
 *
 * We attach at priority 50 so normal bound events can run before us
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Crud.beforePaginate' => array('callable' => 'beforePaginate', 'priority' => 50),
			'Crud.beforeFind' => array('callable' => 'beforeFind', 'priority' => 50)
		);
	}

/**
 * List of relations that should be contained
 *
 * @var array
 */
	protected $_relations = array();

/**
 * beforeFind
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeFind(CakeEvent $event) {
		if (!$this->_request()->is('api')) {
			return;
		}

		$fields = $this->_getFields($event);
		if (empty($fields)) {
			return;
		}

		$event->subject->query['fields'] = array_unique($fields);
		$event->subject->query['contain'] = $this->_relations;
	}

/**
 * beforePaginate
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforePaginate(CakeEvent $event) {
		if (!$this->_request()->is('api')) {
			return;
		}

		$fields = $this->_getFields($event);
		if (empty($fields)) {
			return;
		}

		$controller = $this->_controller();
		$controller->Paginator->settings['fields'] = $fields;
		$controller->Paginator->settings['contain'] = $this->_relations;
	}

/**
 * Whitelist fields that are allowed to be included in the
 * output list of fields
 *
 * @param array $fields
 * @param string $action
 * @return mixed
 */
	public function whitelistFields($fields = null, $action = null) {
		if (empty($fields)) {
			return $this->_action($action)->config('apiFieldFilter.fields.whitelist');
		}

		$this->_action($action)->config('apiFieldFilter.fields.whitelist', $fields);
	}

/**
 * Blacklist fields that are not allowed to be included in the
 * output list of fields
 *
 * @param array $fields
 * @param string $action
 * @return mixed
 */
	public function blacklistFields($fields = null, $action = null) {
		if (empty($fields)) {
			return $this->_action($action)->config('apiFieldFilter.fields.blacklist');
		}

		$this->_action($action)->config('apiFieldFilter.fields.blacklist', $fields);
	}

/**
 * Whitelist associated models that are allowed to be included in the
 * output list of fields
 *
 * @param array $models
 * @param string $action
 * @return mixed
 */
	public function whitelistModels($models = null, $action = null) {
		if (empty($models)) {
			return $this->_action($action)->config('apiFieldFilter.models.whitelist');
		}

		$this->_action($action)->config('apiFieldFilter.models.whitelist', $models);
	}

/**
 * Can the client make a request without specifying the fields he wants
 * returned?
 *
 * This will bypass all black- and white- listing if set to true
 *
 * @param boolean $permit
 * @param string $action
 * @return boolean
 */
	public function allowNoFilter($permit = null, $action = null) {
		if (empty($permit)) {
			return (bool)$this->_action($action)->config('apiFieldFilter.allowNoFilter');
		}

		$this->_action($action)->config('apiFieldFilter.allowNoFilter', (bool)$permit);
	}

/**
 * Get fields for the query
 *
 * @param CakeEvent $event
 * @return array
 * @throws CakeException If fields not specified
 */
	protected function _getFields(CakeEvent $event) {
		$this->_relations = array();

		$fields = $this->_getFieldsForQuery($this->_model());
		if (empty($fields) && !$this->allowNoFilter(null, $event->subject->action)) {
			throw new CakeException('Please specify which fields you would like to select');
		}

		return $fields;
	}

/**
 * Get the list of fields that should be selected
 * in the query based on the HTTP GET requests fields
 *
 * @param Model $model
 * @return array
 */
	protected function _getFieldsForQuery(Model $model) {
		$fields = $this->_getFieldsFromRequest();
		if (empty($fields)) {
			return;
		}

		$newFields = array();
		foreach ($fields as $field) {
			$fieldName = $this->_checkField($model, $field);

			// The field should not be included in the query
			if (empty($fieldName)) {
				continue;
			}

			$newFields[] = $fieldName;
		}

		return $newFields;
	}

/**
 * Get a list of fields from the HTTP request
 *
 * It's assumed the fields are comma separated
 *
 * @return array
 */
	protected function _getFieldsFromRequest() {
		$query = $this->_request()->query;
		if (empty($query['fields'])) {
			return;
		}

		return array_unique(array_filter(explode(',', $query['fields'])));
	}

/**
 * Secure a field - check that the field exists in the model
 * or a closely related model
 *
 * If the field doesn't exist, it's removed from the
 * field list.
 *
 * @param Model $model
 * @param string $field
 * @return mixed
 */
	protected function _checkField(Model $model, $field) {
		list ($modelName, $fieldName) = pluginSplit($field, false);

		// Prefix fields that don't have a model key with the local model name
		if (empty($modelName)) {
			$modelName = $model->alias;
		}

		$isPrimary = $modelName === $model->alias;

		// If the model name is the local one, check if the field exists
		if ($isPrimary && !$model->hasField($fieldName)) {
			return false;
		}

		// Check associated models if the field exists there
		if (!$isPrimary) {
			if (!$this->_associatedModelHasField($model, $modelName, $fieldName)) {
				return false;
			}
		}

		$fullFieldName = sprintf('%s.%s', $modelName, $fieldName);
		if (!$this->_whitelistedField($fullFieldName)) {
			return;
		}

		if ($this->_blacklistedField($fullFieldName)) {
			return;
		}

		if (!$isPrimary) {
			$this->_relations[] = $modelName;
		}

		return $fullFieldName;
	}

/**
 * Check if the associated `modelName` to the `$model`
 * exists and if it has the field in question
 *
 * @param Model $model
 * @param string $modelName
 * @param string $fieldName
 * @return boolean
 */
	protected function _associatedModelHasField(Model $model, $modelName, $fieldName) {
		$associated = $model->getAssociated();
		if (!array_key_exists($modelName, $associated)) {
			return false;
		}

		if (!$this->_whitelistedAssociatedModel($modelName)) {
			return false;
		}

		return $model->{$modelName}->hasField($fieldName);
	}

/**
 * Check if the associated model is whitelisted to be automatically
 * contained on demand or not
 *
 * If no whitelisting exists, no associated models may be joined
 *
 * @param string $modelName
 * @return boolean
 */
	protected function _whitelistedAssociatedModel($modelName) {
		$allowedModels = $this->whitelistModels();
		if (empty($allowedModels)) {
			return false;
		}

		return in_array($modelName, $allowedModels);
	}

/**
 * Check if a field has been whitelisted
 *
 * If no field whitelisting has been done, all fields
 * are allowed to be selected
 *
 * @param string $fieldName
 * @return boolean
 */
	protected function _whitelistedField($fieldName) {
		$allowedFields = $this->whitelistFields();
		if (empty($allowedFields)) {
			return true;
		}

		return in_array($fieldName, $allowedFields);
	}

/**
 * Check if a field has been blacklisted
 *
 * @param string $fieldName
 * @return boolean
 */
	protected function _blacklistedField($fieldName) {
		$disallowedFields = $this->blacklistFields();
		if (empty($disallowedFields)) {
			return false;
		}

		return in_array($fieldName, $disallowedFields);
	}

}
