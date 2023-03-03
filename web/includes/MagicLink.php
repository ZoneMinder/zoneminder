<?php
namespace ZM;
require_once('Object.php');

class MagicLink extends ZM_Object {
  protected static $table = 'Magic_Links';

	protected $defaults = array(
			'Id'              => null,
      'UserId'          => null,
      'Token'           => null,
      'CreatedOn'       => 'NOW()'
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function User() {
    if ((!property_exists($this, 'User') or !$this->User) and $this->UserId) {
      $this->User = User::find_one(['Id'=>$this->UserId]);
    }
    return $this->User;
  }

  public function GenerateToken() {
    $user = $this->User();
    if (!($user and $user->Username())) {
      Error("Not logged in. Cannot generate magic link token");
      Error(print_r($user, true));
      return;
    }
    $this->Token = hash('sha256', ZM_AUTH_HASH_SECRET.$user->Username().time());
    return $this->Token;
  }

  public function url() {
    return ZM_URL.'/index.php?view=changepassword&amp;user_id='.$this->User()->Id().'&amp;magic='.$this->Token();
  }
} # end class MagicLink
?>
