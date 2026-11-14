<?php
App::uses('AppModel', 'Model');
/**
 * Log Model
 *
 */
class Log extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Logs';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'Id';

	public $validate = array(
		'TimeKey' => array('rule' => 'numeric', 'required' => 'create'),
		'Component' => array('rule' => 'notBlank', 'required' => 'create'),
		'Level' => array('rule' => 'numeric', 'required' => 'create'),
		'Code' => array('rule' => 'notBlank', 'required' => 'create'),
		'Message' => array('rule' => 'notBlank', 'required' => 'create'),
	);

}
