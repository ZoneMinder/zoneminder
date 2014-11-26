.chartSize {
    width: <?= $chart['width'] ?>px;
    height: <?= $chart['height'] ?>px;
}

.graphSize {
    width: <?= $chart['graph']['width'] ?>px;
    height: <?= $chart['graph']['height'] ?>px;
}

.graphHeight {
    height: <?= $chart['graph']['height'] ?>px;
}

.graphWidth {
    width: <?= $chart['graph']['width'] ?>px;
}

.imageSize {
    width: <?= $chart['image']['width'] ?>px;
    height: <?= $chart['image']['height'] ?>px;
}

.imageHeight {
    height: <?= $chart['image']['height'] ?>px;
}

.activitySize {
    width: <?= $chart['graph']['width'] ?>px;
    height: <?= $chart['graph']['activityHeight'] ?>px;
}

.eventsSize {
    width: <?= $chart['graph']['width'] ?>px;
    height: <?= $chart['graph']['eventBarHeight'] ?>px;
}

.eventsHeight {
    height: <?= $chart['graph']['eventBarHeight'] ?>px;
}
<?php
if ( $mode == "overlay" )
{
    foreach ( array_keys($monitorIds) as $monitorId )
    {
?>
#chartPanel .eventsPos<?= $monitorId ?> {
    top: <?= $chart['eventBars'][$monitorId]['top'] ?>px;
}
<?php
    }
}
elseif ( $mode == "split" )
{
    foreach ( array_keys($monitorIds) as $monitorId )
    {
?>
#chartPanel .activityPos<?= $monitorId ?> {
    top: <?= $char['activityBars'][$monitorId]['top'] ?>px;
}

#chartPanel .eventsPos<?= $monitorId ?> {
    top: <?= $char['eventBars'][$monitorId]['top'] ?>px;
}
<?php
    }
}

foreach( array_keys($monEventSlots) as $monitorId )
{
?>
.monitorColour<?= $monitorId ?> {
    background-color: <?= $monitors[$monitorId]['WebColour'] ?>;
}
<?php
}
?>
