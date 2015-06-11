<?php

App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * Search Listener
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class SearchListener extends CrudListener {

/**
 * Default configuration
 *
 * @var array
 */
	protected $_settings = array(
		'component' => array(
			'commonProcess' => array(
				'paramType' => 'querystring'
			),
			'presetForm' => array(
				'paramType' => 'querystring'
			)
		),
		'scope' => array()
	);

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
			'Crud.beforeHandle' => array('callable' => 'beforeHandle', 'priority' => 50),
			'Crud.beforePaginate' => array('callable' => 'beforePaginate', 'priority' => 50)
		);
	}

	public function beforeHandle(CakeEvent $event) {
		$request = $this->_request();
		$model = $this->_model();

		if (!array_key_exists($model->alias, $request->data)) {
			return;
		}

		if (!array_key_exists('_search', $request->data($model->alias))) {
			return;
		}

		$controller = $this->_controller();

		$this->_ensureComponent($controller);
		$this->_ensureBehavior($model);
		$this->_commonProcess($controller, $model->name);
	}

/**
 * Define a new scope
 *
 * @param string $name Name of the scope (?scope=$name)
 * @param array $query The query arguments to pass to Search
 * @param array|null $filter The filterArgs to use on the model
 * @return ScopedListener
 */
	public function scope($name, $query, $filter = null) {
		$this->config('scope.' . $name, compact('query', 'filter'));
		return $this;
	}

/**
 * beforePaginate callback
 *
 * @param CakeEvent $e
 * @return void
 */
	public function beforePaginate(CakeEvent $e) {
		$this->_checkRequiredPlugin();

		$model = $this->_model();
		$controller = $this->_controller();
		$request = $this->_request();

		$this->_ensureComponent($controller);
		$this->_ensureBehavior($model);
		$this->_commonProcess($controller, $model->name);

		$query = $request->query;
		if (!empty($request->query['_scope'])) {
			$config = $this->config('scope.' . $request->query['_scope']);
			if (empty($config)) {
				$config = $this->_action()->config('scope.' . $request->query['_scope']);
			}

			$query = Hash::get((array)$config, 'query');

			if (!empty($config['filter'])) {
				$this->_setFilterArgs($model, $config['filter']);
			}
		} else {
			$filterArgs = $this->_action()->config('scope');
			if (!empty($filterArgs)) {
				$this->_setFilterArgs($model, (array)$filterArgs);
			}
		}

		// Avoid notice if there is no filterArgs
		if (empty($model->filterArgs)) {
			$this->_setFilterArgs($model, array());
		}

		$this->_setPaginationOptions($controller, $model, $query);
	}

/**
 * Check that the cakedc/search plugin is installed
 *
 * @throws CakeException If cakedc/search isn't loaded
 * @return void
 */
	protected function _checkRequiredPlugin() {
		if (CakePlugin::loaded('Search')) {
			return;
		}

		throw new CakeException('SearchListener requires the CakeDC/search plugin. Please install it from https://github.com/CakeDC/search');
	}

/**
 * Ensure that the Prg component is loaded from
 * the Search plugin
 *
 * @param Controller $controller
 * @return void
 */
	protected function _ensureComponent(Controller $controller) {
		if ($controller->Components->loaded('Prg')) {
			return;
		}

		$controller->Prg = $controller->Components->load('Search.Prg', $this->config('component'));
		$controller->Prg->initialize($controller);
		$controller->Prg->startup($controller);
	}

/**
 * Ensure that the searchable behavior is loaded
 *
 * @param Model $model
 * @return void
 */
	protected function _ensureBehavior(Model $model) {
		if ($model->Behaviors->loaded('Searchable')) {
			return;
		}

		$model->Behaviors->load('Search.Searchable');
		$model->Behaviors->Searchable->setup($model);
	}

/**
 * Execute commonProcess on Prg component
 *
 * @codeCoverageIgnore
 * @param Controller $controller
 * @param string $modelClass
 * @return void
 */
	protected function _commonProcess(Controller $controller, $modelClass) {
		$controller->Prg->commonProcess($modelClass);
	}

/**
 * Set the pagination options
 *
 * @codeCoverageIgnore
 * @param Controller $controller
 * @param Model $model
 * @param array $query
 * @return void
 */
	protected function _setPaginationOptions(Controller $controller, Model $model, $query) {
		if (!isset($controller->Paginator->settings['conditions'])) {
			$controller->Paginator->settings['conditions'] = array();
		}
		$controller->Paginator->settings['conditions'] = array_merge(
			$controller->Paginator->settings['conditions'],
			$model->parseCriteria($query)
		);
	}

/**
 * Set the model filter args
 *
 * @codeCoverageIgnore
 * @param Model $model
 * @param array $filter
 * @return void
 */
	protected function _setFilterArgs(Model $model, $filter) {
		$model->filterArgs = $filter;
		$model->Behaviors->Searchable->setup($model);
	}

}
