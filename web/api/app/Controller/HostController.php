<?php
App::uses('AppController', 'Controller');

class HostController extends AppController {
	
	public $components = array('RequestHandler');

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

 function getCredentials() {
    // ignore debug warnings from other functions
	$this->view='Json';
	$credentials = "";
	$appendPassword = 0;
	
	$this->loadModel('Config');
    $isZmAuth = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_OPT_USE_AUTH')))['Config']['Value'];
 
    if ($isZmAuth) {
        $zmAuthRelay = $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_RELAY')))['Config']['Value'];
        if ($zmAuthRelay == 'hashed') {
           $zmAuthHashIps= $this->Config->find('first',array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_AUTH_HASH_IPS')))['Config']['Value'];
            $credentials = 'auth='.generateAuthHash($zmAuthHashIps);
        }
        elseif ($zmAuthRelay == 'plain') {
            // user will need to append the store password here
            $credentials = "user=".$this->Session->read('user.Username')."&pass=";
            $appendPassword = 1;
        }
        elseif ($zmAuthRelay == 'none') {
            $credentials = "user=".$this->Session->read('user.Username');
        }    
    }
    $this->set(array(
      'credentials'=> $credentials,
      'append_password'=>$appendPassword,
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

	function getVersion() {
		//throw new UnauthorizedException(__('API Disabled'));
		$version = Configure::read('ZM_VERSION');
		// not going to use the ZM_API_VERSION
		// requires recompilation and dependency on ZM upgrade
		//$apiversion = Configure::read('ZM_API_VERSION');
		$apiversion = '1.0';

		$this->set(array(
			'version' => $version,
			'apiversion' => $apiversion,
			'_serialize' => array('version', 'apiversion')
		));
	}
}
