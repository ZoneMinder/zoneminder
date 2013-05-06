<h2><?php echo $monitor['Monitor']['Name']; ?> Live Stream</h2>
<img id="liveStream" alt="Live Stream of <?php echo $monitor['Monitor']['Name']; ?>" src="/cgi-bin/nph-zms?mode=jpeg&monitor=<?php echo $monitor['Monitor']['Id']; ?>&scale=100&maxfps=5&buffer=1000">
