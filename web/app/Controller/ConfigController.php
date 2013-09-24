<?php
	class ConfigController extends AppController {
  
		public function index() {
			// Get a list of categories
			$categories = $this->Config->find('all', array('fields' => array('Category'), 'group' => array('Category'), 'conditions' => array('Category !=' => 'hidden')));
			$this->set('categories', $categories);

			// Build an array of categories with each child option under that category
			$options = array();
			foreach ($categories as $category) {
				$name = $category['Config']['Category'];
				$configs = $this->Config->findAllByCategory($name,
				   	array('Name', 'Id',  'Value', 'Prompt', 'Type', 'Category', 'Hint'), 'Type');
				$options[$name] = $configs;
			}

			// Pass the completed array to the view
			$this->set('options', $options);

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
