<?php

	class MonitorsController extends AppController {
		public $helpers = array('Html', 'Form');
  
		public function index() {
			$this->set('monitors', $this->Monitor->find('all'));
		}

		public function view($id = null) {
			if (!$id) {
				throw new NotFoundException(__('Invalid monitor'));
			}

			$monitor = $this->Monitor->findById($id);
			if (!$monitor) {
				throw new NotFoundException(__('Invalid monitor'));
			}
			$this->set('monitor', $monitor);
		}
	}

?>
