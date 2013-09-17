<h2><?php echo $monitor['Monitor']['Name']; ?> Live Stream</h2>

<?php 
	if($daemonStatus && $monitor['Monitor']['Function'] != "None" && $monitor['Monitor']['Enabled'])
		echo $this->LiveStream->makeLiveStream($monitor['Monitor']['Name'], $streamSrc, $monitor['Monitor']['Id']); 
	else
		echo $this->LiveStream->showNoImage($monitor['Monitor']['Name'], $streamSrc, $monitor['Monitor']['Id']);
?>
