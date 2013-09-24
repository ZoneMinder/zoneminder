<div class="tab-pane" id="buffers">
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
