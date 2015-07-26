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
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'Name';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'Name';

}
