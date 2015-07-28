<?php
App::uses('Component', 'Controller');

class ConfigParserComponent extends Component {
	public function parseHints($hint) {
		$string = "";
		$hints = explode('|', $hint);
			foreach ($hints as $hint) {
				$string .= "<option value=\"$hint\">$hint</option>";
			}
		return $string;
	}

	public function getLabel($name) {
		$width = 'col-md-4';

		$string = '<div class="form-group">';
		$string .= '<label for="%s" class="control-label %s">%s</label>';
		$label = sprintf($string, $name, $width, $name);
		$label .= '<div class="col-md-6">';

		return $label;
	}

	public function getInput($name, $type, $id)  {
		if ($type == 'checkbox') {
			$string = '<input id="%s" type="checkbox" ng-change="updateConfig(\'%s\', \'%s\')" ng-model="myModel.configData[\'%s\']" ng-true-value="\'1\'" ng-false-value="\'0\'"><span class="form-control-feedback"></span></div>';
		} elseif ($type == 'text') {
			$string = '<input id="%s" type="text" ng-change="updateConfig(\'%s\', \'%s\')" ng-model="myModel.configData[\'%s\']" class="form-control"><span class="form-control-feedback"></span></div>';
		} elseif ($type == 'textarea') {
			$string = '<textarea id="%s" ng-change="updateConfig(\'%s\', \'%s\')" class="form-control" rows="3" ng-model="myModel.configData[\'%s\']"></textarea><span class="form-control-feedback"></span></div>';
		} elseif ($type == 'select') {
			$string = '<select id="%s" ng-change="updateConfig(\'%s\', \'%s\')" class="form-control" ng-model="myModel.configData[\'%s\']">';
		}

		$input = sprintf($string, $name, $id, $name, $name);
		return $input;
	}

	public function parseOptions($configs) {
			$category = $configs[0]['Config']['Category'];
			$string = "";

			foreach ($configs as $option) {

				$type = $option['Config']['Type'];
				$name = $option['Config']['Name'];
				$value = $option['Config']['Value'];
				$hint = $option['Config']['Hint'];
				$id = $option['Config']['Id'];

				$string .= $this->getLabel($name);

				switch ($type) {
					case "boolean":
						$string .= $this->getInput($name, 'checkbox', $id);
						break;
					case "text":
						$string .= $this->getInput($name, 'textarea', $id);
						break;
					case "string":
						if (strpos($hint, '|') === FALSE) {
							$string .= $this->getInput($name, 'text', $id);
						} else {
							$string .= $this->getInput($name, 'select', $id);
							$string .= $this->parseHints($hint);
							$string .= '</select> <span class="form-control-feedback"></span> </div>';
						}
						break;
					default:
						$string .= $this->getInput($name, 'text', $id);
				}
				$string .= "</div><!-- End .form-group -->\n";
			}
		return $string;
	}


}
?>
