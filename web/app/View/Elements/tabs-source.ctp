<div id="source">
<?php
switch ($monitor['Type']) {
case 'Remote':
	echo $this->Form->input('Protocol', array(
		'type' => 'select',
		'options' => $protocoloptions
	));
	echo $this->Form->input('Method', array(
		'type' => 'select',
		'options' => $methodoptions
	));
	echo $this->Form->input('Host');
	echo $this->Form->input('Port');
	echo $this->Form->input('Path');
	break;

case 'Local':
	echo $this->Form->input('Path');
	echo $this->Form->input('Method', array(
		'type' => 'select',
		'options' => $optionsMethod
	));
	echo $this->Form->input('Channel', array(
		'type' => 'select',
		'options' => $channeloptions
	));
	echo $this->Form->input('Format', array(
		'type' => 'select',
		'options' => $formatoptions
	));
	echo $this->Form->input('Palette', array(
		'type' => 'select',
		'options' => $optionsPalette
	));
	break;

case 'File':
	break;
}

echo $this->Form->input('Colours', array(
	'type' => 'select',
	'options' => $optionsColours
));
echo $this->Form->input('Width');
echo $this->Form->input('Height');

?>
</div>
