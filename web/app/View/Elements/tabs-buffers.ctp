<div class="tab-pane" id="buffers">
<?php 
	echo $this->Form->inputs(array(
		'ImageBufferCount' => array('after' => '<span class="help-block">This option determines how many frames are held in the ring buffer at any one time</span>'),
		'WarmupCount' => array('after' => '<span class="help-block">How many frames the analysis daemon should process but not examine when it starts. This allows it to generate an accurate reference image from a series of images before looking too carefully for any changes</span>'),
		'PreEventCount' => array('after' => '<span class="help-block">How many frames from before the event to include in the event.</span>'),
		'PostEventCount' => array('after' => '<span class="help-block">How many frames from after the event to include in the event.</span>'),
		'StreamReplayBuffer' => array('after' => '<span class="help-block"></span>'),
		'AlarmFrameCount' => array('after' => '<span class="help-block">How many consecutive alarm frames must occur before an alarm event is generated</span>'),
		'legend' => false
	));
?>
</div>
