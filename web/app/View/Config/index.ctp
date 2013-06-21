<?php echo $this->Form->create('Config', array(
    'url' => '/config',
    'novalidate' => true
)); ?>

<div id="tabs">
<ul>
<?php
foreach ($categories as $key => $value) {
	$category = $value['Config']['Category'];
	echo '<li><a href="#tabs-'.$category.'">' . $category . '</a></li>';
}
?>
</ul>

<?php
foreach ($options as $option => $value) {
	echo "<div id=\"tabs-$option\">";
	foreach ($value as $val) {
		$id = $val['Config']['Id'];
		$inputname = 'Config.' . $id . '.' . $val['Config']['Name'];
		echo $this->Form->input($inputname, array(
			'default' => $val['Config']['Value'],
			'label' => $val['Config']['Name'],
			'title' => $val['Config']['Prompt']
		));
		echo "\n";
	}
	echo "</div>"; // End each category div
}
?>
</div> <!-- End the tabs div -->

<? echo $this->Form->end('Save Config'); ?>
