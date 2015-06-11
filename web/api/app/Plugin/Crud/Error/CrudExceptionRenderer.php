<?php

App::uses('ExceptionRenderer', 'Error');

/**
 * Exception renderer for ApiListener
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class CrudExceptionRenderer extends ExceptionRenderer {

/**
 * Renders validation errors and sends a 412 error code
 *
 * @param ValidationErrorException $error
 * @return void
 */
	public function crudValidation($error) {
		$url = $this->controller->request->here();
		$status = $code = $error->getCode();
		try {
			$this->controller->response->statusCode($status);
		} catch(Exception $e) {
			$status = 412;
			$this->controller->response->statusCode($status);
		}

		$sets = array(
			'code' => $code,
			'url' => h($url),
			'name' => $error->getMessage(),
			'error' => $error,
			'errorCount' => $error->getValidationErrorCount(),
			'errors' => $error->getValidationErrors(),
			'_serialize' => array('code', 'url', 'name', 'errorCount', 'errors')
		);
		$this->controller->set($sets);
		$this->_outputMessage('error400');
	}

/**
 * Generate the response using the controller object.
 *
 * If there is no specific template for the raised error (normally there won't be one)
 * swallow the missing view exception and just use the standard
 * error format. This prevents throwing an unknown Exception and seeing instead
 * a MissingView exception
 *
 * @param string $template The template to render.
 * @return void
 */
	protected function _outputMessage($template) {
		try {
			$this->controller->set('success', false);
			$this->controller->set('data', $this->_getErrorData());
			$this->controller->set('_serialize', array('success', 'data'));
			$this->controller->render($template);
			$this->controller->afterFilter();
			$this->controller->response->send();
		} catch (MissingViewException $e) {
			$this->_outputMessageSafe('error500');
		} catch (Exception $e) {
			$this->controller->set(array(
				'error' => $e,
				'name' => $e->getMessage(),
				'code' => $e->getCode()
			));
			$this->_outputMessageSafe('error500');
		}
	}

/**
 * A safer way to render error messages, replaces all helpers, with basics
 * and doesn't call component methods.
 *
 * @param string $template The template to render
 * @return void
 */
	protected function _outputMessageSafe($template) {
		$this->controller->layoutPath = '';
		$this->controller->subDir = '';
		$this->controller->viewPath = 'Errors/';
		$this->controller->viewClass = 'View';
		$this->controller->helpers = array('Form', 'Html', 'Session');

		$this->controller->render($template);
		$this->controller->response->send();
	}

/**
 * Helper method used to generate extra debugging data into the error template
 *
 * @return array debugging data
 */
	protected function _getErrorData() {
		$data = array();

		$viewVars = $this->controller->viewVars;
		if (!empty($viewVars['_serialize'])) {
			foreach ($viewVars['_serialize'] as $v) {
				$data[$v] = $viewVars[$v];
			}
		}

		if (!empty($viewVars['error'])) {
			$data['exception'] = array(
				'class' => get_class($viewVars['error']),
				'code' => $viewVars['error']->getCode(),
				'message' => $viewVars['error']->getMessage()
			);
		}

		if (Configure::read('debug')) {
			$data['exception']['trace'] = preg_split('@\n@', $viewVars['error']->getTraceAsString());
		}

		if (class_exists('ConnectionManager') && Configure::read('debug') > 1) {
			$sources = ConnectionManager::sourceList();
			$data['queryLog'] = array();
			foreach ($sources as $source) {
				$db = ConnectionManager::getDataSource($source);
				if (!method_exists($db, 'getLog')) {
					continue;
				}
				$data['queryLog'][$source] = $db->getLog(false, false);
			}
		}

		return $data;
	}
}
