<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');


class User extends ZM_Object {
  protected static $table = 'Users';

	protected $defaults = array(
			'Id'              => null,
      'Username'        => array('type'=>'text','filter_regexp'=>'/[^\w\.@ ]/', 'default'=>''),
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

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function Name( ) {
    return $this->{'Username'};
  }

  public static function Indexed_By_Id() {
    $results = array();
    foreach ( ZM_Object::_find('ZM\User', null, array('order'=>'lower(Username)')) as $Object ) {
      $results[$Object->Id()] = $Object;
    }
    return $results;
  }

} # end class User
?>
