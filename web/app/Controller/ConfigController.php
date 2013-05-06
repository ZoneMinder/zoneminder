<?php
	class ConfigController extends AppController {
  
		public function index() {
			$configs['fields'] = array('Name', 'Id',  'Value', 'Prompt', 'Type');
			$configs['order'] = array('Config.Category ASC');
			$this->set('configs', $this->Config->find('all', $configs));

			if (!empty($this->request->data)) {
				$data = array();
				foreach ($this->request->data['Config'] as $key => $value) {
					foreach ($value as $fieldName => $fieldValue) {
						$arr =	array('Config' => array('Name' => $fieldName, 'Value' => $fieldValue));
						array_push($data, $arr);
					}
				}

				if($this->Config->saveMany($data)) {
					$this->Session->setFlash('Your config has been updated.');
				} else {
					$this->Session->setFlash('Your config has not been updated.');
				}

			}
		}

	}
?>
