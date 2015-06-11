<?php
App::uses('Model', 'Model');

/**
 * Crud Example Model
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class CrudExample extends Model {

	public $alias = 'CrudExample';

	public $useTable = 'posts';

	public $useDbConfig = 'test';

	public $findMethods = array(
		'published' => true,
		'unpublished' => true,
		'firstPublished' => true,
		'firstUnpublished' => true,
	);

	protected function _findPublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'Y';
			return $query;
		}
		return $results;
	}

	protected function _findUnpublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'N';
			return $query;
		}
		return $results;
	}

	protected function _findFirstPublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'Y';
			return parent::_findFirst($state, $query, $results);
		}
		return parent::_findFirst($state, $query, $results);
	}

	protected function _findFirstUnpublished($state, $query, $results = array()) {
		if ($state === 'before') {
			$query['conditions']['published'] = 'N';
			return parent::_findFirst($state, $query, $results);
		}

		return parent::_findFirst($state, $query, $results);
	}

}
