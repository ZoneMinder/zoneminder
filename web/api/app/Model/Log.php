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
	public $primaryKey = 'TimeKey';

}
