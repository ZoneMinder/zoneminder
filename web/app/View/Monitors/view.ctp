<h2><?php echo $monitor['Monitor']['Name']; ?> Live Stream</h2>

<?php echo $this->LiveStream->makeLiveStream($monitor['Monitor']['Name'], $streamSrc, $monitor['Monitor']['Id']); ?>
