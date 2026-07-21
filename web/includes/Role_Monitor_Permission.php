<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('Monitor.php');

class Role_Monitor_Permission extends ZM_Object {
  protected static $table = 'Role_Monitors_Permissions';
  protected $defaults = array(
    'Id'         => null,
    'RoleId'     => null,
    'MonitorId'  => null,
    'Permission' => 'Inherit',
  );
  private $Role;
  private $Monitor;

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Role($new = null) {
    if ($new) $this->Role = $new;
    if (!$this->Role) {
      require_once('User_Role.php');
      $this->Role = User_Role::find_one(['Id' => $this->RoleId()]);
    }
    return $this->Role;
  }

  public function Monitor($new = null) {
    if ($new) $this->Monitor = $new;
    if (!$this->Monitor)
      $this->Monitor = Monitor::find_one(['Id' => $this->MonitorId()]);
    return $this->Monitor;
  }

} # end class Role_Monitor_Permission
?>
