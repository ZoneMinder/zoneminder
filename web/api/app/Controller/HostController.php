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
      require_once "../../../includes/auth.php"; # in the event we directly call getCredentials.json
      $this->Session->read('user'); # this is needed for command line/curl to recognize a session
      $zmAuthRelay = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_RELAY')))['Config']['Value'];
      if ( $zmAuthRelay == 'hashed' ) {
        $zmAuthHashIps = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_HASH_IPS')))['Config']['Value'];
        $credentials = 'auth='.generateAuthHash($zmAuthHashIps);
      } else if ( $zmAuthRelay == 'plain' ) {
        // user will need to append the store password here
        $credentials = 'user='.$this->Session->read('user.Username').'&pass=';
        $appendPassword = 1;
      } else if ( $zmAuthRelay == 'none' ) {
        $credentials = 'user='.$this->Session->read('user.Username');
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

 function getDiskPercent($loc = null) {
       require_once "../../../includes/functions.php";
       if (!$loc) $loc = Configure::read('ZM_DIR_EVENTS');
       $usagePercent = getDiskPercent($loc);
       $this->set(array(
      'usagePercent' => $usagePercent,
      '_serialize' => array('usagePercent')
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
