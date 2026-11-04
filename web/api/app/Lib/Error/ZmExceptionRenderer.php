<?php
App::uses('CrudExceptionRenderer', 'Crud.Error');
App::uses('ZmErrorHandler', 'Error');

/**
 * Crud's API exception renderer, without internals in 5xx bodies.
 *
 * Crud adds data.exception.message from the raw exception even when Cake has
 * replaced the message with a generic one (debug 0), so a PDOException put the
 * SQL error and its fragment of the query in the response. It also echoes the
 * request URL, credentials included, in data.url.
 */
class ZmExceptionRenderer extends CrudExceptionRenderer {

/**
 * {@inheritDoc}
 */
	protected function _getErrorData() {
		$data = parent::_getErrorData();
		if (isset($data['url'])) {
			$data['url'] = ZmErrorHandler::redact($data['url']);
		}
		if (!Configure::read('debug') && isset($data['exception'])
			&& $this->controller->response->statusCode() >= 500) {
			$data['exception']['message'] = __d('cake', 'An Internal Error Has Occurred.');
		}
		return $data;
	}

}
