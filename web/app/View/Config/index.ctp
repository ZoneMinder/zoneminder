<?php echo $this->Form->create('Config', array(
    'url' => '/config',
    'novalidate' => true
)); ?>

<div id="tabs">
<ul>
<?php
foreach ($categories as $key => $value) {
	$category = $value['Config']['Category'];
	echo '<li><a href="#tabs-'.$category.'">' . ucfirst($category) . '</a></li>';
}
?>
</ul>

<?php
foreach ($options as $option => $value) {
	echo "<div id=\"tabs-$option\">";
	foreach ($value as $val) {
		$id = $val['Config']['Id'];
		$name = $val['Config']['Name'];
		$inputname = 'Config.' . $id . '.' . $name;
		$type = $val['Config']['Type'];
		$hint = $val['Config']['Hint'];
		$hints = explode('|', $hint);
		$selectoptions = array();

		// Because I don't want to modify the database, I need
		// to work with what is already there to determine
		// which types of inputs to use.  I can do this based
		// off of hthe 'hint' fileld.
		//
		// If 'hint' contains '|', it is either a radio
		// or select box, though I'm making all radios selects.
		// If the 'hint' contains '|' && '=', it was supposed to be a
		// select.
		//
		// This would be much easier if each Config row had an 'inputtype' column...

		// If the type is supposed to be a radio...
		// I'm making it a select anyway!
		if (preg_match("/\|/", $hint) && ($type != 'boolean')  ) {
			$type = 'select';
			foreach ($hints as $hint) {
				$foo = explode('|', $hint);
				$selectoptions[$foo[0]] = $foo[0]; // I don't want my selects indexed - I want them associated.
			}
		}

		// If the type is supposed to be a select box...
		if ( preg_match("/\=/", $hint) && ($type == 'select') ) {
			$selectoptions = array();
			foreach ($hints as $hint) {
				$foo = explode('=', $hint);
				$selectoptions[$foo[1]] = $foo[0];
			}
		}

		// For all of the other types, set them appropriately.
		switch ($type) {
			case 'boolean':
				$type = 'checkbox';
				break;
			case 'integer':
				$type = 'text';
				break;
			case 'string':
				$type = 'text';
				break;
			case 'text':
				$type = 'textarea';
				break;
		}

		// Create the actual inputs.  'options' and 'legend'
		// are ignored when they're not needed, such as in
		// the case of a text or checkbox input type.
		echo $this->Form->input($inputname, array(
			'default' => $val['Config']['Value'],
			'label' => $name,
			'title' => $val['Config']['Prompt'],
			'type' => $type,
			'options' => $selectoptions, // Only used by cakephp when 'type' is 'select'
			'legend' => false
		));
	}
	echo "</div>"; // End each category div
}
?>
</div> <!-- End the tabs div -->

<? echo $this->Form->end('Save Config'); ?>
