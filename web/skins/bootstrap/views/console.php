<?php
//
// ZoneMinder web console file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

$group = NULL;
if ( ! empty($_COOKIE['zmGroup']) ) {
	if ( $group = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_COOKIE['zmGroup'])) )
		$groupIds = array_flip(explode( ',', $group['MonitorIds'] ));
}

noCacheHeaders();

$maxWidth = 0;
$maxHeight = 0;
$cycleCount = 0;
$minSequence = 0;
$maxSequence = 1;
$seqIdList = array();
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
$displayMonitors = array();
for ( $i = 0; $i < count($monitors); $i++ )
{
    if ( !visibleMonitor( $monitors[$i]['Id'] ) )
    {
        continue;
    }
    if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) )
    {
        continue;
    }
    $monitors[$i]['Show'] = true;
    if ( empty($minSequence) || ($monitors[$i]['Sequence'] < $minSequence) )
    {
        $minSequence = $monitors[$i]['Sequence'];
    }
    if ( $monitors[$i]['Sequence'] > $maxSequence )
    {
        $maxSequence = $monitors[$i]['Sequence'];
    }
    $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
    $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    $monitors[$i]['ZoneCount'] = dbFetchOne( 'select count(Id) as ZoneCount from Zones where MonitorId = ?', 'ZoneCount', array($monitors[$i]['Id']) );
    if ( $monitors[$i]['Function'] != 'None' )
    {
        $cycleCount++;
        $scaleWidth = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        $scaleHeight = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
        if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
    }
    $seqIdList[] = $monitors[$i]['Id'];
    $displayMonitors[] = $monitors[$i];
}
$lastId = 0;
$seqIdUpList = array();
foreach ( $seqIdList as $seqId )
{
    if ( !empty($lastId) )
        $seqIdUpList[$seqId] = $lastId;
    else
        $seqIdUpList[$seqId] = $seqId;
    $lastId = $seqId;
}
$lastId = 0;
$seqIdDownList = array();
foreach ( array_reverse($seqIdList) as $seqId )
{
    if ( !empty($lastId) )
        $seqIdDownList[$seqId] = $lastId;
    else
        $seqIdDownList[$seqId] = $seqId;
    $lastId = $seqId;
}

$cycleWidth = $maxWidth;
$cycleHeight = $maxHeight;

$eventsView = ZM_WEB_EVENTS_VIEW;
$eventsWindow = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);

$eventCount = 0;
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    $eventCounts[$i]['total'] = 0;
}
$zoneCount = 0;
foreach( $displayMonitors as $monitor )
{
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
        $eventCounts[$i]['total'] += $monitor['EventCount'.$i];
    }
    $zoneCount += $monitor['ZoneCount'];
}

$seqUpFile = getSkinFile( 'graphics/seq-u.gif' );
$seqDownFile = getSkinFile( 'graphics/seq-d.gif' );

$versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'errorText':'';

xhtmlHeaders( __FILE__, $SLANG['Console'] );
?>
<body>
		<?php include("header.php"); ?>

    <form name="monitorForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?= $view ?>"/>
    <input type="hidden" name="action" value=""/>

    <div id="content" class="container-fluid">
			<div class="row">
				<div class="col-md-2"><?php include("sidebar.php"); ?></div>

				<div class="col-md-10">

	<div ng-controller="ConsoleController">
<div class="row">
<?php foreach( $displayMonitors as $monitor ) {
    if ( !$monitor['zmc'] )
        $dclass = "danger";
    else
    {
        if ( !$monitor['zma'] )
            $dclass = "warning";
        else
            $dclass = "info";
    }
?>
<div class="col-md-3">
	<div class="panel panel-<?= $dclass ?>">
		<div class="panel-heading">
			<input class="pull-right btn btn-default" type="checkbox" name="markMids[]" value="<?= $monitor['Id'] ?>" onclick="setButtonStates( this )"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/>
			<h2 class="text-left panel-title"><?= $monitor['Name'] ?> <small>(<?= $monitor['Id'] ?>)</small></h2>
		</div>

		<div class="panel-body center-block">
			<div>
			<?php
				$streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale ) );
				outputImageStill( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
			?>
			</div>

			<p><span ng-bind="Counts<?= $monitor['Id'] ?>"></span> recent events</p>
		</div>
	</div>
</div>
<?php } ?>
</div>
	</div>



			</div> <!-- End .col-md-10 -->


				</div> <!-- End .row -->
			</div> <!-- End #content .container-fluid -->
    </form>
		<?php include("footer.php"); ?>
</body>
</html>
