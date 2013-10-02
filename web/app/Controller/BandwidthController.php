<?php
class BandwidthController extends AppController {
  
	public function index() {
		if (!empty($this->request->data)) {
			$bandwidth = $this->request->data['Bandwidth']['Bandwidth'];
			Configure::write('zmBandwidth', $bandwidth);
			$this->set('bandwidth', $bandwidth);
			if (Configure::read('zmBandwidth') == $bandwidth) {
				$this->Session->setFlash('Successfully updated bandwidth');
			} else {
				$this->Session->setFlash('Failed to update bandwidth');
			}
		} else {
			$this->set('bandwidth', Configure::read('zmBandwidth'));
		}
	}

}
?>
