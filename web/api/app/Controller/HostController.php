<?php
App::uses('AppController', 'Controller');

class HostController extends AppController {
	
	public $components = array('RequestHandler');

	public function daemonCheck($daemon=false, $args=false) {
    $string = Configure::read('ZM_PATH_BIN')."/zmdc.pl check";
    if ( $daemon )
    {
        $string .= " $daemon";
        if ( $args )
            $string .= " $args";
    }
    $result = exec( $string );
    $result = preg_match( '/running/', $result );

		$this->set(array(
			'result' => $result,
			'_serialize' => array('result')
		));
	}

	function getLoad() {
		$uptime = shell_exec( 'uptime' );
		$load = '';
		if ( preg_match( '/load average: ([\d.]+)/', $uptime, $matches ) )
			$load = $matches[1];
	
		$this->set(array(
			'load' => $load,
			'_serialize' => array('load')
		));
	}

	function getDiskPercent() {
		$this->loadModel('Config');
		$zm_dir_events = $this->Config->find('list', array(
			'conditions' => array('Name' => 'ZM_DIR_EVENTS'),
			'fields' => array('Name', 'Value')
		));
		$zm_dir_events = $zm_dir_events['ZM_DIR_EVENTS' ];

		// Test to see if $zm_dir_events is relative or absolute
		if ('/' === "" || strrpos($zm_dir_events, '/', -strlen($zm_dir_events)) !== TRUE) {
			// relative - so add the full path
			$zm_dir_events = Configure::read('ZM_PATH_WEB') . '/' . $zm_dir_events;
		}

		$df = shell_exec( 'df '.$zm_dir_events);
		$space = -1;
		if ( preg_match( '/\s(\d+)%/ms', $df, $matches ) )
			$space = $matches[1];

		$this->set(array(
			'space' => $space,
			'_serialize' => array('space')
		));
	}

	function getVersion() {
		$version = Configure::read('ZM_VERSION');

		$this->set(array(
			'version' => $version,
			'_serialize' => array('version')
		));
	}
}
