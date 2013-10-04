<h2><?php echo $monitor['Monitor']['Name']; ?> Live Stream</h2>

<?php 
	echo $this->Html->image($streamSrc['src'], array(
		'alt' => $streamSrc['alt'],
		'id' => $streamSrc['id']
	));
?>
