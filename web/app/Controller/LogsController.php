<?php
class LogsController extends AppController {
	public function index() {
		$conditions = array();
		$named = $this->extractNamedParams(
			array('Component')
		);

		if ($named) {
			foreach ($named as $key => $value) {
				switch ($key) {
				case "Component":
					$Component = array($key => $value);
					array_push($conditions, $Component);
					break;
				}
			}
		};

		$this->set('loglines', $this->Log->find('all', array(
			'limit' => 100,
			'order' => array('TimeKey' => 'desc'),
			'conditions' => $conditions
		)));
		$this->set('components', $this->Log->find('all', array(
			'fields' => array('DISTINCT Component')	
		)));
	}
}
?>
