<?php
namespace ZM;

class Group_Permission extends ZM_Object {
  protected static $table = 'Groups_Permissions';
  protected $defaults = array(
      'Id'        =>  null,
      'GroupId'   =>  null,
      'UserId' =>  null,
      );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }
} # end class Group_Permission
?>
