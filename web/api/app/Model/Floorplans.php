<?php
App::uses('AppModel', 'Model');
/**
 * Tag Model
 *
 */
class Floorplans extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Floorplans';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'id';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'name';

	public $recursive = -1;

  var $actsAs = array( 'Containable' );
}
