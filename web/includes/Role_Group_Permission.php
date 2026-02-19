<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('Group.php');

class Role_Group_Permission extends ZM_Object {
  protected static $table = 'Role_Groups_Permissions';
  protected $defaults = array(
    'Id'         => null,
    'RoleId'     => null,
    'GroupId'    => null,
    'Permission' => 'Inherit',
  );
  private $Role;
  private $Group;
  private $Monitors;

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function MonitorPermission($mid) {
    if (!$this->Monitors)
      $this->Monitors = array_to_hash_by_key('Id', $this->Group()->Monitors());
    if (isset($this->Monitors[$mid])) return $this->Permission();
    return 'Inherit';
  }

  public function Role($new = null) {
    if ($new) $this->Role = $new;
    if (!$this->Role) {
      require_once('User_Role.php');
      $this->Role = User_Role::find_one(['Id' => $this->RoleId()]);
    }
    return $this->Role;
  }

  public function Group($new = null) {
    if ($new) $this->Group = $new;
    if (!$this->Group)
      $this->Group = Group::find_one(['Id' => $this->GroupId()]);
    return $this->Group;
  }

} # end class Role_Group_Permission
?>
