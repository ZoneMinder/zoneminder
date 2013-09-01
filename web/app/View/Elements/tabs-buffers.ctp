<div id="buffers">
<?php 
	echo $this->Form->inputs(array(
		'ImageBufferCount',
		'WarmupCount',
		'PreEventCount',
		'PostEventCount',
		'StreamReplayBuffer',
		'AlarmFrameCount',
		'legend' => false
	));
?>
</div>
