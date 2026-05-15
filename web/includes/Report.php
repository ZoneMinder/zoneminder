<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');

class Report extends ZM_Object {
  protected static $table = 'Reports';

	protected $defaults = array(
			'Id'            => null,
			'Name'          => '',
      'FilterId'      => null,
      'StartDateTime' => null,
      'EndDateTime'   => null,
      'Interval'      => '86400',
      'CreatedBy'     => null,
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function canEdit($u=null) {
    global $user;
    if (!$u) $u = $user;
    if (!$u) return false;

    if ($u->System() == 'Edit') return true;
    $role = $u->Role();
    if ($role && ($role->System() == 'Edit')) return true;

    if ($this->CreatedBy() and $this->CreatedBy() == $u->Id()) return true;
    return false;
  }
} # end class Report
?>
