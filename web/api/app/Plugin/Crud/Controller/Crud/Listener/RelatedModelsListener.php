<?php

App::uses('CakeEventListener', 'Event');
App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * Implements beforeRender event listener to set related models' lists to
 * the view
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class RelatedModelsListener extends CrudListener {

/**
 * Gets the list of associated model lists to be fetched for an action
 *
 * @param string $action name of the action
 * @return array
 */
	public function models($action = null) {
		$settings = $this->_action($action)->relatedModels();
		if ($settings === true) {
			$ModelInstance = $this->_model();
			return array_merge(
				$ModelInstance->getAssociated('belongsTo'),
				$ModelInstance->getAssociated('hasAndBelongsToMany')
			);
		}

		if (empty($settings)) {
			return array();
		}

		if (is_string($settings)) {
			$settings = array($settings);
		}

		return $settings;
	}

/**
 * Find and publish all related models to the view
 * for an action
 *
 * @param NULL|string $action If NULL the current action will be used
 * @return void
 */
	public function publishRelatedModels($action = null) {
		$models = $this->models($action);

		if (empty($models)) {
			return;
		}

		$Controller = $this->_controller();

		foreach ($models as $modelName) {
			$associationType = $this->_getAssociationType($modelName);
			$associatedModel = $this->_getModelInstance($modelName, $associationType);

			$viewVar = Inflector::variable(Inflector::pluralize($associatedModel->alias));
			if (array_key_exists($viewVar, $Controller->viewVars)) {
				continue;
			}

			$query = $this->_getBaseQuery($associatedModel, $associationType);

			$subject = $this->_trigger('beforeRelatedModel', compact('modelName', 'query', 'viewVar', 'associationType', 'associatedModel'));
			$items = $this->_findRelatedItems($associatedModel, $subject->query);
			$subject = $this->_trigger('afterRelatedModel', compact('modelName', 'items', 'viewVar', 'associationType', 'associatedModel'));

			$Controller->set($subject->viewVar, $subject->items);
		}
	}

/**
 * Fetches related models' list and sets them to a variable for the view
 *
 * @codeCoverageIgnore
 * @param CakeEvent $event
 * @return void
 */
	public function beforeRender(CakeEvent $event) {
		$this->publishRelatedModels();
	}

/**
 * Execute the DB query to find the related items
 *
 * @param Model $Model
 * @param array $query
 * @return array
 */
	protected function _findRelatedItems(Model $Model, $query) {
		if ($this->_hasTreeBehavior($Model)) {
			return $Model->generateTreeList(
				$query['conditions'],
				$query['keyPath'],
				$query['valuePath'],
				$query['spacer'],
				$query['recursive']
			);
		}

		return $Model->find('list', $query);
	}

/**
 * Get the base query to find the related items for an associated model
 *
 * @param Model $associatedModel
 * @param string $associationType
 * @return array
 */
	protected function _getBaseQuery(Model $associatedModel, $associationType = null) {
		$query = array();

		if ($associationType === 'belongsTo') {
			$PrimaryModel = $this->_model();
			$query['conditions'][] = $PrimaryModel->belongsTo[$associatedModel->alias]['conditions'];
		}

		if ($this->_hasTreeBehavior($associatedModel)) {
			$TreeBehavior = $this->_getTreeBehavior($associatedModel);
			$query = array(
				'keyPath' => null,
				'valuePath' => null,
				'spacer' => '_',
				'recursive' => $TreeBehavior->settings[$associatedModel->alias]['recursive']
			);

			if (empty($query['conditions'])) {
				$query['conditions'][] = $TreeBehavior->settings[$associatedModel->alias]['scope'];
			}
		}

		return $query;
	}

/**
 * Returns model instance based on its name
 *
 * @param string $modelName
 * @param string $associationType
 * @return Model
 */
	protected function _getModelInstance($modelName, $associationType = null) {
		$PrimaryModel = $this->_model();

		if (isset($PrimaryModel->{$modelName})) {
			return $PrimaryModel->{$modelName};
		}

		$Controller = $this->_controller();
		if (isset($Controller->{$modelName}) && $Controller->{$modelName} instanceOf Model) {
			return $Controller->{$modelName};
		}

		if ($associationType && !empty($PrimaryModel->{$associationType}[$modelName]['className'])) {
			return $this->_classRegistryInit($PrimaryModel->{$associationType}[$modelName]['className']);
		}

		return $this->_classRegistryInit($modelName);
	}

/**
 * Returns model's association type with controller's model
 *
 * @param string $modelName
 * @return string|null Association type if found else null
 */
	protected function _getAssociationType($modelName) {
		$associated = $this->_model()->getAssociated();
		return isset($associated[$modelName]) ? $associated[$modelName] : null;
	}

/**
 * Check if a model has the Tree behavior attached or not
 *
 * @codeCoverageIgnore
 * @param Model $Model
 * @return boolean
 */
	protected function _hasTreeBehavior(Model $Model) {
		return $Model->Behaviors->attached('Tree');
	}

/**
 * Get the TreeBehavior from a model
 *
 * @codeCoverageIgnore
 * @param Model $Model
 * @return TreeBehavior
 */
	protected function _getTreeBehavior(Model $Model) {
		return $Model->Behaviors->Tree;
	}

/**
 * Wrapper for ClassRegistry::init for easier testing
 *
 * @codeCoverageIgnore
 * @return Model
 */
	protected function _classRegistryInit($modelName) {
		return ClassRegistry::init($modelName);
	}

}
