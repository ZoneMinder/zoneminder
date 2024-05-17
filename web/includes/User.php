<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Group_Permission.php');
require_once('Monitor_Permission.php');
require_once('User_Preference.php');

class User extends ZM_Object {
  protected static $table = 'Users';

  protected $Id;
  protected $Username = '';
  protected $Name = '';
  protected $Email = '';
  protected $Phone = '';
  protected $Password = '';
  protected $Language = '';
  protected $Enabled = 1;
  protected $Stream = 'None';
  protected $Events = 'None';
  protected $Experiments = 'None';
  protected $Control = 'None';
  protected $Monitors = 'None';
  protected $Groups = 'None';
  protected $Devices = 'None';
  protected $Snapshots = 'None';
  protected $System = 'None';
  protected $MaxBandwidth = '';
  protected $TokenMinExpiry = 0;
  protected $APIEnabled = 1;
  protected $HomeView = 'console';


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
      'TokenMinExpiry'  => 0,
      'APIEnabled'      => 1,
      'HomeView'        => '',
			);

  private $Group_Permissions; # array of GP objects indexed by id
  private $Monitor_Permissions;
  private $Preferences;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Name($new=null) {
    if ($new != null) {
      $this->Name = $new;
    }
    if (property_exists($this, 'Name') and !empty($this->Name)) {
      return $this->Name;
    }
    return $this->Username();
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
      if ($this->Id()) {
      $this->Group_Permissions = array_to_hash_by_key('GroupId', Group_Permission::find(['UserId'=>$this->Id()]));
      } else {
        $this->Group_Permissions = [];
      }
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
      if ($this->Id()) {
        $this->Monitor_Permissions = array_to_hash_by_key('MonitorId', Monitor_Permission::find(['UserId'=>$this->Id()]));
      } else {
        $this->Monitor_Permissions = [];
      }
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

  public function Preferences($new=-1) {
    if ($new != -1) $this->Preferences = $new;
    if (!$this->Preferences) {
      $this->Preferences = array_to_hash_by_key('Name', User_Preference::find(['UserId'=>$this->Id()]));
    }
    return array_values($this->Preferences);
  }

  public function Preference($name) {
    if (!$this->Preferences) $this->Preferences();

    if (!isset($this->Preferences[$name])) {
      $up = $this->Preferences[$name] = new User_Preference();
      $up->UserId($this->Id());
      $up->Name($name);
    }
    return $this->Preferences[$name];
  }

  public function viewableMonitorIds() {
    if (!property_exists($this, 'viewableMonitorIds')) {
      $this->viewableMonitorIds = [];
      $this->unviewableMonitorIds = [];
      foreach (Monitor::find() as $monitor) {
        if ($monitor->canView($this)) {
          $this->viewableMonitorIds[] = $monitor->Id();
        } else {
          $this->unviewableMonitorIds[] = $monitor->Id();
        }
      }
    }
    return $this->viewableMonitorIds;
  }
  public function unviewableMonitorIds() {
    if (!property_exists($this, 'unviewableMonitorIds')) {
      $this->viewableMonitorIds = [];
      $this->unviewableMonitorIds = [];
      foreach (Monitor::find() as $monitor) {
        if ($monitor->canView($this)) {
          $this->viewableMonitorIds[] = $monitor->Id();
        } else {
          $this->unviewableMonitorIds[] = $monitor->Id();
        }
      }
    }
    return $this->unviewableMonitorIds;
  }
} # end class User
?>
