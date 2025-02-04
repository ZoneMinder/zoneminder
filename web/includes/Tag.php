<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');

class Tag extends ZM_Object {
  protected static $table = 'Tags';

	protected $defaults = array(
			'Id'                  => null,
			'Name'                => '',
      'CreateDate'          => null,
      'CreatedBy'           => 0,
      'LastAssignedDate'    => null,

			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }
} # end class Tag
?>
