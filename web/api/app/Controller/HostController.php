<?php
App::uses('AppController', 'Controller');

class HostController extends AppController {

  public $components = array('RequestHandler', 'Session');

  public function daemonCheck($daemon=false, $args=false) {
    $string = Configure::read('ZM_PATH_BIN').'/zmdc.pl check';
    if ( $daemon ) {
      $string .= " $daemon";
      if ( $args )
        $string .= " $args";
    }
    $result = exec($string);
    $result = preg_match('/running/', $result);

    $this->set(array(
      'result' => $result,
      '_serialize' => array('result')
    ));
  }

  function getLoad() {
    $load = sys_getloadavg();

    $this->set(array(
      'load' => $load,
      '_serialize' => array('load')
    ));
  }
  
  function login() {
    $cred = $this->_getCredentials();
    $cred_depr = $this->_getCredentialsDeprecated();
    $ver = $this->_getVersion();
    $this->set(array(
      'token'=>$cred[0],
      'token_expires'=>$cred[1] * 3600, // takes AUTH_HASH_TTL || 2 hrs as the default
      'credentials'=>$cred_depr[0],
      'append_password'=>$cred_depr[1],
      'version' => $ver[0],
      'apiversion' => $ver[1],
      '_serialize' => array(
                            'token',
                            'token_expires',
                            'version',
                            'credentials',
                            'append_password',
                            'apiversion'
      )));
  } // end function login()

  // clears out session
  function logout() {
    userLogout();

    $this->set(array(
      'result' => 'ok',
      '_serialize' => array('result')
    ));

  } // end function logout()

  private function _getCredentialsDeprecated() {
    $credentials = '';
    $appendPassword = 0;
    if (ZM_OPT_USE_AUTH) {
      require_once __DIR__ .'/../../../includes/auth.php'; 
      if (ZM_AUTH_RELAY=='hashed') {
        $credentials = 'auth='.generateAuthHash(ZM_AUTH_HASH_IPS,true);
      }
      else {
        $credentials = 'user='.$this->Session->read('Username').'&pass=';
        $appendPassword = 1;
      }
      return array($credentials, $appendPassword);
    }
  }
  
  private function _getCredentials() {
    $credentials = '';
    $this->loadModel('Config');

    $isZmAuth = ZM_OPT_USE_AUTH;
    $jwt = '';
    $ttl = '';
  
    if ( $isZmAuth ) {
      require_once __DIR__ .'/../../../includes/auth.php'; 
      require_once __DIR__.'/../../../vendor/autoload.php';
      $zmAuthRelay = ZM_AUTH_RELAY;
      $zmAuthHashIps = NULL;
      if ( $zmAuthRelay == 'hashed' ) {
        $zmAuthHashIps = ZM_AUTH_HASH_IPS;
      } 

      $key = ZM_AUTH_HASH_SECRET;
      if ($zmAuthHashIps) {
        $key = $key . $_SERVER['REMOTE_ADDR'];
      }
      $issuedAt   = time();
      $ttl = ZM_AUTH_HASH_TTL || 2;

     // print ("relay=".$zmAuthRelay." haship=".$zmAuthHashIps." remote ip=".$_SERVER['REMOTE_ADDR']);

      $expireAt     = $issuedAt + $ttl * 3600;
      $expireAt = $issuedAt + 60; // TEST REMOVE
  
      $token = array(
          "iss" => "ZoneMinder",
          "iat" => $issuedAt,
          "exp" => $expireAt,
          "user" => $_SESSION['username']    
      );
    
      //use \Firebase\JWT\JWT;
      $jwt = \Firebase\JWT\JWT::encode($token, $key, 'HS256');

    } 
    return array($jwt, $ttl);
  } // end function _getCredentials

  // If $mid is set, only return disk usage for that monitor
  // Else, return an array of total disk usage, and per-monitor
  // usage.
  function getDiskPercent($mid = null) {
    $this->loadModel('Config');
    $this->loadModel('Monitor');

    // If $mid is passed, see if it is valid
    if ( $mid ) {
      if ( !$this->Monitor->exists($mid) ) {
        throw new NotFoundException(__('Invalid monitor'));
      }
    }

    $zm_dir_events = ZM_DIR_EVENTS;

    // Test to see if $zm_dir_events is relative or absolute
    #if ('/' === "" || strrpos($zm_dir_events, '/', -strlen($zm_dir_events)) !== TRUE) {
    if ( substr($zm_dir_events, 0, 1) != '/' ) {
      // relative - so add the full path
      $zm_dir_events = ZM_PATH_WEB . '/' . $zm_dir_events;
    }

    if ( $mid ) {
      // Get disk usage for $mid
      ZM\Logger::Debug("Executing du -s0 $zm_dir_events/$mid | awk '{print $1}'");
      $usage = shell_exec("du -s0 $zm_dir_events/$mid | awk '{print $1}'");
    } else {
      $monitors = $this->Monitor->find('all', array(
        'fields' => array('Id', 'Name', 'WebColour')
      ));
      $usage = array();

      // Add each monitor's usage to array
      foreach ($monitors as $key => $value) {
        $id = $value['Monitor']['Id'];
        $name = $value['Monitor']['Name'];
        $color = $value['Monitor']['WebColour'];

        $space = shell_exec ("du -s0 $zm_dir_events/$id | awk '{print $1}'");
        if ($space == null) {
          $space = 0;
        }
        $space = $space/1024/1024;

        $usage[$name] = array(
          'space' => rtrim($space),
          'color' => $color
        );
      }

      // Add total usage to array
      $space = shell_exec( "df $zm_dir_events |tail -n1 | awk '{print $3 }'");
      $space = $space/1024/1024;
      $usage['Total'] = array(
        'space' => rtrim($space),
        'color' => '#F7464A'
      );
    }

    $this->set(array(
      'usage' => $usage,
      '_serialize' => array('usage')
    ));
  }

  function getTimeZone() {
    //http://php.net/manual/en/function.date-default-timezone-get.php
    $tz = date_default_timezone_get();
    $this->set(array(
      'tz' => $tz,
      '_serialize' => array('tz')
    ));
  }

  private function _getVersion() {
    $version = Configure::read('ZM_VERSION');
    $apiversion = '2.0';
    return array($version, $apiversion);
  }

  function getVersion() {
    $val = $this->_getVersion();
    $this->set(array(
      'version' => $val[0],
      'apiversion' => $val[1],
      '_serialize' => array('version', 'apiversion')
    ));
  }
}
