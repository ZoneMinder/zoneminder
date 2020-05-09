.chartSize {
    height: <?php echo $chart['height'] ?>px;
}

.graphSize {
    height: <?php echo $chart['graph']['height'] ?>px;
}

.graphHeight {
    height: <?php echo $chart['graph']['height'] ?>px;
}

.graphWidth {
}

.imageSize {
}

.imageHeight {
<?php
switch ( $max_aspect_ratio ) {
  case 1:
    echo 'padding-top: 100%;'; break;
  case 1.33: // 4:3
    echo 'padding-top: 75%;'; break;
  case 1.47:
   echo 'padding-top: 68.18%;'; break;
  case 1.5: // 3:2
    echo 'padding-top: 66.66%'; break;
  case 1.6: // 8:5
    echo 'padding-top: 62.5%'; break;
  case 1.78: // 16:9
   echo 'padding-top: 56.25%;'; break;
  default:
    ZM\Error("Unknown aspect ratio $max_aspect_ratio");
    echo 'padding-top: 100%;';
}
?>
}

.activitySize {
    height: <?php echo $chart['graph']['activityHeight'] ?>px;
}

.eventsSize {
    height: <?php echo $chart['graph']['eventBarHeight'] ?>px;
}

.events .event {
    height: <?php echo $chart['graph']['eventBarHeight'] ?>px;
}
<?php
if ( $mode == 'overlay' ) {
    foreach ( array_keys($monitors) as $monitorId ) {
?>
#chartPanel .eventsPos<?php echo $monitorId ?> {
    top: <?php echo $chart['eventBars'][$monitorId]['top'] ?>px;
}
<?php
    }
} else if ( $mode == 'split' ) {
    foreach ( array_keys($monitors) as $monitorId ) {
?>
#chartPanel .activityPos<?php echo $monitorId ?> {
    top: <?php echo $char['activityBars'][$monitorId]['top'] ?>px;
}

#chartPanel .eventsPos<?php echo $monitorId ?> {
    top: <?php echo $char['eventBars'][$monitorId]['top'] ?>px;
}
<?php
    }
}

foreach ( array_keys($monEventSlots) as $monitorId ) {
?>
.monitorColour<?php echo $monitorId ?> {
  background-color: <?php echo $monitors[$monitorId]->WebColour() ?>;
}
<?php
}
?>
