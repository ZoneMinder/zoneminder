<?php
App::uses('AppModel', 'Model');

class BoostCake extends AppModel {

	public $useTable = false;

	protected $_schema = array(
		'id' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '10'),
		'text' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'email' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'password' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'price' => array('type' => 'integer', 'null' => '', 'default' => '', 'length' => '10'),
		'textarea' => array('type' => 'string', 'null' => '', 'default' => '', 'length' => '255'),
		'checkbox' => array('type' => 'boolean', 'null' => false, 'default' => false),
		'remember' => array('type' => 'boolean', 'null' => false, 'default' => false),
		'select' => array('type' => 'integer', 'length' => '10', 'null' => true),
		'radio' => array('type' => 'integer', 'length' => '10', 'null' => true),
		'datetime' => array('type' => 'datetime')
	);

}
