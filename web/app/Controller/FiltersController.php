<?php

class FiltersController extends AppController {
	public function index() {
		$this->set('filters', $this->Filter->find('all'));
	}
}

?>
