<?php

App::uses('CrudAction', 'Crud.Controller/Crud');

/**
 * Handles 'Add' Crud actions
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class AddCrudAction extends CrudAction {

/**
 * Default settings for 'add' actions
 *
 * `enabled` Is this crud action enabled or disabled
 *
 * `view` A map of the controller action and the view to render
 * If `NULL` (the default) the controller action name will be used
 *
 * `relatedModels` is a map of the controller action and the whether it should fetch associations lists
 * to be used in select boxes. An array as value means it is enabled and represent the list
 * of model associations to be fetched
 *
 * `saveOptions` Raw array passed as 2nd argument to saveAll() in `add` and `edit` method
 * If you configure a key with your action name, it will override the default settings.
 * This is useful for adding fieldList to enhance security in saveAll.
 *
 * @var array
 */
	protected $_settings = array(
		'enabled' => true,
		'saveMethod' => 'saveAssociated',
		'view' => null,
		'relatedModels' => true,
		'saveOptions' => array(
			'validate' => 'first',
			'atomic' => true
		),
		'api' => array(
			'methods' => array('put', 'post'),
			'success' => array(
				'code' => 201,
				'data' => array(
					'subject' => array('id')
				)
			),
			'error' => array(
				'exception' => array(
					'type' => 'validate',
					'class' => 'CrudValidationException'
				)
			)
		),
		'redirect' => array(
			'post_add' => array(
				'reader' => 'request.data',
				'key' => '_add',
				'url' => array('action' => 'add')
			),
			'post_edit' => array(
				'reader' => 'request.data',
				'key' => '_edit',
				'url' => array('action' => 'edit', array('subject.key', 'id'))
			)
		),
		'messages' => array(
			'success' => array(
				'text' => 'Successfully created {name}'
			),
			'error' => array(
				'text' => 'Could not create {name}'
			)
		),
		'serialize' => array()
	);

/**
 * Constant representing the scope of this action
 *
 * @var integer
 */
	const ACTION_SCOPE = CrudAction::SCOPE_MODEL;

/**
 * HTTP GET handler
 *
 * @return void
 */
	protected function _get() {
		$request = $this->_request();
		$model = $this->_model();

		$model->create();
		$request->data = $model->data;
		$this->_trigger('beforeRender', array('success' => false));
	}

/**
 * HTTP POST handler
 *
 * @return void
 */
	protected function _post() {
		$request = $this->_request();
		$model = $this->_model();

		$this->_trigger('beforeSave');
		if (call_user_func(array($model, $this->saveMethod()), $request->data, $this->saveOptions())) {
			$this->setFlash('success');
			$subject = $this->_trigger('afterSave', array('success' => true, 'created' => true, 'id' => $model->id));
			return $this->_redirect($subject, array('action' => 'index'));
		}

		$this->setFlash('error');

		$subject = $this->_trigger('afterSave', array('success' => false, 'created' => false));
		$request->data = Hash::merge($request->data, $model->data);
		$this->_trigger('beforeRender', $subject);
	}

/**
 * HTTP PUT handler
 *
 * @return void
 */
	protected function _put() {
		return $this->_post();
	}

}
