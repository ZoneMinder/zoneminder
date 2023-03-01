<?php
namespace ZM;
require_once('Object.php');

class MagicLink extends ZM_Object {
  protected static $table = 'Magic_Links';

	protected $defaults = array(
			'Id'              => null,
      'UserId'          => null,
      'Token'           => null,
      'CreatedOn'       => null
			);

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function GenerateToken() {
    $this->Token = md5(ZM_AUTH_HASH_SECRET.$user['Username'].time());
  }
} # end class MagicLink
?>
