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
    $cred_depr = $this->_getCredentialsDeprecated();
    $ver = $this->_getVersion();

    $mUser = $this->request->query('user') ? $this->request->query('user') : $this->request->data('user');
    $mPassword = $this->request->query('pass') ? $this->request->query('pass') : $this->request->data('pass');
    $mToken = $this->request->query('token') ? $this->request->query('token') : $this->request->data('token');

    if ($mUser && $mPassword) {
      $cred = $this->_getCredentials(true);
      // if you authenticated via user/pass then generate new refresh
      $this->set(array(
        'access_token'=>$cred[0],
        'access_token_expires'=>$cred[1],
        'refresh_token'=>$cred[2],
        'refresh_token_expires'=>$cred[3],
        'credentials'=>$cred_depr[0],
        'append_password'=>$cred_depr[1],
        'version' => $ver[0],
        'apiversion' => $ver[1],
        '_serialize' => array(
                              'access_token',
                              'access_token_expires',
                              'refresh_token',
                              'refresh_token_expires',
                              'version',
                              'credentials',
                              'append_password',
                              'apiversion'
        )));
    }
    else {
      $cred = $this->_getCredentials(false);
      $this->set(array(
        'access_token'=>$cred[0],
        'access_token_expires'=>$cred[1],
        'credentials'=>$cred_depr[0],
        'append_password'=>$cred_depr[1],
        'version' => $ver[0],
        'apiversion' => $ver[1],
        '_serialize' => array(
                              'access_token',
                              'access_token_expires',
                              'version',
                              'credentials',
                              'append_password',
                              'apiversion'
        )));

    }

   
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
  
  private function _getCredentials($generate_refresh_token=false) {
    $credentials = '';
    $this->loadModel('Config');

    if ( ZM_OPT_USE_AUTH ) {
      require_once __DIR__ .'/../../../includes/auth.php'; 
      require_once __DIR__.'/../../../vendor/autoload.php';
    
      $key = ZM_AUTH_HASH_SECRET;
      if (!$key) {
        throw new ForbiddenException(__('Please create a valid AUTH_HASH_SECRET in ZoneMinder'));
      }

      /* we won't support AUTH_HASH_IPS in token mode
        reasons: 
        a) counter-intuitive for mobile consumers 
        b) zmu will never be able to to validate via a token if we sign
           it after appending REMOTE_ADDR
     
      if (ZM_AUTH_HASH_IPS) {
        $key = $key . $_SERVER['REMOTE_ADDR'];
      }*/

      $access_issued_at   = time();
      $access_ttl = (ZM_AUTH_HASH_TTL || 2) * 3600; 

      // by default access token will expire in 2 hrs
      // you can change it by changing the value of ZM_AUTH_HASH_TLL
      $access_expire_at     = $access_issued_at + $access_ttl;
      //$access_expire_at = $access_issued_at + 60; // TEST, REMOVE
  
      $access_token = array(
          "iss" => "ZoneMinder",
          "iat" => $access_issued_at,
          "exp" => $access_expire_at,
          "user" => $_SESSION['username'],
          "type" => "access"
      );
    
      $jwt_access_token = \Firebase\JWT\JWT::encode($access_token, $key, 'HS256');

      $jwt_refresh_token = "";
      $refresh_ttl = 0;

      if ($generate_refresh_token) {
        $refresh_issued_at   = time();
        $refresh_ttl = 24 * 3600; // 1 day
  
        $refresh_expire_at     = $refresh_issued_at + $refresh_ttl;
        $refresh_token = array(
            "iss" => "ZoneMinder",
            "iat" => $refresh_issued_at,
            "exp" => $refresh_expire_at,
            "user" => $_SESSION['username'],
            "type" => "refresh"  
        );
        $jwt_refresh_token = \Firebase\JWT\JWT::encode($refresh_token, $key, 'HS256');
      }
     
    } 
    return array($jwt_access_token, $access_ttl, $jwt_refresh_token, $refresh_ttl);
  }

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
