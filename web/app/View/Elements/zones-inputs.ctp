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
			'label' => false,
			'div' => false,
			'required' => true
		),
		'class' => 'form-horizontal'
	));

	echo $this->element('zones-input', array('field'=>'Name'));

#	echo $this->element('zones-input', array('Type', array('type' => 'select', 'options' => $optionsType)));
#	echo $this->element('zones-input', array('Preset', array('type' => 'select', 'options' => $optionsPreset)));
#	echo $this->element('zones-input', array('Units', array('type' => 'select', 'options' => $optionsUnits)));
#	echo $this->element('zones-input', array('CheckMethod', array('type' => 'select', 'options' => $optionsCheckMethod)));

	echo $this->element('zones-input', array('field'=>'MinPixelThreshold'));
	echo $this->element('zones-input', array('field'=>'MaxPixelThreshold'));

	echo $this->element('zones-input', array('field' => 'MinAlarmPixels'));
	echo $this->element('zones-input', array('field' => 'MaxAlarmPixels'));

	echo $this->element('zones-input', array('field' => 'FilterX'));
	echo $this->element('zones-input', array('field' => 'FilterY'));

	echo $this->element('zones-input', array('field' => 'MinFilterPixels'));
	echo $this->element('zones-input', array('field' => 'MaxFilterPixels'));

	echo $this->element('zones-input', array('field' => 'MinBlobPixels'));
	echo $this->element('zones-input', array('field' => 'MaxBlobPixels'));

	echo $this->element('zones-input', array('field' => 'MinBlobs'));
	echo $this->element('zones-input', array('field' => 'MaxBlobs'));

	echo $this->element('zones-input', array('field' => 'OverloadFrames'));

	echo $this->Form->input('Id', array('type' => 'hidden'));
	echo $this->Form->input('MonitorId', array('type' => 'hidden', 'default' => $mid));
	echo $this->Form->input('NumCoords', array('type' => 'hidden'));
	echo $this->Form->input('Coords', array('type' => 'hidden'));
	echo $this->Form->end(array('label' => 'Save Zone', 'class' => array('btn', 'btn-default')));
?>
