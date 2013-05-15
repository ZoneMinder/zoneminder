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

#mysql> describe Monitors;
#+--------------------+------------------------------------------------------------+------+-----+------------------------+----------------+
#| Field              | Type                                                       | Null | Key | Default                | Extra          |
#+--------------------+------------------------------------------------------------+------+-----+------------------------+----------------+
#| Id                 | int(10) unsigned                                           | NO   | PRI | NULL                   | auto_increment |
#| Name               | varchar(64)                                                | NO   |     |                        |                |
#| Type               | enum('Local','Remote','File','Ffmpeg')                     | NO   |     | Local                  |                |
#| Function           | enum('None','Monitor','Modect','Record','Mocord','Nodect') | NO   |     | Monitor                |                |
#| Enabled            | tinyint(3) unsigned                                        | NO   |     | 1                      |                |
#| LinkedMonitors     | varchar(255)                                               | NO   |     |                        |                |
#| Triggers           | set('X10')                                                 | NO   |     |                        |                |
#| Device             | varchar(64)                                                | NO   |     |                        |                |
#| Channel            | tinyint(3) unsigned                                        | NO   |     | 0                      |                |
#| Format             | int(10) unsigned                                           | NO   |     | 0                      |                |
#| Protocol           | varchar(16)                                                | NO   |     |                        |                |
#| Method             | varchar(16)                                                | NO   |     |                        |                |
#| Host               | varchar(64)                                                | NO   |     |                        |                |
#| Port               | varchar(8)                                                 | NO   |     |                        |                |
#| SubPath            | varchar(64)                                                | NO   |     |                        |                |
#| Path               | varchar(255)                                               | NO   |     |                        |                |
#| Width              | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| Height             | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| Colours            | tinyint(3) unsigned                                        | NO   |     | 1                      |                |
#| Palette            | int(10) unsigned                                           | NO   |     | 0                      |                |
#| Orientation        | enum('0','90','180','270','hori','vert')                   | NO   |     | 0                      |                |
#| Deinterlacing      | int(10) unsigned                                           | NO   |     | 0                      |                |
#| Brightness         | mediumint(7)                                               | NO   |     | -1                     |                |
#| Contrast           | mediumint(7)                                               | NO   |     | -1                     |                |
#| Hue                | mediumint(7)                                               | NO   |     | -1                     |                |
#| Colour             | mediumint(7)                                               | NO   |     | -1                     |                |
#| EventPrefix        | varchar(32)                                                | NO   |     | Event-                 |                |
#| LabelFormat        | varchar(64)                                                | NO   |     | %N - %y/%m/%d %H:%M:%S |                |
#| LabelX             | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| LabelY             | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| ImageBufferCount   | smallint(5) unsigned                                       | NO   |     | 100                    |                |
#| WarmupCount        | smallint(5) unsigned                                       | NO   |     | 25                     |                |
#| PreEventCount      | smallint(5) unsigned                                       | NO   |     | 10                     |                |
#| PostEventCount     | smallint(5) unsigned                                       | NO   |     | 10                     |                |
#| StreamReplayBuffer | int(10) unsigned                                           | NO   |     | 1000                   |                |
#| AlarmFrameCount    | smallint(5) unsigned                                       | NO   |     | 1                      |                |
#| SectionLength      | int(10) unsigned                                           | NO   |     | 600                    |                |
#| FrameSkip          | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| MaxFPS             | decimal(5,2)                                               | YES  |     | NULL                   |                |
#| AlarmMaxFPS        | decimal(5,2)                                               | YES  |     | NULL                   |                |
#| FPSReportInterval  | smallint(5) unsigned                                       | NO   |     | 250                    |                |
#| RefBlendPerc       | tinyint(3) unsigned                                        | NO   |     | 10                     |                |
#| Controllable       | tinyint(3) unsigned                                        | NO   |     | 0                      |                |
#| ControlId          | int(10) unsigned                                           | NO   |     | 0                      |                |
#| ControlDevice      | varchar(255)                                               | YES  |     | NULL                   |                |
#| ControlAddress     | varchar(255)                                               | YES  |     | NULL                   |                |
#| AutoStopTimeout    | decimal(5,2)                                               | YES  |     | NULL                   |                |
#| TrackMotion        | tinyint(3) unsigned                                        | NO   |     | 0                      |                |
#| TrackDelay         | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| ReturnLocation     | tinyint(3)                                                 | NO   |     | -1                     |                |
#| ReturnDelay        | smallint(5) unsigned                                       | NO   |     | 0                      |                |
#| DefaultView        | enum('Events','Control')                                   | NO   |     | Events                 |                |
#| DefaultRate        | smallint(5) unsigned                                       | NO   |     | 100                    |                |
#| DefaultScale       | smallint(5) unsigned                                       | NO   |     | 100                    |                |
#| SignalCheckColour  | varchar(32)                                                | NO   |     | #0000BE                |                |
#| WebColour          | varchar(32)                                                | NO   |     | red                    |                |
#| Sequence           | smallint(5) unsigned                                       | YES  |     | NULL                   |                |
#| UsedPl             | varchar(88)                                                | NO   |     |                        |                |
#| DoNativeMotDet     | tinyint(3) unsigned                                        | NO   |     | 1                      |                |
#+--------------------+------------------------------------------------------------+------+-----+------------------------+----------------+
#59 rows in set (0.00 sec)

?>
