<?php
App::uses('ErrorHandler', 'Error');

/**
 * Cake's ErrorHandler, without credentials in the log.
 *
 * Cake logs every uncaught exception (401, 404, 500, ...) to cake_error.log
 * with "Request URL: " and the query string. API clients may authenticate with
 * ?token=, ?auth=, ?user=&pass= in the query string, so each failed request
 * wrote a working credential to a world-readable log.
 */
class ZmErrorHandler extends ErrorHandler {

/**
 * Query parameters that carry a credential.
 *
 * @var array
 */
	public static $secretParams = array('token', 'auth', 'pass', 'password');

/**
 * Replace the values of credential parameters in a URL or in any text that
 * contains one.
 *
 * @param string $text A URL, query string or log message
 * @return string The text with each credential value replaced by [redacted]
 */
	public static function redact($text) {
		$names = implode('|', array_map('preg_quote', static::$secretParams));
		// The URL Cake puts in error bodies is HTML-escaped, so & may be &amp;
		return preg_replace('/((?:[?&]|&amp;)(?:'.$names.')=)[^&#\s]*/i', '$1[redacted]', $text);
	}

/**
 * {@inheritDoc}
 */
	protected static function _getMessage($exception) {
		return static::redact(parent::_getMessage($exception));
	}

}
