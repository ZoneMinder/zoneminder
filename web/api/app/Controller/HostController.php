<?php
App::uses('AppController', 'Controller');

class HostController extends AppController {

  public $components = array('RequestHandler');

  public function daemonCheck($daemon=false, $args=false) {
    $string = ZM_PATH_BIN.'/zmdc.pl check';
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

    $username = $this->request->query('user') ? $this->request->query('user') : $this->request->data('user');
    if ( !$username )
      $username = $this->request->query('username') ? $this->request->query('username') : $this->request->data('username');
    $password = $this->request->query('pass') ? $this->request->query('pass') : $this->request->data('pass');
    if ( !$password )
      $password = $this->request->query('password') ? $this->request->query('password') : $this->request->data('password');

    $token = $this->request->query('token') ? $this->request->query('token') : $this->request->data('token');

    if ( !($username && $password) && !$token ) {
      throw new UnauthorizedException(__('No identity provided'));
    }

    $ver = $this->_getVersion();
    $cred = [];
    $cred_depr = [];

    if ( $username && $password ) {
      ZM\Logger::Debug('Username and password provided, generating access and refresh tokens');
      $cred = $this->_getCredentials(true, '', $username); // generate refresh
    } else {
      ZM\Logger::Debug('Only generating access token');
      $cred = $this->_getCredentials(false, $token); // don't generate refresh
    }

    $login_array = array (
      'access_token'          => $cred[0],
      'access_token_expires'  => $cred[1]
    );

    if ( $username && $password ) {
      $login_array['refresh_token'] = $cred[2];
      $login_array['refresh_token_expires'] = $cred[3];
    }

    if ( ZM_OPT_USE_LEGACY_API_AUTH ) {
      $cred_depr = $this->_getCredentialsDeprecated();
      $login_array['credentials'] = $cred_depr[0];
      $login_array['append_password'] = $cred_depr[1];
    } else {
      ZM\Logger::Debug('Legacy Auth is disabled, not generating auth= credentials');
    }

    $login_array['version'] = $ver[0];
    $login_array['apiversion'] = $ver[1];

    $login_array['_serialize'] = array_keys($login_array);

    $this->set($login_array);

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
    if ( ZM_OPT_USE_AUTH ) {
      require_once __DIR__ .'/../../../includes/auth.php';
      if ( ZM_AUTH_RELAY == 'hashed' ) {
        $credentials = 'auth='.generateAuthHash(ZM_AUTH_HASH_IPS, true);
      } else {
        $credentials = 'user='.$_SESSION['Username'].'&pass=';
        $appendPassword = 1;
      }
      return array($credentials, $appendPassword);
    }
  }

  private function _getCredentials($generate_refresh_token=false, $token='', $username='') {

    if ( !ZM_OPT_USE_AUTH ) {
      ZM\Error('OPT_USE_AUTH is turned off. Tokens will be null');
      return;
    }
      

    if ( !ZM_AUTH_HASH_SECRET )
      throw new ForbiddenException(__('Please create a valid AUTH_HASH_SECRET in ZoneMinder'));

    require_once __DIR__ .'/../../../includes/auth.php';
    require_once __DIR__.'/../../../vendor/autoload.php';

    if ( $token ) {
      // If we have a token, we need to derive username from there
      $ret = validateToken($token, 'refresh', true);
      $username = $ret[0]['Username'];
    }

    ZM\Info("Creating token for \"$username\"");

    /* we won't support AUTH_HASH_IPS in token mode
      reasons:
      a) counter-intuitive for mobile consumers
      b) zmu will never be able to to validate via a token if we sign
         it after appending REMOTE_ADDR

    if (ZM_AUTH_HASH_IPS) {
      $key = $key . $_SERVER['REMOTE_ADDR'];
    }*/

    $access_issued_at = time();
    $access_ttl = max(ZM_AUTH_HASH_TTL,1) * 3600;

    // by default access token will expire in 2 hrs
    // you can change it by changing the value of ZM_AUTH_HASH_TLL
    $access_expire_at     = $access_issued_at + $access_ttl;
    //$access_expire_at = $access_issued_at + 60; // TEST, REMOVE

    $access_token = array(
        'iss' => 'ZoneMinder',
        'iat' => $access_issued_at,
        'exp' => $access_expire_at,
        'user' => $username,
        'type' => 'access'
    );

    $jwt_access_token = \Firebase\JWT\JWT::encode($access_token, ZM_AUTH_HASH_SECRET, 'HS256');

    $jwt_refresh_token = '';
    $refresh_ttl = 0;

    if ( $generate_refresh_token ) {
      $refresh_issued_at = time();
      $refresh_ttl = 24 * 3600; // 1 day

      $refresh_expire_at = $refresh_issued_at + $refresh_ttl;
      $refresh_token = array(
          'iss' => 'ZoneMinder',
          'iat' => $refresh_issued_at,
          'exp' => $refresh_expire_at,
          'user' => $username,
          'type' => 'refresh'
      );
      $jwt_refresh_token = \Firebase\JWT\JWT::encode($refresh_token, ZM_AUTH_HASH_SECRET, 'HS256');
    } # end if generate_refresh_token
    return array($jwt_access_token, $access_ttl, $jwt_refresh_token, $refresh_ttl);
  } # end function _getCredentials($generate_refresh_token=false, $token='')

  // If $mid is set, only return disk usage for that monitor
  // Else, return an array of total disk usage, and per-monitor
  // usage.
  // This function is deprecated.  Use the Storage object or monitor object instead
  function getDiskPercent($mid = null) {
    $this->loadModel('Monitor');

    // If $mid is passed, see if it is valid
    if ( $mid and !$this->Monitor->exists($mid) ) {
      throw new NotFoundException(__('Invalid monitor'));
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

        $space = shell_exec("du -s0 $zm_dir_events/$id | awk '{print $1}'");
        if ( $space == null ) {
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
    $version = ZM_VERSION;
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
