<h2>Add Monitor</h2>

<?php
$typeoptions = array("Local" => "Local", "Remote" => "Remote", "File" => "File", "Ffmpeg" => "Ffmpeg");
$functionoptions = array('Modect' => 'Modect', 'Monitor' => 'Monitor', 'Record' => 'Record', 'None' => 'None', 'Nodect' => 'Nodect', 'Mocord' => 'Mocord');
$defaultviewoptions = array('Events' => 'Events', 'Control' => 'Control');
$defaultrateoptions = array(10000 => '100x', 5000 => '50x', 2500 => '25x', 1000 => '10x', 400 => '4x', 200 => '2x', 100 => 'Real', 50 => '1/2x', 25 => '1/4x');
$defaultscaleoptions = array(400 => '4x', 300 => '4x', 200 => '2x', 100 => 'Actual', 75 => '3/4x', 50 => '1/2x', 33 => '1/3x', 25 => '1/4x');

echo $this->Form->create('Monitor');
echo $this->Form->inputs(array(
	'legend' => 'General',
	'Name',
	'Type' => array('type' => 'select', 'options' => $typeoptions),
	'Function' => array('type' => 'select', 'options' => $functionoptions),
	'Enabled' => array('type' => 'checkbox')
));
echo $this->Form->inputs(array(
	'legend' => 'Buffers',
	'Image Buffer Size (frames)',
	'Warmup Frames',
	'Pre Event Image Count',
	'Post Event Image Count',
	'Stream Reply Image Buffer',
	'Alarm Frame Count'
));
echo $this->Form->inputs(array(
	'Timestamp Label Format' => array('default' => '%N - %d/%m/%y %H:%M:%S'),
	'Timestamp Label X' => array('default' => 0),
	'Timestamp Label Y' => array('default' => 0)
));
echo $this->Form->inputs(array(
	'legend' => 'Misc',
	'Event Prefix' => array('default' => 'Event-'),
	'Section Length' => array('default' => 600),
	'Frame Skip' => array('default' => 0),
	'FPS Report Interval' => array('default' => 1000),
	'Web Colour' => array('default' => 'red'),
	'Signal Check Colour' => array('default' => '#0000c0'),
	'Default View' => array('type' => 'select', 'options' => $defaultviewoptions, 'selected' => 'Events'),
	'Default Rate' => array('type' => 'select', 'options' => $defaultrateoptions, 'selected' => 100),
	'Default Scale' => array('type' => 'select', 'options' => $defaultscaleoptions, 'selected' => 100)
));
echo $this->Form->end('Save');

?>
