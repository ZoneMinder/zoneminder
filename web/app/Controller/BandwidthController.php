<?php
class BandwidthController extends AppController {
  
	public function index() {
		$this->set('bandwidth', $this->Cookie->read('zmBandwidth'));

		if (!empty($this->request->data)) {
			$bandwidth = $this->request->data['Bandwidth']['Bandwidth'];
			$this->Cookie->write('zmBandwidth', $bandwidth, false);
			if ($this->Cookie->read('zmBandwidth') == $bandwidth) {
				$this->Session->setFlash('Successfully updated bandwidth');
			} else {
				$this->Session->setFlash('Failed to update bandwidth');
			}
		}
	}

}
?>
