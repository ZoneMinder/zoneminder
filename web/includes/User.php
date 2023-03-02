<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Group_Permission.php');
require_once('Monitor_Permission.php');


class User extends ZM_Object {
  protected static $table = 'Users';

	protected $defaults = array(
			'Id'              => null,
      'Username'        => array('type'=>'text','filter_regexp'=>'/[^\w\.@ ]/', 'default'=>''),
      'Name'            => '',
      'Email'           => '',
      'Phone'           => '',
      'Password'        => '',
      'Language'        => '',
      'Enabled'         => 1,
      'Stream'          => 'None',
      'Events'          => 'None',
      'Control'         => 'None',
      'Monitors'        => 'None',
      'Groups'          => 'None',
      'Devices'         => 'None',
      'Snapshots'       => 'None',
      'System'          => 'None',
      'MaxBandwidth'    => '',
      'MonitorIds'      => '',
      'TokenMinExpiry'  => 0,
      'APIEnabled'      => 1,
      'HomeView'        => '',
			);

  private $Group_Permissions; # array of GP objects indexed by id
  private $Monitor_Permissions;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function Name( ) {
    if (property_exists($this, 'Name') and !empty($this->Name)) {
      return $this->Name;
    }
    return $this->{'Username'};
  }

  public static function Indexed_By_Id() {
    $results = array();
    foreach ( ZM_Object::_find('ZM\User', null, array('order'=>'lower(Username)')) as $Object ) {
      $results[$Object->Id()] = $Object;
    }
    return $results;
  }

  public function Group_Permissions() {
    if (!$this->Group_Permissions) {
      $this->Group_Permissions = array_to_hash_by_key('GroupId', Group_Permission::find(['UserId'=>$this->Id()]));
    }
    return array_values($this->Group_Permissions);
  }

  public function Group_Permission($group_id) {
    if (!$this->Group_Permissions) $this->Group_Permissions();

    if (!isset($this->Group_Permissions[$group_id])) {
      $gp = $this->Group_Permissions[$group_id] = new Group_Permission();
      $gp->UserId($this->Id());
      $gp->GroupId($this->Id());
    }

    return $this->Group_Permissions[$group_id];
  }

  public function Monitor_Permissions($new=-1) {
    if ($new != -1) $this->Monitor_Permissions = $new;
    if (!$this->Monitor_Permissions) {
      $this->Monitor_Permissions = array_to_hash_by_key('MonitorId', Monitor_Permission::find(['UserId'=>$this->Id()]));
    }
    return array_values($this->Monitor_Permissions);
  }

  public function Monitor_Permission($monitor_id) {
    if (!$this->Monitor_Permissions) $this->Monitor_Permissions();

    if (!isset($this->Monitor_Permissions[$monitor_id])) {
      $mp = $this->Monitor_Permissions[$monitor_id] = new Monitor_Permission();
      $mp->UserId($this->Id());
      $mp->MonitorId($monitor_id);
    }
    return $this->Monitor_Permissions[$monitor_id];
  }
} # end class User
?>
