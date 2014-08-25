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

	public function buildString($name, $type) {

	}

	public function parseOptions($configs) {
			$string = "";
			foreach ($configs as $option) {

				$type = $option['Config']['Type'];
				$name = $option['Config']['Name'];
				$value = $option['Config']['Value'];
				$hint = $option['Config']['Hint'];

				switch ($type) {
					case "boolean":
						$string .= <<<EOD
<div class="checkbox">
	<label>
		<input type="checkbox" id="$name" value="$value">
		$name
	</label>
</div>
EOD;
						break;
					case "integer":
						$string .= <<<EOD
<div class="form-group">
	<label for="$name">$name</label>
	<input type="number" class="form-control" id="$name" value="$value">
</div>
EOD;
						break;
					case "text":
						$string .= $this->buildString($name, 'textbox');
						$string .= <<<EOD
<div class="form-group">
	<label for="$name">$name</label>
	<textarea class="form-control" rows="3">$value</textarea>
</div>
EOD;
						break;
					case "hexadecimal":
						$string .= <<<EOD
<div class="form-group">
	<label for="$name">$name</label>
	<input type="text" class="form-control" id="$name" value="$value">
</div>
EOD;

						break;
					case "decimal":
						$string .= <<<EOD
<div class="form-group">
	<label for="$name">$name</label>
	<input type="number" class="form-control" id="$name" value="$value">
</div>
EOD;
						break;
					case "string":
						if (strpos($hint, '|') === FALSE) {
							$string .= <<<EOD
<div class="form-group">
	<label for="$name">$name</label>
	<input type="number" class="form-control" id="$name" value="$value">
</div>
EOD;
						} else {
							$string .= <<<EOD
<div class="form-group">
	<label for="$name">$name</label>
	<select id="$name" class="form-control">

EOD;
							$string .= $this->parseHints($hint);
							$string .= <<<EOD
	</select>
</div>
EOD;
						}
						break;
				}
				$string .= "\n";
			}

		return $string;
	}
}
?>
