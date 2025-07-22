<?php
namespace ZM;
require_once('database.php');
require_once('User.php');
require_once('Object.php');

class User_Preference extends ZM_Object {
  protected static $table = 'User_Preferences';
  protected $defaults = array(
    'Id' => null,
    'UserId' => null,
    'Name' => '',
    'Value' => '',
  );
  private $User = null;

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function User() {
    if (!$this->User) {
      if ($this->{'UserId'}) {
        $this->User = User::find_one(['Id'=> $this->{'UserId'}]);
      }
      if (!$this->User) {
        $this->User = new User();
      }
    }
    return $this->User;
  }
} # end class User_Preference
?>
