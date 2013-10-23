<?php
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

	echo $this->Form->input('Type', array('type' => 'select'));
	echo $this->Form->input('Preset', array('type' => 'select'));
	echo $this->Form->input('Units', array('type' => 'select'));
	echo $this->Form->input('CheckMethod', array('type' => 'select'));

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

	echo $this->Form->input('id', array('type' => 'hidden'));
	echo $this->Form->end(array('label' => 'Save Zone', 'class' => array('btn', 'btn-default')));
?>
