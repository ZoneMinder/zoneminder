<?php
/**
 * Exception containing validation errors from the model. Useful for API
 * responses where you need an error code in response
 *
 */
class CrudValidationException extends CakeException {

/**
 * List of validation errors that occurred in the model
 *
 * @var array
 */
	protected $_validationErrors = array();

/**
 * How many validation errors are there?
 *
 * @var integer
 */
	protected $_validationErrorCount = 0;

/**
 * Constructor
 *
 * @param array $error list of validation errors
 * @param integer $code code to report to client
 * @return void
 */
	public function __construct($errors, $code = 412) {
		$this->_validationErrors = array_filter($errors);
		$flat = Hash::flatten($this->_validationErrors);

		$errorCount = $this->_validationErrorCount = count($flat);
		$this->message = __dn('crud', 'A validation error occurred', '%d validation errors occurred', $errorCount, array($errorCount));

		if ($errorCount === 1) {
			$code = $this->_deriveRuleSpecific($this->_validationErrors, $code);
		}

		parent::__construct($this->message, $code);
	}

/**
 * _deriveRuleSpecific
 *
 * If there is only one error, change the exception message to be rule specific
 * Also change the response code to be that of the validation rule if defined
 *
 * @param array $errors
 * @param integer $code
 * @return integer
 */
	protected function _deriveRuleSpecific($errors = array(), $code = 412) {
		$model = key($errors);
		$field = key($errors[$model]);
		$error = $errors[$model][$field][0];

		$instance = ClassRegistry::getObject($model);
		if (!isset($instance->validate[$field])) {
			return $code;
		}

		foreach ($instance->validate[$field] as $key => $rule) {
			$matchesMessage = (isset($rule['message']) && $error === $rule['message']);
			if ($key !== $error && !$matchesMessage) {
				continue;
			}

			$this->message = sprintf('%s.%s : %s', $model, $field, $error);
			if (!empty($rule['code'])) {
				$code = $rule['code'];
			}
			break;
		}

		return $code;
	}

/**
 * Returns the list of validation errors
 *
 * @return array
 */
	public function getValidationErrors() {
		return $this->_validationErrors;
	}

/**
 * How many validation errors are there?
 *
 * @return integer
 */
	public function getValidationErrorCount() {
		return $this->_validationErrorCount;
	}

}
