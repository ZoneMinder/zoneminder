<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');


class Manufacturer extends ZM_Object {
  protected static $table = 'Manufacturers';

	protected $defaults = array(
			'Id'                  => null,
			'Name'                => '',
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }
} # end class Manufacturer
?>
