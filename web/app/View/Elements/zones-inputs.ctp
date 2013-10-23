<?php
	$optionsCheckMethod = array(
		'AlarmedPixels' => 'AlarmedPixels',
		'Blobs' => 'Blobs',
		'FilteredPixels' => 'FilteredPixels'
	);
	$optionsUnits = array(
		'Pixels' => 'Pixels',
		'Percent' => 'Percent'
	);
	$optionsPreset = array(
		'Fast, low sensitivity',
		'Fast, medium sensitivity',
		'Fast, high sensitivity',
		'Best, low sensitivity',
		'Best, medium sensitivity',
		'Best, high sensitivity'
	);
	$optionsType = array(
		'Inclusive' => 'Inclusive',
		'Exclusive' => 'Exclusive',
		'Preclusive' => 'Preclusive',
		'Active' => 'Active',
		'Inactive' => 'Inactive'
	);

	echo $this->Form->create('Zone', array(
		'inputDefaults' => array(
			'legend' => false,
			'fieldset' => false,
			'label' => array('class' => array('control-label')),
			'div' => array('class' => array('form-group')),
			'class' => 'form-control'
		),
		'class' => 'form-horizontal'
	));

	echo $this->Form->input('Name');

	echo $this->Form->input('Type', array('type' => 'select', 'options' => $optionsType));
	echo $this->Form->input('Preset', array('type' => 'select', 'options' => $optionsPreset));
	echo $this->Form->input('Units', array('type' => 'select', 'options' => $optionsUnits));
	echo $this->Form->input('CheckMethod', array('type' => 'select', 'options' => $optionsCheckMethod));

	echo $this->Form->input('MinPixelThreshold');
	echo $this->Form->input('MaxPixelThreshold');

	echo $this->Form->input('MinAlarmPixels');
	echo $this->Form->input('MaxAlarmPixels');

	echo $this->Form->input('FilterX');
	echo $this->Form->input('FilterY');

	echo $this->Form->input('MinFilterPixels');
	echo $this->Form->input('MaxFilterPixels');

	echo $this->Form->input('MinBlobPixels');
	echo $this->Form->input('MaxBlobPixels');

	echo $this->Form->input('MinBlobs');
	echo $this->Form->input('MaxBlobs');

	echo $this->Form->input('OverloadFrames');

	echo $this->Form->input('Id', array('type' => 'hidden'));
	echo $this->Form->input('NumCoords', array('type' => 'hidden'));
	echo $this->Form->input('Coords', array('type' => 'hidden'));
	echo $this->Form->end(array('label' => 'Save Zone', 'class' => array('btn', 'btn-default')));
?>
