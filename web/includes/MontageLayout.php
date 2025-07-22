<?php
namespace ZM;
require_once('Object.php');

class MontageLayout extends ZM_Object {
  protected static $table = 'MontageLayouts';
  protected $defaults = array(
    'Id' => null,
    'Name' => '',
    'UserId'  =>  0,
    'Positions' => 0,
  );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

} // end class MontageLayout
?>
