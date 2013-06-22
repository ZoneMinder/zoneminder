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
		$inputname = 'Config.' . $id . '.' . $val['Config']['Name'];

		switch ($val['Config']['Type']) {
			case 'boolean':
				$type = 'checkbox';
				break;
			case 'integer':
				$type = 'text';
				break;
			case 'string':
				$type = 'text';
				break;
		}
		echo $this->Form->input($inputname, array(
			'default' => $val['Config']['Value'],
			'label' => $val['Config']['Name'],
			'title' => $val['Config']['Prompt'],
			'type' => $type
		));
		echo "\n";
	}
	echo "</div>"; // End each category div
}
?>
</div> <!-- End the tabs div -->

<? echo $this->Form->end('Save Config'); ?>
