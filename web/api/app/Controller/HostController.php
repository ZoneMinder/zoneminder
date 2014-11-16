<?php
App::uses('AppController', 'Controller');

class HostController extends AppController {
	
	public $components = array('RequestHandler');

	public function daemonCheck($daemon=false, $args=false) {
    $string = "`which zmdc.pl` check";
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

}
