<?php

App::uses('CrudAction', 'Crud.Controller/Crud');

/**
 * Handles 'Edit' Crud actions
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class EditCrudAction extends CrudAction {

/**
 * Default settings for 'edit' actions
 *
 * `enabled` Is this crud action enabled or disabled
 *
 * `findMethod` The default `Model::find()` method for reading data
 *
 * `view` A map of the controller action and the view to render
 * If `NULL` (the default) the controller action name will be used
 *
 * `relatedModels` is a map of the controller action and the whether it should fetch associations lists
 * to be used in select boxes. An array as value means it is enabled and represent the list
 * of model associations to be fetched
 *
 * `validateId` ID Argument validation - by default it will inspect your model's primary key
 * and based on its data type either use integer or UUID validation.
 * Can be disabled by setting it to "false". Supports "integer" and "uuid" configuration
 * By default its configuration is NULL, which means "auto detect"
 *
 * `saveOptions` Raw array passed as 2nd argument to saveAll() in `add` and `edit` method
 * If you configure a key with your action name, it will override the default settings.
 * This is useful for adding fieldList to enhance security in saveAll.
 *
 * @var array
 */
	protected $_settings = array(
		'enabled' => true,
		'findMethod' => 'first',
		'saveMethod' => 'saveAssociated',
		'view' => null,
		'relatedModels' => true,
		'validateId' => null,
		'saveOptions' => array(
			'validate' => 'first',
			'atomic' => true
		),
		'messages' => array(
			'success' => array(
				'text' => 'Successfully updated {name}'
			),
			'error' => array(
				'text' => 'Could not update {name}'
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
		'api' => array(
			'methods' => array('put', 'post'),
			'success' => array(
				'code' => 200
			),
			'error' => array(
				'exception' => array(
					'type' => 'validate',
					'class' => 'CrudValidationException'
				)
			)
		),
		'serialize' => array()
	);

/**
 * Constant representing the scope of this action
 *
 * @var integer
 */
	const ACTION_SCOPE = CrudAction::SCOPE_RECORD;

/**
 * HTTP GET handler
 *
 * @throws NotFoundException If record not found
 * @param string $id
 * @return void
 */
	protected function _get($id = null) {
		if (!$this->_validateId($id)) {
			return false;
		}

		$request = $this->_request();
		$model = $this->_model();

		$request->data = $this->_findRecord($id);
		if (empty($request->data)) {
			return $this->_notFound($id);
		}

		$item = $request->data;
		$subject = $this->_trigger('afterFind', compact('id', 'item'));
		$request->data = Hash::merge($request->data, $model->data, $subject->item);

		$this->_trigger('beforeRender');
	}

/**
 * HTTP PUT handler
 *
 * @param mixed $id
 * @return void
 */
	protected function _put($id = null) {
		if (!$this->_validateId($id)) {
			return false;
		}

		$request = $this->_request();
		$model = $this->_model();

		$existing = $this->_findRecord($id, 'count');
		if (empty($existing)) {
			return $this->_notFound($id);
		}

		$request->data = $this->_injectPrimaryKey($request->data, $id, $model);

		$this->_trigger('beforeSave', compact('id'));
		if (call_user_func(array($model, $this->saveMethod()), $request->data, $this->saveOptions())) {
			$this->setFlash('success');
			$subject = $this->_trigger('afterSave', array('id' => $id, 'success' => true, 'created' => false));
			return $this->_redirect($subject, array('action' => 'index'));
		}

		$this->setFlash('error');
		$subject = $this->_trigger('afterSave', array('id' => $id, 'success' => false, 'created' => false));
		$this->_trigger('beforeRender', $subject);
	}

/**
 * Find a record from the ID
 *
 * @param string $id
 * @param string $findMethod
 * @return array
 */
	protected function _findRecord($id, $findMethod = null) {
		$model = $this->_model();

		$query = array();
		$query['conditions'] = array($model->escapeField() => $id);

		if (!$findMethod) {
			$findMethod = $this->_getFindMethod($findMethod);
		}

		$subject = $this->_trigger('beforeFind', compact('query', 'findMethod'));
		return $model->find($subject->findMethod, $subject->query);
	}

/**
 * Throw exception if a record is not found
 *
 * @throws Exception
 * @param string $id
 * @return void
 */
	protected function _notFound($id) {
		$this->_trigger('recordNotFound', compact('id'));

		$message = $this->message('recordNotFound', compact('id'));
		$exceptionClass = $message['class'];
		throw new $exceptionClass($message['text'], $message['code']);
	}

/**
 * HTTP POST handler
 *
 * Thin proxy for _put
 *
 * @param mixed $id
 * @return void
 */
	protected function _post($id = null) {
		return $this->_put($id);
	}

/**
 * Inject the id (from the URL) into the data to be saved.
 *
 * Determine what the format of the data is there are two formats accepted by cake:
 *
 *     array(
 *         'Model' => array('stuff' => 'here')
 *     );
 *
 * and
 *
 *     array('stuff' => 'here')
 *
 * The latter is most appropriate for API calls.
 *
 * If either the first array key is Capitalized, or the model alias is present in the form data,
 * The id will be injected under the model-alias key:
 *
 *     array(
 *         'Model' => array('stuff' => 'here', 'id' => $id)
 *     );
 *
 *     // HABTM example
 *     array(
 *         'Category' => array('Category' => array(123)),
 *         'Model' => array('id' => $id) // <- added
 *     );
 *
 * If the model-alias key is absent AND the first array key is not capitalized, inject in the root:
 *
 *     array('stuff' => 'here', 'id' => $id)
 *
 *
 * @param array $data
 * @param mixed $id
 * @param Model $model
 * @return array
 */
	protected function _injectPrimaryKey($data, $id, $model) {
		$key = key($data);
		$keyIsModelAlias = (strtoupper($key[0]) === $key[0]);

		if (isset($data[$model->alias]) || $keyIsModelAlias) {
			$data[$model->alias][$model->primaryKey] = $id;
		} else {
			$data[$model->primaryKey] = $id;
		}

		return $data;
	}

/**
 * Is the passed ID valid?
 *
 * Validate the id in the URL (the parent function) and then validate the id in the data.
 *
 * The data-id check is independent of the config setting `validateId`; this checks whether
 * the id in the URL matches the id in the submitted data (a type insensitive check). If
 * the id is different, this probably indicates a malicious form submission, attempting
 * to add/edit a record the user doesn't have permission for by submitting to a URL they
 * do have permission to access
 *
 * @param mixed $id
 * @return boolean
 * @throws BadRequestException If id is invalid
 */
	protected function _validateId($id) {
		parent::_validateId($id);

		$request = $this->_request();
		if (!$request->data) {
			return true;
		}

		$dataId = null;
		$model = $this->_model();

		$dataId = $request->data($model->alias . '.' . $model->primaryKey) ?: $request->data($model->primaryKey);
		if ($dataId === null) {
			return true;
		}

		// deliberately type insensitive
		if ($dataId == $id) {
			return true;
		}

		$this->_trigger('invalidId', array('id' => $dataId));

		$message = $this->message('invalidId');
		$exceptionClass = $message['class'];
		throw new $exceptionClass($message['text'], $message['code']);
	}

}
