<?php
//
// ZoneMinder web console file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

$event_counts = array(
    array(
        "title" => $zmSlangEvents,
        "filter" => array(
            "terms" => array(
            )
        ),
    ),
    array(
        "title" => $zmSlangHour,
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 hour" ),
            )
        ),
    ),
    array(
        "title" => $zmSlangDay,
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 day" ),
            )
        ),
    ),
    array(
        "title" => $zmSlangWeek,
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-7 day" ),
            )
        ),
    ),
    array(
        "title" => $zmSlangMonth,
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "0" ),
                array( "cnj" => "and", "attr" => "DateTime", "op" => ">=", "val" => "-1 month" ),
            )
        ),
    ),
    array(
        "title" => $zmSlangArchived,
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "1" ),
            )
        ),
    ),
);

$running = daemonCheck();
$status = $running?$zmSlangRunning:$zmSlangStopped;

if ( !isset($cgroup) )
{
	$cgroup = 0;
}
if ( $group = dbFetchOne( "select * from Groups where Id = '$cgroup'" ) )
    $group_ids = array_flip(split( ',', $group['MonitorIds'] ));

if ( ZM_WEB_REFRESH_METHOD == "http" )
	header("Refresh: ".ZM_WEB_REFRESH_MAIN."; URL=$PHP_SELF" );
noCacheHeaders();

$db_now = strftime( STRF_FMT_DATETIME_DB );
$monitors = array();
$max_width = 0;
$max_height = 0;
$cycle_count = 0;
$min_sequence = 0;
$max_sequence = 1;
$seq_id_list = array();
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
for ( $i = 0; $i < count($monitors); $i++ )
{
	if ( !visibleMonitor( $monitors[$i]['Id'] ) )
	{
		continue;
	}
	if ( $group && !empty($group_ids) && !array_key_exists( $monitors[$i]['Id'], $group_ids ) )
	{
		continue;
	}
	$monitors[$i]['Show'] = true;
	if ( empty($min_sequence) || ($monitors[$i]['Sequence'] < $min_sequence) )
	{
		$min_sequence = $monitors[$i]['Sequence'];
	}
	if ( $monitors[$i]['Sequence'] > $max_sequence )
	{
		$max_sequence = $monitors[$i]['Sequence'];
	}
	$monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
	$monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
	$monitors[$i]['ZoneCount'] = dbFetchOne( "select count(Id) as ZoneCount from Zones where MonitorId = '".$monitors[$i]['Id']."'", "ZoneCount" );
    $counts = array();
    for ( $j = 0; $j < count($event_counts); $j++ )
    {
        $filter = addFilterTerm( $event_counts[$j]['filter'], count($event_counts[$j]['filter']['terms']), array( "cnj" => "and", "attr" => "MonitorId", "op" => "=", "val" => $monitors[$i]['Id'] ) );
        parseFilter( $filter );
        $counts[] = "count(if(1".$filter['sql'].",1,NULL)) as EventCount$j";
        $monitors[$i]['event_counts'][$j]['filter'] = $filter;
    }
	$sql = "select ".join($counts,", ")." from Events as E where MonitorId = '".$monitors[$i]['Id']."'";
	$counts = dbFetchOne( $sql );
	if ( $monitors[$i]['Function'] != 'None' )
	{
		$cycle_count++;
		$scale_width = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
		$scale_height = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
		if ( $max_width < $scale_width ) $max_width = $scale_width;
		if ( $max_height < $scale_height ) $max_height = $scale_height;
	}
	$monitors[$i] = array_merge( $monitors[$i], $counts );
	$seq_id_list[] = $monitors[$i]['Id'];
}
$last_id = 0;
$seq_id_u_list = array();
foreach ( $seq_id_list as $seq_id )
{
	if ( !empty($last_id) )
	{
		$seq_id_u_list[$seq_id] = $last_id;
	}
	$last_id = $seq_id;
}
$last_id = 0;
$seq_id_d_list = array();
foreach ( array_reverse($seq_id_list) as $seq_id )
{
	if ( !empty($last_id) )
	{
		$seq_id_d_list[$seq_id] = $last_id;
	}
	$last_id = $seq_id;
}

