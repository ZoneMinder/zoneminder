<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('Group.php');

class User_Role extends ZM_Object {
  protected static $table = 'User_Roles';

  protected $defaults = array(
    'Id'          => null,
    'Name'        => '',
    'Description' => '',
    'Stream'      => 'None',
    'Events'      => 'None',
    'Control'     => 'None',
    'Monitors'    => 'None',
    'Groups'      => 'None',
    'Devices'     => 'None',
    'Snapshots'   => 'None',
    'System'      => 'None',
  );

  private $Group_Permissions;
  private $Monitor_Permissions;

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public static function Indexed_By_Id() {
    $results = array();
    foreach (ZM_Object::_find('ZM\User_Role', null, array('order' => 'lower(Name)')) as $Object) {
      $results[$Object->Id()] = $Object;
    }
    return $results;
  }

  public function Group_Permissions() {
    if (!$this->Group_Permissions) {
      if ($this->Id()) {
        require_once('Role_Group_Permission.php');
        $this->Group_Permissions = array_to_hash_by_key('GroupId', Role_Group_Permission::find(['RoleId' => $this->Id()]));
      } else {
        $this->Group_Permissions = [];
      }
    }
    return array_values($this->Group_Permissions);
  }

  public function Group_Permission($group_id) {
    if (!$this->Group_Permissions) $this->Group_Permissions();

    if (!isset($this->Group_Permissions[$group_id])) {
      require_once('Role_Group_Permission.php');
      $gp = $this->Group_Permissions[$group_id] = new Role_Group_Permission();
      $gp->RoleId($this->Id());
      $gp->GroupId($group_id);
    }

    return $this->Group_Permissions[$group_id];
  }

  public function Monitor_Permissions($new = -1) {
    if ($new != -1) $this->Monitor_Permissions = $new;
    if (!$this->Monitor_Permissions) {
      if ($this->Id()) {
        require_once('Role_Monitor_Permission.php');
        $this->Monitor_Permissions = array_to_hash_by_key('MonitorId', Role_Monitor_Permission::find(['RoleId' => $this->Id()]));
      } else {
        $this->Monitor_Permissions = [];
      }
    }
    return array_values($this->Monitor_Permissions);
  }

  public function Monitor_Permission($monitor_id) {
    if (!$this->Monitor_Permissions) $this->Monitor_Permissions();

    if (!isset($this->Monitor_Permissions[$monitor_id])) {
      require_once('Role_Monitor_Permission.php');
      $mp = $this->Monitor_Permissions[$monitor_id] = new Role_Monitor_Permission();
      $mp->RoleId($this->Id());
      $mp->MonitorId($monitor_id);
    }
    return $this->Monitor_Permissions[$monitor_id];
  }

  public function Users() {
    require_once('User.php');
    return User::find(['RoleId' => $this->Id()]);
  }

} # end class User_Role
?>
