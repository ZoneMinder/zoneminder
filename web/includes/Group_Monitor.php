<?php
namespace ZM;

class Group_Monitor extends ZM_Object {
  protected static $table = 'Groups_Monitors';
  protected $defaults = array(
      'Id'        =>  null,
      'GroupId'   =>  null,
      'MonitorId' =>  null,
      );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }
} # end class Group_Monitor
?>
