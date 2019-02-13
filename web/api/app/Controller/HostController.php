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
    $ver = $this->_getVersion();
    $this->set(array(
      'credentials' => $cred[0],
      'append_password'=>$cred[1],
      'version' => $ver[0],
      'apiversion' => $ver[1],
      '_serialize' => array('credentials',
                            'append_password',
                            'version',
                            'apiversion'
      )));
  } // end function login()

  // clears out session
  function logout() {
    global $user;
    $this->Session->Write('user', null);

    $this->set(array(
      'result' => 'ok',
      '_serialize' => array('result')
    ));

  } // end function logout()
  
  private function _getCredentials() {
    $credentials = '';
    $appendPassword = 0;
    $this->loadModel('Config');
    $isZmAuth = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_OPT_USE_AUTH')))['Config']['Value'];

    if ( $isZmAuth ) {
    // In future, we may want to completely move to AUTH_HASH_LOGINS and return &auth= for all cases
      require_once "../../../includes/auth.php"; # in the event we directly call getCredentials.json
      $this->Session->read('user'); # this is needed for command line/curl to recognize a session
      $zmAuthRelay = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_RELAY')))['Config']['Value'];
      if ( $zmAuthRelay == 'hashed' ) {
        $zmAuthHashIps = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_HASH_IPS')))['Config']['Value'];
        // make sure auth is regenerated each time we call this API
        $credentials = 'auth='.generateAuthHash($zmAuthHashIps,true);
      } else {
        // user will need to append the store password here
        $credentials = 'user='.$this->Session->read('user.Username').'&pass=';
        $appendPassword = 1;
      }
    }
    return array($credentials, $appendPassword);
  } // end function _getCredentials

  function getCredentials() {
    // ignore debug warnings from other functions
    $this->view='Json';
    $val = $this->_getCredentials();
    $this->set(array(
      'credentials'=> $val[0],
      'append_password'=>$val[1],
      '_serialize'  =>  array('credentials', 'append_password')
    ) );
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
      Logger::Debug("Executing du -s0 $zm_dir_events/$mid | awk '{print $1}'");
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
    $apiversion = '1.0';
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
