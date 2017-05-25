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
		$load = sys_getloadavg();

		$this->set(array(
			'load' => $load,
			'_serialize' => array('load')
		));
	}

	// If $mid is set, only return disk usage for that monitor
  // Else, return an array of total disk usage, and per-monitor
  // usage.
	function getDiskPercent($mid = null) {
		$this->loadModel('Config');
		$this->loadModel('Monitor');

		// If $mid is passed, see if it is valid
		if ($mid) {	
			if (!$this->Monitor->exists($mid)) {
				throw new NotFoundException(__('Invalid monitor'));
			}
		}

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

		if ($mid) {
			// Get disk usage for $mid
			$usage = shell_exec ("du -sh0 $zm_dir_events/$mid | awk '{print $1}'");
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

    function getTimeZone()
    {
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
