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

	public function parseOptions($configs) {
			$category = $configs[0]['Config']['Category'];
			$string = "";

			foreach ($configs as $option) {

				$type = $option['Config']['Type'];
				$name = $option['Config']['Name'];
				$value = $option['Config']['Value'];
				$hint = $option['Config']['Hint'];
				$id = $option['Config']['Id'];

				switch ($type) {
					case "boolean":
						$string .= <<<EOD
<div class="form-group">
	<label for="$name" class="control-label col-sm-4">$name</label>
	<div class="col-md-6"> <input id="$name" type="checkbox" ng-model="myModel.configData['$name']" ng-true-value="1" ng-false-value="0" ng-change="updateConfig('$id', '$name')"><span class="form-control-feedback"></span></div>
</div>
EOD;
						break;
					case "text":
						$string .= <<<EOD
<div class="form-group">
	<label for="$name" class="control-label col-sm-4">$name</label>
	<div class="col-md-6"><textarea ng-change="updateConfig('$id', '$name')" class="form-control" rows="3">$value</textarea><span class="form-control-feedback"></span></div>
</div>
EOD;
						break;
					case "string":
						if (strpos($hint, '|') === FALSE) {
							$string .= <<<EOD
<div class="form-group">
	<label for="$name" class="control-label col-sm-4">$name</label>
	<div class="col-md-6"><input id="$name" type="text" ng-change="updateConfig('$id', '$name')" class="form-control" ng-model="myModel.configData['$name']"><span class="form-control-feedback"></span></div>
</div>
EOD;
						} else {
							$string .= <<<EOD
<div class="form-group">
	<label for="$name" class="control-label col-sm-4">$name</label>
	<div class="col-md-6"><select id="$name" ng-change="updateConfig('$id', '$name')" class="form-control" ng-model="myModel.configData['$name']">

EOD;
							$string .= $this->parseHints($hint);
							$string .= <<<EOD
	</select>
<span class="form-control-feedback"></span>
	</div>
</div>
EOD;
						}
						break;
					default:
						$string .= <<<EOD
<div class="form-group">
	<label for="$name" class="control-label col-sm-4">$name</label>
	<div class="col-md-6"><input id="$name" type="text" ng-change="updateConfig('$id', '$name')" class="form-control" ng-model="myModel.configData['$name']"><span class="form-control-feedback"></span></div>
</div>
EOD;
				}
				$string .= "\n";
			}
		file_put_contents("/tmp/html/$category.html", $string);
		return $string;
	}


}
?>
