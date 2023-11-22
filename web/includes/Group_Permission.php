<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('Monitor.php');
require_once('User.php');
require_once('Group.php');

class Group_Permission extends ZM_Object {
  protected static $table = 'Groups_Permissions';
  protected $defaults = array(
      'Id'          =>  null,
      'GroupId'     =>  null,
      'UserId'      =>  null,
      'Permission'  =>  'Inherit',
      );
  private $Group;
  private $User;
  private $Monitors;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function MonitorPermission($mid) {
    if (!$this->Monitors)
      $this->Monitors = array_to_hash_by_key('Id', $this->Group()->Monitors());
    if (isset($this->Monitors[$mid])) return $this->Permission;
    return 'Inherit';
  }

  public function Group($new=null) {
    if ($new) $this->Group = $new;
    if (!$this->Group)
      $this->Group = Group::find_one(['Id'=>$this->GroupId]);
    return $this->Group;
  }
  public function User($new=null) {
    if ($new) $this->User = $new;
    if (!$this->User)
      $this->User = User::find_one(['Id'=>$this->UserId]);
    return $this->User;
  }

} # end class Group_Permission
?>
