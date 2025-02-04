<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');

class Server extends ZM_Object {
  protected static $table = 'Servers';
  public $Id;
  public $Name = '';
  public $Protocol = '';
  public $Hostname = '';
  public $Port = null;
  public $PathToIndex = '';
  public $PathToZMS = ZM_PATH_ZMS;
  public $PathToApi = ZM_PATH_API;
  public $State_Id = -1;
  public $Status = 'Unknown';
  public $TimeUpdateStats = null;
  public $CpuLoad = -1;
  public $CpuUserPercent = -1;
  public $CpuNicePercent = -1;
  public $CpuSystemPercent = -1;
  public $CpuIdlePercent = -1;
  public $CpuUsagePercent = -1;
  public $TotalMem = -1;
  public $FreeMem = -1;
  public $TotalSwap = -1;
  public $FreeSwap = -1;
  public $Latitude;
  public $Longitude;

  protected $defaults = array(
    'Id'                   => null,
    'Name'                 => '',
    'Protocol'             => '',
    'Hostname'             => '',
    'Port'                 => null,
    'PathToIndex'          => null,
    'PathToZMS'            => ZM_PATH_ZMS,
    'PathToApi'            => ZM_PATH_API,
    'zmaudit'              => 1,
    'zmstats'              => 1,
    'zmtrigger'            => 0,
    'zmeventnotification'  => 0,
  );

  public function ReadStats() {
    #ToDo: Analyze the date of the last entry, because The entry may be out of date and not updated.
    $dbStats = dbFetchAll('SELECT * FROM Server_Stats WHERE ServerId=? ORDER BY TimeStamp DESC LIMIT 1',NULL, [$this->Id()>1 ? $this->Id() : 0]);
    if (count($dbStats)) {
      $this->TimeUpdateStats = $dbStats[0]['TimeStamp'];
      $this->CpuLoad = $dbStats[0]['CpuLoad'];
      $this->CpuUserPercent = $dbStats[0]['CpuUserPercent'];
      $this->CpuNicePercent = $dbStats[0]['CpuNicePercent'];
      $this->CpuSystemPercent = $dbStats[0]['CpuSystemPercent'];
      $this->CpuIdlePercent = $dbStats[0]['CpuIdlePercent'];
      $this->CpuUsagePercent = $dbStats[0]['CpuUsagePercent'];
      $this->TotalMem = $dbStats[0]['TotalMem'];
      $this->FreeMem = $dbStats[0]['FreeMem'];
      $this->TotalSwap = $dbStats[0]['TotalSwap'];
      $this->FreeSwap = $dbStats[0]['FreeSwap'];
    }
  }

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Hostname($new = null) {
    if ($new != null)
      $this->{'Hostname'} = $new;

    if (isset( $this->{'Hostname'}) and ($this->{'Hostname'} != '')) {
      return $this->{'Hostname'};
    } else if ( $this->Id() ) {
      return $this->{'Name'};
    }

    if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
      return $_SERVER['HTTP_X_FORWARDED_HOST'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_SERVER'])) {
      return $_SERVER['HTTP_X_FORWARDED_SERVER'];
    } else if (isset($_SERVER['HTTP_HOST'])) {
      # This theoretically will match ipv6 addresses as well
      if ( preg_match( '/^(\[[[:xdigit:]:]+\]|[^:]+)(:[[:digit:]]+)?$/', $_SERVER['HTTP_HOST'], $matches ) ) {
        return $matches[1];
      }

      $result = explode(':', $_SERVER['HTTP_HOST']);
      return $result[0];
    }
    return '';
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
              or
              ( isset($_SERVER['HTTP_FRONT_END_HTTPS']) and ($_SERVER['HTTP_FRONT_END_HTTPS'] == 'On' ) )
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
    if ( !$port ) {
      $port = $this->Port();
    }
    if ( $this->Protocol() == 'https' and $port == 443 ) {
    } else if ( $this->Protocol() == 'http' and $port == 80 ) {
    } else {
      $url .= ':'.$port;
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
    return ZM_PATH_API;
  }
  public function SendToApi($path) {
    $url = $this->UrlToApi().$path;
    $auth_relay = get_auth_relay();
    if ($auth_relay) $url .= '?'.$auth_relay;
    Debug('sending command to '.$url);

    $context_options=array(
      "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
      ),
    );
    $context = stream_context_create($context_options);
    try {
      $result = @file_get_contents($url, false, $context);
      if ($result === FALSE) { /* Handle error */
        Error("Error using $url");
      }
    } catch (Exception $e) {
      Error("Except $e thrown sending to $url");
    }
    return $result;
  }
} # end class Server
?>
