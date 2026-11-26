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
			// zmpkg.pl treats these names as commands, so such a state could never be applied
			'notReserved' => array(
				'rule' => array('custom', '/^(?!(?:start|stop|restart|status|logrot|version)$)/'),
				'message' => 'A run state cannot be named start, stop, restart, status, logrot or version',
			),
		),
		// The column has no default, so a state added without one is refused by MySQL.
		'Definition' => array(
			'required' => array(
				'rule' => array('maxLength', 65535),
				'required' => 'create',
				'allowEmpty' => true,
				'message' => 'A state needs a Definition',
			),
		),
	);

}
