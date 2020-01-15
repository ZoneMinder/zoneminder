<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');


class Server extends ZM_Object {
  protected static $table = 'Servers';

  protected $defaults = array(
    'Id'                   => null,
    'Name'                 => '',
    'Protocol'             => '',
    'Hostname'             => '',
    'Port'                 => null,
    'PathToIndex'          => null,
    'PathToZMS'            => ZM_PATH_ZMS,
    'PathToApi'            => '/zm/api',
    'zmaudit'              => 1,
    'zmstats'              => 1,
    'zmtrigger'            => 0,
    'zmeventnotification'  => 0,
  );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function Hostname( $new = null ) {
    if ( $new != null )
      $this->{'Hostname'} = $new;

    if ( isset( $this->{'Hostname'}) and ( $this->{'Hostname'} != '' ) ) {
      return $this->{'Hostname'};
    } else if ( $this->Id() ) {
      return $this->{'Name'};
    }
    # This theoretically will match ipv6 addresses as well
    if ( preg_match( '/^(\[[[:xdigit:]:]+\]|[^:]+)(:[[:digit:]]+)?$/', $_SERVER['HTTP_HOST'], $matches ) ) {
      return $matches[1];
    }

    $result = explode(':', $_SERVER['HTTP_HOST']);
    return $result[0];
  }

  public function Protocol( $new = null ) {
    if ( $new != null )
      $this->{'Protocol'} = $new;

    if ( isset($this->{'Protocol'}) and ( $this->{'Protocol'} != '' ) ) {
      return $this->{'Protocol'};
    }

    return  ( 
              ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' )
              or
              ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and ( $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) )
            ) ? 'https' : 'http';
  }

  public function Port( $new = '' ) {
    if ( $new != '' )
      $this->{'Port'} = $new;

    if ( isset($this->{'Port'}) and $this->{'Port'} ) {
      return $this->{'Port'};
    }

    if ( isset($_SERVER['HTTP_X_FORWARDED_PORT']) ) {
      return $_SERVER['HTTP_X_FORWARDED_PORT'];
    }

    return $_SERVER['SERVER_PORT'];
  }

  public function PathToZMS( $new = null ) {
    if ( $new != null )
      $this->{'PathToZMS'} = $new;
    if ( $this->Id() and $this->{'PathToZMS'} ) {
      return $this->{'PathToZMS'};
    } else {
      return ZM_PATH_ZMS;
    }
  }

  public function UrlToZMS( $port = null ) {
    return $this->Url($port).$this->PathToZMS();
  }

	public function Url( $port = null ) {
    if ( ! ( $this->Id() or $port ) ) {
      # Don't specify a hostname or port, the browser will figure it out
      return '';
    }

    $url = $this->Protocol().'://';
		$url .= $this->Hostname();
    if ( $port ) {
      $url .= ':'.$port;
    } else {
      $url .= ':'.$this->Port();
    }
    return $url;
	}

  public function PathToIndex( $new = null ) {
    if ( $new != null )
      $this->{'PathToIndex'} = $new;

    if ( isset($this->{'PathToIndex'}) and $this->{'PathToIndex'} ) {
      return $this->{'PathToIndex'};
    }
    // We can't trust PHP_SELF to not include an XSS vector. See note in skin.js.php.
    return preg_replace('/\.php.*$/i', '.php', $_SERVER['PHP_SELF']);
  }

  public function UrlToIndex( $port=null ) {
    return $this->Url($port).$this->PathToIndex();
  }
  public function UrlToApi( $port=null ) {
    return $this->Url($port).$this->PathToApi();
  }
  public function PathToApi( $new = null ) {
    if ( $new != null )
      $this->{'PathToApi'} = $new;

    if ( isset($this->{'PathToApi'}) and $this->{'PathToApi'} ) {
      return $this->{'PathToApi'};
    }
    return '/zm/api';
  }
} # end class Server
?>
