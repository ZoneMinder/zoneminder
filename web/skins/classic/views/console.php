<?php
xhtmlHeaders( __FILE__, translate('Console') );
?>
<body>
    <form name="monitorForm" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="action" value=""/>

    <?php include("skins/$skin/views/header.php") ?>

    <div class="container-fluid">
      <table class="table table-striped table-hover table-condensed">
        <thead>
          <tr>
            <th class="colName"><?php echo translate('Name') ?></th>
            <th class="colFunction"><?php echo translate('Function') ?></th>
<?php if ( count($servers) ) { ?>
            <th class="colServer"><?php echo translate('Server') ?></th>
<?php } ?>
            <th class="colSource"><?php echo translate('Source') ?></th>
<?php
for ( $i = 0; $i < count($eventCounts); $i++ )
{
?>
            <th class="colEvents"><?php echo $eventCounts[$i]['title'] ?></th>
<?php
}
?>
            <th class="colZones"><?php echo translate('Zones') ?></th>
<?php
if ( canEdit('Monitors') )
{
?>
            <th class="colOrder"><?php echo translate('Order') ?></th>
<?php
}
?>
            <th class="colMark"><?php echo translate('Mark') ?></th>
          </tr>
        </thead>
        <tfoot>
          <tr>
            <td class="colLeftButtons" colspan="<?php echo count($servers) ? 4 : 3 ?>">
              <input type="button" class="btn btn-primary" value="<?php echo translate('AddNewMonitor'); ?>" onclick="createPopup( '?view=monitor', 'zmMonitor0', 'monitor' ); return( false );"></input>
            </td>
<?php
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    parseFilter( $eventCounts[$i]['filter'] );
?>
            <td class="colEvents"><?php echo makePopupLink( '?view='.$eventsView.'&amp;page=1'.$eventCounts[$i]['filter']['query'], $eventsWindow, $eventsView, $eventCounts[$i]['total'], canView( 'Events' ) ) ?></td>
<?php
}
?>
            <td class="colZones"><?php echo $zoneCount ?></td>
            <td><input class="btn btn-primary" type="button" name="editBtn" value="<?php echo translate('Edit') ?>" onclick="editMonitor( this )" disabled="disabled"/></td>
            <td><input class="btn btn-danger" type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteMonitor( this )" disabled="disabled"/></td>
          </tr>
        </tfoot>
        <tbody>
<?php
foreach( $displayMonitors as $monitor )
{
?>
          <tr>
<?php
    if ( !$monitor['zmc'] )
        $dclass = "errorText";
    else
    {
    // https://github.com/ZoneMinder/ZoneMinder/issues/1082
        if ( !$monitor['zma'] && $monitor['Function']!='Monitor' )
            $dclass = "warnText";
        else
            $dclass = "infoText";
    }
    if ( $monitor['Function'] == 'None' )
        $fclass = "errorText";
    //elseif ( $monitor['Function'] == 'Monitor' )
     //   $fclass = "warnText";
    else
        $fclass = "infoText";
    if ( !$monitor['Enabled'] )
        $fclass .= " disabledText";
    $scale = max( reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
            <td class="colName"><?php echo makePopupLink( '?view=watch&amp;mid='.$monitor['Id'], 'zmWatch'.$monitor['Id'], array( 'watch', reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) ), $monitor['Name'], $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
            <td class="colFunction"><?php echo makePopupLink( '?view=function&amp;mid='.$monitor['Id'], 'zmFunction', 'function', '<span class="'.$fclass.'">'.translate('Fn'.$monitor['Function']).( empty($monitor['Enabled']) ? ', disabled' : '' ) .'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php if ( count($servers) ) { ?>
            <td class="colServer"><?php 
$Server = new Server( $monitor['ServerId'] );
echo $Server->Name();
 ?></td>
<?php } ?>
<?php if ( $monitor['Type'] == "Local" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$monitor['Device'].' ('.$monitor['Channel'].')</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "Remote" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*@/', '', $monitor['Host'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "File" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "Ffmpeg" || $monitor['Type'] == "Libvlc" ) {
    $domain = parse_url( $monitor['Path'], PHP_URL_HOST );
    $shortpath = $domain ? $domain : preg_replace( '/^.*\//', '', $monitor['Path'] );
    if ( $shortpath == '' ) {
        $shortpath = 'Monitor ' . $monitor['Id'];
    }
?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$shortpath.'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "cURL" ) { ?>
            <td class="colSource"><?php echo makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
<?php } else { ?>
            <td class="colSource">&nbsp;</td>
<?php } ?>
<?php
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
?>
            <td class="colEvents"><?php echo makePopupLink( '?view='.$eventsView.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['query'], $eventsWindow, $eventsView, $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
<?php
    }
?>
            <td class="colZones"><?php echo makePopupLink( '?view=zones&amp;mid='.$monitor['Id'], 'zmZones', array( 'zones', $monitor['Width'], $monitor['Height'] ), $monitor['ZoneCount'], canView( 'Monitors' ) ) ?></td>
<?php
    if ( canEdit('Monitors') )
    {
?>
            <td class="colOrder"><?php echo makeLink( '?view='.$view.'&amp;action=sequence&amp;mid='.$monitor['Id'].'&amp;smid='.$seqIdUpList[$monitor['Id']], '<img src="'.$seqUpFile.'" alt="Up"/>', $monitor['Sequence']>$minSequence ) ?><?php echo makeLink( '?view='.$view.'&amp;action=sequence&amp;mid='.$monitor['Id'].'&amp;smid='.$seqIdDownList[$monitor['Id']], '<img src="'.$seqDownFile.'" alt="Down"/>', $monitor['Sequence']<$maxSequence ) ?></td>
<?php
    }
?>
            <td class="colMark"><input type="checkbox" name="markMids[]" value="<?php echo $monitor['Id'] ?>" onclick="setButtonStates( this )"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
          </tr>
<?php
}
?>
        </tbody>
      </table>
    </div>

<div id="footer">


<div class="pull-left">
<?php echo makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?php echo translate('BandwidthHead') ?>
</div>

<div class="pull-right">
	<?php echo makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="'.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit( 'System' ) ) ?>
</div>
<ul class="list-inline">
	<li><?php echo translate('Load') ?>: <?php echo getLoad() ?></li>
	<li><?php echo translate('Disk') ?>: <?php echo getDiskPercent() ?>%</li>
</ul>
</div> <!-- End .footer -->

    </form>
<?php include("skins/$skin/views/state.php") ?>
</body>
</html>
