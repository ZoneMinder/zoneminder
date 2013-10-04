<?php

class ZonesController extends AppController {
    public function index() {
	$this->set('zones', $this->Zone->find('all'));
    }

}

?>
