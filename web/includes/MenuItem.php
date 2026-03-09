<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');

class MenuItem extends ZM_Object {
  protected static $table = 'Menu_Items';

  protected $defaults = array(
    'Id'        => null,
    'MenuKey'   => '',
    'Enabled'   => 1,
    'Label'     => null,
    'SortOrder' => 0,
  );

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function displayLabel() {
    if ($this->{'Label'} !== null && $this->{'Label'} !== '') {
      return $this->{'Label'};
    }
    return translate($this->{'MenuKey'});
  }
}
