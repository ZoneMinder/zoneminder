<?php
namespace ZM;
use Exception;
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

  // Describes what still points at this server ('3 monitors, 1 storage area'), or '' when nothing does.
  public static function InUseSummary($id) {
    $row = dbFetchOne('SELECT (SELECT COUNT(*) FROM Monitors WHERE Deleted=0 AND ServerId=?) AS Monitors, (SELECT COUNT(*) FROM Storage WHERE ServerId=?) AS Storage, (SELECT COUNT(*) FROM CronJobs WHERE ServerId=?) AS CronJobs', false, array($id, $id, $id));
    if (!$row) return '';
    $parts = array();
    if ($row['Monitors']) $parts[] = $row['Monitors'].' monitor'.($row['Monitors'] == 1 ? '' : 's');
    if ($row['Storage']) $parts[] = $row['Storage'].' storage area'.($row['Storage'] == 1 ? '' : 's');
    if ($row['CronJobs']) $parts[] = $row['CronJobs'].' cron job'.($row['CronJobs'] == 1 ? '' : 's');
    return implode(', ', $parts);
  }

  public function ReadStats() {
    # Single-server installs store stats under ServerId=0 (zmstats.pl writes
    # ZM_SERVER_ID ? ZM_SERVER_ID : 0), and $this->Id() is empty/undef there.
    # Coerce a falsy id to 0 so the lookup matches; a real id passes through.
    # Rows older than a few update intervals are stale (zmstats stopped), so the defaults (-1) stay.
    $dbStats = dbFetchAll('SELECT * FROM Server_Stats WHERE ServerId=? AND TimeStamp > NOW() - INTERVAL ? SECOND ORDER BY TimeStamp DESC LIMIT 1',NULL, [$this->Id() ? $this->Id() : 0, max(60, 4 * (int)ZM_STATS_UPDATE_INTERVAL)]);
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

  # Chained proxies append their own value to these headers, so the value is a
  # comma-separated list. The first element is the client-facing one.
  private static function firstHeaderValue($header) {
    if (!isset($_SERVER[$header])) return null;
    $values = explode(',', $_SERVER[$header]);
    return trim($values[0]);
  }

  public function Hostname($new = null) {
    if ($new != null)
      $this->{'Hostname'} = $new;

    if (isset( $this->{'Hostname'}) and ($this->{'Hostname'} != '')) {
      return $this->{'Hostname'};
    } else if ( $this->Id() ) {
      return $this->{'Name'};
    }

    # The host header may carry a port, which Url() adds separately.
    foreach (array('HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_SERVER', 'HTTP_HOST') as $header) {
      $value = self::firstHeaderValue($header);
      if ($value === null) continue;
      # This theoretically will match ipv6 addresses as well
      if ( preg_match( '/^(\[[[:xdigit:]:]+\]|[^:]+)(:[[:digit:]]+)?$/', $value, $matches ) ) {
        return $matches[1];
      }

      $result = explode(':', $value);
      return $result[0];
    }
    // Fall back to ZM_BASE_URL for single-server setups
    if ( defined('ZM_BASE_URL') && ZM_BASE_URL ) {
      $parsed = parse_url(ZM_BASE_URL);
      if ( isset($parsed['host']) ) {
        return $parsed['host'];
      }
    }
    return '';
  }

  public function Protocol( $new = null ) {
    if ( $new != null )
      $this->{'Protocol'} = $new;

    if ( isset($this->{'Protocol'}) and ( $this->{'Protocol'} != '' ) ) {
      return $this->{'Protocol'};
    }

    // Check $_SERVER variables first
    $forwarded_proto = self::firstHeaderValue('HTTP_X_FORWARDED_PROTO');
    if ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' )
      or ( $forwarded_proto !== null and ( $forwarded_proto == 'https' ) )
      or ( isset($_SERVER['HTTP_FRONT_END_HTTPS']) and ($_SERVER['HTTP_FRONT_END_HTTPS'] == 'On' ) )
    ) {
      return 'https';
    }

    // Fall back to ZM_BASE_URL for single-server setups
    if ( defined('ZM_BASE_URL') && ZM_BASE_URL ) {
      $parsed = parse_url(ZM_BASE_URL);
      if ( isset($parsed['scheme']) ) {
        return $parsed['scheme'];
      }
    }

    return 'http';
  }

  public function Port( $new = '' ) {
    if ( $new != '' )
      $this->{'Port'} = $new;

    if ( isset($this->{'Port'}) and $this->{'Port'} ) {
      return $this->{'Port'};
    }

    # The request headers and ZM_BASE_URL describe the server handling this
    # request, not another one in a multi-server setup.
    if ( $this->Id() and !(defined('ZM_SERVER_ID') and ($this->Id() == ZM_SERVER_ID)) ) return null;

    $forwarded_port = self::firstHeaderValue('HTTP_X_FORWARDED_PORT');
    if ( $forwarded_port !== null ) {
      return $forwarded_port;
    }

    // Fall back to ZM_BASE_URL for single-server setups
    if ( defined('ZM_BASE_URL') && ZM_BASE_URL ) {
      $parsed = parse_url(ZM_BASE_URL);
      if ( isset($parsed['port']) ) {
        return $parsed['port'];
      }
      // Return default ports based on protocol
      if ( isset($parsed['scheme']) ) {
        return $parsed['scheme'] == 'https' ? 443 : 80;
      }
    }

    return null;
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
    $hostname = $this->Hostname();
    # An IPv6 literal must be bracketed to be followed by a port.
    if ( strpos($hostname, ':') !== false and $hostname[0] != '[' ) {
      $hostname = '['.$hostname.']';
    }
    $url .= $hostname;
    if ( !$port ) {
      $port = $this->Port();
    }
    if ( $this->Protocol() == 'https' and $port == 443 ) {
    } else if ( $this->Protocol() == 'http' and $port == 80 ) {
    } else if ( $port ) {
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
    Debug('sending command to '.urlForLog($url));

    $context_options=array(
      "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
      ),
      "http"=>array(
        "timeout"=>10,
      ),
    );
    $context = stream_context_create($context_options);
    try {
      $result = @file_get_contents($url, false, $context);
      if ($result === FALSE) { /* Handle error */
        Error('Error using '.urlForLog($url).': '.lastErrorForLog());
      }
    } catch (Exception $e) {
      Error("Except $e thrown sending to ".urlForLog($url));
    }
    return $result;
  }
} # end class Server
?>
