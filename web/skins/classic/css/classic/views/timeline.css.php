.chartSize {
    width: <?php echo $chart['width'] ?>px;
    height: <?php echo $chart['height'] ?>px;
}

.graphSize {
    width: <?php echo $chart['graph']['width'] ?>px;
    height: <?php echo $chart['graph']['height'] ?>px;
}

.graphHeight {
    height: <?php echo $chart['graph']['height'] ?>px;
}

.graphWidth {
    width: <?php echo $chart['graph']['width'] ?>px;
}

.imageSize {
    width: <?php echo $chart['image']['width'] ?>px;
    height: <?php echo $chart['image']['height'] ?>px;
}

.imageHeight {
    height: <?php echo $chart['image']['height'] ?>px;
}

.activitySize {
    width: <?php echo $chart['graph']['width'] ?>px;
    height: <?php echo $chart['graph']['activityHeight'] ?>px;
}

.eventsSize {
    width: <?php echo $chart['graph']['width'] ?>px;
    height: <?php echo $chart['graph']['eventBarHeight'] ?>px;
}

.eventsHeight {
    height: <?php echo $chart['graph']['eventBarHeight'] ?>px;
}
<?php
if ( $mode == "overlay" )
{
    foreach ( array_keys($monitorIds) as $monitorId )
    {
?>
#chartPanel .eventsPos<?php echo $monitorId ?> {
    top: <?php echo $chart['eventBars'][$monitorId]['top'] ?>px;
}
<?php
    }
}
elseif ( $mode == "split" )
{
    foreach ( array_keys($monitorIds) as $monitorId )
    {
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

foreach( array_keys($monEventSlots) as $monitorId )
{
?>
.monitorColour<?php echo $monitorId ?> {
    background-color: <?php echo $monitors[$monitorId]['WebColour'] ?>;
}
<?php
}
?>
