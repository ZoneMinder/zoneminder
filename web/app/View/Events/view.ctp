<div id="videoPopup" style="display: inline-block; margin: 20px; padding-right: 40px;">

<video src="<?php echo $videoSrc; ?>" controls>
</video>

<p>Event <?php echo $event['Event']['Id']; ?> started at <?php echo $event['Event']['StartTime']; ?> and lasted for <?php echo $event['Event']['Length']; ?> seconds, containing <?php echo $event['Event']['Frames']; ?> frames.</p>

</div>