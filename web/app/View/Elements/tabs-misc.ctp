<div class="tab-pane" id="misc">
<?php
	echo $this->Form->inputs(array(
		'EventPrefix',
		'SectionLength',
		'FrameSkip',
		'FPSReportInterval',
		'DefaultView',
		'DefaultRate',
		'DefaultScale',
		'WebColour',
		'legend' => false
	));

	echo $this->Form->input('LinkedMonitors', array(
		'type' => 'checkbox',
		'options' => $linkedMonitors,
	));

?>
</div>
