<?php

App::uses('CrudAction', 'Crud.Controller/Crud');

/**
 * Handles 'Index' Crud actions
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class IndexCrudAction extends CrudAction {

/**
 * Default settings for 'index' actions
 *
 * `enabled` Is this crud action enabled or disabled
 *
 * `findMethod` The default `Model::find()` method for reading data
 *
 * `view` A map of the controller action and the view to render
 * If `NULL` (the default) the controller action name will be used
 *
 * @var array
 */
	protected $_settings = array(
		'enabled' => true,
		'findMethod' => 'all',
		'view' => null,
		'viewVar' => null,
		'serialize' => array(),
		'api' => array(
			'success' => array(
				'code' => 200
			),
			'error' => array(
				'code' => 400
			)
		)
	);

/**
 * Constant representing the scope of this action
 *
 * @var integer
 */
	const ACTION_SCOPE = CrudAction::SCOPE_MODEL;

/**
 * Change the name of the view variable name
 * of the data when its sent to the view
 *
 * @param mixed $name
 * @return mixed
 */
	public function viewVar($name = null) {
		if (empty($name)) {
			return $this->config('viewVar') ?: Inflector::variable($this->_controller()->name);
		}

		return $this->config('viewVar', $name);
	}

/**
 * Compute pagination settings
 *
 * Initializes PaginatorComponent if it isn't loaded already
 * Modified the findType based on the CrudAction configuration
 *
 * @return array The Paginator settings
 */
	public function paginationConfig() {
		$controller = $this->_controller();

		if (!isset($controller->Paginator)) {
			$pagination = isset($controller->paginate) ? $controller->paginate : array();
			$controller->Paginator = $controller->Components->load('Paginator', $pagination);
		}

		$Paginator = $controller->Paginator;
		$settings = &$Paginator->settings;

		if (isset($settings[$controller->modelClass])) {
			if (empty($settings[$controller->modelClass]['findType'])) {
				$settings[$controller->modelClass]['findType'] = $this->_getFindMethod('all');
			}
		} elseif (empty($settings['findType'])) {
			$settings['findType'] = $this->_getFindMethod('all');
		}

		return $settings;
	}

/**
 * HTTP GET handler
 *
 * @return void
 */
	protected function _get() {
		$this->paginationConfig();

		$controller = $this->_controller();

		$success = true;
		$viewVar = $this->viewVar();

		$subject = $this->_trigger('beforePaginate', array('paginator' => $controller->Paginator, 'success' => $success, 'viewVar' => $viewVar));
		$items = $controller->paginate($this->_model());
		$subject = $this->_trigger('afterPaginate', array('success' => $subject->success, 'viewVar' => $subject->viewVar, 'items' => $items));

		$items = $subject->items;

		if ($items instanceof Iterator) {
			$items = iterator_to_array($items);
		}

		$controller->set(array('success' => $subject->success, $subject->viewVar => $items));
		$this->_trigger('beforeRender', $subject);
	}

}
