<?php
App::uses('AppModel', 'Model');
/**
 * ZonePreset Model
 *
 */
class ZonePreset extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'ZonePresets';

/**
 * Primary key field
 *
 * @var string
 */
	public $primaryKey = 'Id';

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'Name';

}
