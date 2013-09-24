<h2><?php echo $monitor['Monitor']['Name']; ?> Live Stream</h2>

<?php 
	if($daemonStatus && $monitor['Monitor']['Function'] != "None" && $monitor['Monitor']['Enabled'])
		echo $this->Html->image($streamSrc, array(
			'alt' => 'Live stream of ' . $monitor['Monitor']['Name'],
			'id' => 'liveStream_' . $monitor['Monitor']['Id']
		));
	else
		echo $this->LiveStream->showNoImage($monitor['Monitor']['Name'], $streamSrc, $monitor['Monitor']['Id']);
?>
