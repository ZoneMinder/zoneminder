<?php
class AllTest extends CakeTestSuite {

	public static function suite() {
		$suite = new CakeTestSuite('All tests');
		$path = dirname(__FILE__);
		$suite->addTestDirectory($path . DS . 'View' . DS . 'Helper');
		return $suite;
	}

}