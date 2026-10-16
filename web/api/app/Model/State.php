<?php
App::uses('AppModel', 'Model');
/**
 * State Model
 *
 */
class State extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'States';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'Name';

/**
 * Validation rules. States are keyed by Id; Name is what zmpkg.pl and
 * states/change/<name> use, so it must be set and unique.
 *
 * @var array
 */
	public $validate = array(
		'Name' => array(
			'notBlank' => array(
				'rule' => array('notBlank'),
				'message' => 'A state needs a name',
			),
			'isUnique' => array(
				'rule' => array('isUnique'),
				'message' => 'A state with this name already exists',
			),
		),
	);

}
