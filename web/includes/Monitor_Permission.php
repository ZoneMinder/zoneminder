<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('Monitor.php');
require_once('User.php');
require_once('Monitor.php');

class Monitor_Permission extends ZM_Object {
  protected static $table = 'Monitors_Permissions';
  protected $defaults = array(
      'Id'          =>  null,
      'MonitorId'     =>  null,
      'UserId'      =>  null,
      'Permission'  =>  'Inherit',
      );
  private $Monitor;
  private $User;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Monitor($new=null) {
    if ($new) $this->Monitor = $new;
    if (!$this->Monitor)
      $this->Monitor = Monitor::find_one(['Id'=>$this->MonitorId]);
    return $this->Monitor;
  }
  public function User($new=null) {
    if ($new) $this->User = $new;
    if (!$this->User)
      $this->User = User::find_one(['Id'=>$this->UserId]);
    return $this->User;
  }

} # end class Monitor_Permission
?>