if ( $cycle_count )
{
	$montage_rows = intval((($cycle_count-1)/ZM_WEB_MONTAGE_MAX_COLS)+1);
	$montage_cols = intval(ceil($cycle_count/$montage_rows));
}
else
{
	$montage_rows = 0;
	$montage_cols = 0;
}
$montage_width = ZM_WEB_MONTAGE_WIDTH?ZM_WEB_MONTAGE_WIDTH:$max_width;
$montage_height = ZM_WEB_MONTAGE_HEIGHT?ZM_WEB_MONTAGE_HEIGHT:$max_height;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangConsole ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<link rel="shortcut icon" href="favicon.ico">
<link rel="icon" type="image/ico" href="favicon.ico">
<script type="text/javascript">
<?php
if ( ZM_WEB_RESIZE_CONSOLE )
{
?>
window.resizeTo( <?= $jws['console']['w'] ?>, <?= $jws['console']['h']+(25*(count($monitors)>6?count($monitors):6)) ?> );
<?php
}
?>
function newWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,status=no,width="+Width+",height="+Height);
}
function scrollWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,scrollbars,status=no,width="+Width+",height="+Height);
}
function filterWindow(Url,Name)
{   
	var Win = window.open(Url,Name,"resizable,scrollbars,status=no,width=<?= $jws['filter']['w'] ?>,height=<?= $jws['filter']['h'] ?>");
}   
function configureButton(form,name)
{
	var checked = false;
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				checked = true;
				break;
			}
		}
	}
	form.delete_btn.disabled = !checked;
}
function confirmDelete()
{
	return( confirm( 'Warning, deleting a monitor also deletes all events and database entries associated with it.\nAre you sure you wish to delete?' ) );
}
<?php
if ( ZM_WEB_REFRESH_METHOD == "javascript" )
{
?>
window.setTimeout( "window.location.replace('<?= $PHP_SELF ?>')", <?= (ZM_WEB_REFRESH_MAIN*1000) ?> );
<?php
}
?>
<?php
if ( ZM_CHECK_FOR_UPDATES && canEdit('System') && ZM_DYN_LAST_VERSION && ( verNum(ZM_VERSION) < verNum(ZM_DYN_LAST_VERSION) ) && ( verNum(ZM_DYN_CURR_VERSION) < verNum(ZM_DYN_LAST_VERSION) ) && ( ZM_DYN_NEXT_REMINDER < time() ) )
{
?>
newWindow( '<?= $PHP_SELF ?>?view=version', 'zmVersion', <?= $jws['version']['w'] ?>, <?= $jws['version']['h'] ?> );
<?php
}
elseif ( ZM_DYN_SHOW_DONATE_REMINDER )
{
	if ( canEdit('System') )
	{
		if ( ZM_DYN_DONATE_REMINDER_TIME > 0 )
		{
			if ( ZM_DYN_DONATE_REMINDER_TIME < time() )
			{
?>
newWindow( '<?= $PHP_SELF ?>?view=donate', 'zmDonate', <?= $jws['donate']['w'] ?>, <?= $jws['donate']['h'] ?> );
<?php
			}
		}
		else
		{
			$next_reminder = time() + 30*24*60*60;
			dbQuery( "update Config set Value = '".$next_reminder."' where Name = 'ZM_DYN_DONATE_REMINDER_TIME'" );
		}
	}
}
?>
</script>
</head>
<body scroll="auto">
<form name="monitor_form" method="get" action="<?= $PHP_SELF ?>" onSubmit="return(confirmDelete());">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="delete">
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr>
<td class="smallhead" align="left"><?= preg_match( '/%/', DATE_FMT_CONSOLE_LONG )?strftime( DATE_FMT_CONSOLE_LONG ):date( DATE_FMT_CONSOLE_LONG ) ?></td>
<td class="bighead" align="center"><strong><a href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a> <?= $zmSlangConsole ?> - <?= makeLink( "javascript: newWindow( '".$PHP_SELF."?view=state', 'zmState', ".$jws['state']['w'].", ".$jws['state']['h']." );", $status, canEdit( 'System' ) ) ?> - <?= makeLink( "javascript: newWindow( '$PHP_SELF?view=version', 'zmVersion', ".$jws['version']['w'].", ".$jws['version']['h']." );", "v".ZM_VERSION, canEdit( 'System' ) ) ?></strong></td>
<td class="smallhead" align="right"><?= $zmSlangLoad ?>: <?= getLoad() ?> / <?= $zmSlangDisk ?>: <?= getDiskPercent() ?>%</td>
</tr>
<tr>
<td class="smallhead" align="left"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td class="smallhead" align="left">
<tr>
<td class="smallhead" align="left">
<?php
if ( canView( 'System' ) )
{
?>
<a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=groups', 'zmGroups', <?= $jws['groups']['w'] ?>, <?= $jws['groups']['h'] ?> );"><?= sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) ).($group?' ('.$group['Name'].')':'') ?></a>
<?php
}
else
{
?>
<?= sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) ) ?>
<?php
}
?>
</td>
<?php
if ( ZM_OPT_X10 && canView('Devices' ) )
{
?>
<td class="smallhead" align="right"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=devices', 'zmDevices', <?= $jws['devices']['w'] ?>, <?= $jws['devices']['h'] ?> );"><?= $zmSlangDevices ?></a></td>
<?php
}
?>
</tr>
</table>
</td>
<?php
if ( ZM_OPT_USE_AUTH )
{
?>
<td class="smallhead" align="center"><?= $zmSlangLoggedInAs ?> <?= makeLink( "javascript: newWindow( '$PHP_SELF?view=logout', 'zmLogout', ".$jws['logout']['w'].", ".$jws['logout']['h'].");", $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?>, <?= strtolower( $zmSlangConfiguredFor ) ?>
<?php
}
else
{
?>
<td class="smallhead" align="center"><?= $zmSlangConfiguredFor ?>
<?php
}
?>
&nbsp;<?= makeLink( "javascript: newWindow( '$PHP_SELF?view=bandwidth', 'zmBandwidth', ".$jws['bandwidth']['w'].", ".$jws['bandwidth']['h']." );", $bw_array[$bandwidth], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?= $zmSlangBandwidth ?></td>
<td class="smallhead" align="right"><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td class="smallhead" align="left">
<?php
if ( canView( 'Stream' ) && $cycle_count > 1 )
{
?>
<?= makeLink( "javascript: newWindow( '$PHP_SELF?view=cycle&group=$cgroup', 'zmCycle$cgroup', ".($montage_width+$jws['cycle']['w']).", ".($montage_height+$jws['cycle']['h'])." );", $zmSlangCycle, $running ) ?>&nbsp;/&nbsp;<?= makeLink( "javascript: newWindow( '$PHP_SELF?view=montage&group=$cgroup', 'zmMontage$cgroup', ".(($montage_cols*$montage_width)+$jws['montage']['w']).", ".(($montage_rows*((ZM_WEB_COMPACT_MONTAGE?4:40)+$montage_height))+$jws['montage']['h'])." );", $zmSlangMontage, $running ) ?>
<?php
}
else
{
?>
&nbsp;
<?php
}
?>
</td><td align="right" class="smallhead"><?php if ( canView('System') ) { ?><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=options', 'zmOptions', <?= $jws['options']['w'] ?>, <?= $jws['options']['h'] ?> );"><?= $zmSlangOptions ?></a><?php } else { ?>&nbsp;<?php } ?></td></tr></table></td>
</tr>
</table>
<table align="center" border="0" cellspacing="2" cellpadding="2" width="96%">
<tr><td align="left" class="smallhead"><?= $zmSlangId ?></td>
<td align="left" class="smallhead"><?= $zmSlangName ?></td>
<td align="left" class="smallhead"><?= $zmSlangFunction ?></td>
<td align="left" class="smallhead"><?= $zmSlangSource ?></td>
<?php
for ( $i = 0; $i < count($event_counts); $i++ )
{
?>
<td align="right" class="smallhead"><?= $event_counts[$i]['title'] ?></td>
<?php
}
?>
<td align="right" class="smallhead"><?= $zmSlangZones ?></td>
<?php
if ( canEdit('Monitors') )
{
?>
<td align="center" class="smallhead"><?= $zmSlangOrder ?></td>
<?php
}
?>
<td align="center" class="smallhead"><?= $zmSlangMark ?></td>
</tr>
<?php
$events_view = ZM_WEB_EVENTS_VIEW;
$events_window = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);

$event_count = 0;
for ( $i = 0; $i < count($event_counts); $i++ )
{
    $event_counts[$i]['total'] = 0;
}
$zone_count = 0;
foreach( $monitors as $monitor )
{
    if ( empty($monitor['Show']) )
        continue;
    for ( $i = 0; $i < count($event_counts); $i++ )
    {
	    $event_counts[$i]['total'] += $monitor['EventCount'.$i];
    }
	$zone_count += $monitor['ZoneCount'];
?>
<tr>
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor".$monitor['Id']."', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", $monitor['Id'].'.', canView( 'Monitors' ) ) ?></td>
<?php
	if ( !$monitor['zmc'] )
	{
		$dclass = "redtext";
	}
	else
	{
		if ( !$monitor['zma'] )
		{
			$dclass = "ambtext";
		}
		else
		{
			$dclass = "gretext";
		}
	}
	if ( $monitor['Function'] == 'None' )
	{
		$fclass = "redtext";
	}
	elseif ( $monitor['Function'] == 'Monitor' )
	{
		$fclass = "ambtext";
	}
	else
	{
		$fclass = "gretext";
	}
	if ( !$monitor['Enabled'] )
	{
		$fclass .= "em";
	}
	$scale = max( reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=watch&mid=".$monitor['Id']."', 'zmWatch".$monitor['Id']."', ".(reScale( $monitor['Width'], $scale )+$jws['watch']['w']).", ".(reScale( $monitor['Height'], $scale )+$jws['watch']['h'])." );", $monitor['Name'], $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=function&mid=".$monitor['Id']."', 'zmFunction', ".$jws['function']['w'].", ".$jws['function']['h']." );", "<span class=\"$fclass\">".$monitor['Function']."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php if ( $monitor['Type'] == "Local" ) { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor".$monitor['Id']."', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">".$monitor['Device']." (".$monitor['Channel'].")</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "Remote" ) { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor".$monitor['Id']."', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">".preg_replace( '/^.*@/', '', $monitor['Host'] )."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } elseif ( $monitor['Type'] == "File" ) { ?>
<td align="left" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=monitor&mid=".$monitor['Id']."', 'zmMonitor".$monitor['Id']."', ".$jws['monitor']['w'].", ".$jws['monitor']['h']." );", "<span class=\"$dclass\">".preg_replace( '/^.*\//', '', $monitor['Path'] )."</span>", canEdit( 'Monitors' ) ) ?></td>
<?php } else { ?>
<td align="left" class="text">&nbsp;</td>
<?php } ?>
<?php
for ( $i = 0; $i < count($event_counts); $i++ )
{
?>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=$events_view&page=1".$monitor['event_counts'][$i]['filter']['query']."', '$events_window', ".$jws[$events_view]['w'].", ".$jws[$events_view]['h']." );", $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
<?php
}
?>
<td align="right" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=zones&mid=".$monitor['Id']."', 'zmZones', ".($monitor['Width']+$jws['zones']['w']).", ".($monitor['Height']+$jws['zones']['h'])." );", $monitor['ZoneCount'], canView( 'Monitors' ) ) ?></td>
<?php
if ( canEdit('Monitors') )
{
?>
<td align="center" class="text"><?= makeLink( "$PHP_SELF?view=$view&action=sequence&mid=".$monitor['Id']."&smid=".$seq_id_u_list[$monitor['Id']], '<img src="graphics/seq-u.gif" alt="" width="12" height="11" border="0">', $monitor['Sequence']>$min_sequence ) ?><?= makeLink( "$PHP_SELF?view=$view&action=sequence&mid=".$monitor['Id']."&smid=".$seq_id_d_list[$monitor['Id']], '<img src="graphics/seq-d.gif" alt="" width="12" height="11" border="0">', $monitor['Sequence']<$max_sequence ) ?></td>
<?php
}
?>
<td align="center" class="text"><input type="checkbox" name="mark_mids[]" value="<?= $monitor['Id'] ?>" onClick="configureButton( document.monitor_form, 'mark_mids' );"<?php if ( !canEdit( 'Monitors' ) || $user['MonitorIds'] ) {?> disabled<?php } ?>></td>
</tr>
<?php
}
?>
<tr>
<td colspan="4" align="center">
<table align="center" border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="center"><input type="button" value="<?= $zmSlangRefresh ?>" class="form" onClick="javascript: location.reload(true);"></td>
<td align="center"><input type="button" value="<?= $zmSlangAddNewMonitor ?>" class="form" onClick="javascript: newWindow( '<?= $PHP_SELF ?>?view=monitor', 'zmMonitor0', <?= $jws['monitor']['w'] ?>, <?= $jws['monitor']['h'] ?>);"<?php if ( !canEdit( 'Monitors' ) || $user['MonitorIds'] ) {?> disabled<?php } ?>></td>
<td align="center"><input type="button" value="<?= $zmSlangFilters ?>" class="form" onClick="javascript: scrollWindow( '<?= $PHP_SELF ?>?view=filter&filter[terms][0][attr]=DateTime&filter[terms][0][op]=%3c&filter[terms][0][val]=now', 'zmFilter', <?= $jws['filter']['w'] ?>, <?= $jws['filter']['h'] ?> );"<?php if ( !canView( 'Events' ) ) {?> disabled<?php } ?>></td>
</tr>
</table>
</td>
<?php
for ( $i = 0; $i < count($event_counts); $i++ )
{
    parseFilter( $event_counts[$i]['filter'] );
?>
<td align="right" class="text"><?= makeLink( "javascript: scrollWindow( '$PHP_SELF?view=$events_view&page=1".$event_counts[$i]['filter']['query']."', '$events_window', ".$jws[$events_view]['w'].", ".$jws[$events_view]['h']." );", $event_counts[$i]['total'], canView( 'Events' ) ) ?></td>
<?php
}
?>
<td align="right" class="text"><?= $zone_count ?></td>
<td align="center" colspan="<?= canEdit('Monitors')?2:1 ?>"><input type="submit" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" disabled></td>
</tr>
</table>
</form>
</body>
</html>
