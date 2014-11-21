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
}
