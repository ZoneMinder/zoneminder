<?php
//
// ZoneMinder web events view file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}
if ( !isset($sort_parms) )
{
	$sort_parms = "";
}
if ( !isset($sort_field) )
{
	$sort_field = "Time";
	$sort_asc = false;
}
switch( $sort_field )
{
	case 'Id' :
		$sort_column = "E.Id";
		break;
	case 'Name' :
		$sort_column = "E.Name";
		break;
	case 'Time' :
		$sort_column = "E.StartTime";
		break;
	case 'Secs' :
		$sort_column = "E.Length";
		break;
	case 'Frames' :
		$sort_column = "E.Frames";
		break;
	case 'AlarmFrames' :
		$sort_column = "E.AlarmFrames";
		break;
	case 'TotScore' :
		$sort_column = "E.TotScore";
		break;
	case 'AvgScore' :
		$sort_column = "E.AvgScore";
		break;
	case 'MaxScore' :
		$sort_column = "E.MaxScore";
		break;
	default:
		$sort_column = "E.StartTime";
		break;
}
$sort_order = $sort_asc?"asc":"desc";
if ( !$sort_asc ) $sort_asc = 0;

$result = mysql_query( "select * from Monitors where Id = '$mid'" );
if ( !$result )
	die( mysql_error() );
$monitor = mysql_fetch_assoc( $result );

$count_sql = "select count(E.Id) as EventCount from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where M.Id = '$mid'";
$events_sql = "select E.Id,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived,E.LearnState from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where M.Id = '$mid'";
$filter_query = ''; 
$filter_sql = '';
$filter_fields = '';
if ( $trms )
{
	$filter_query .= "&trms=$trms";
	$filter_fields .= '<input type="hidden" name="trms" value="'.$trms.'">'."\n";
}
for ( $i = 1; $i <= $trms; $i++ )
{
	$conjunction_name = "cnj$i";
	$obracket_name = "obr$i";
	$cbracket_name = "cbr$i";
	$attr_name = "attr$i";
	$op_name = "op$i";
	$value_name = "val$i";
	if ( isset($$conjunction_name) )
	{
		$filter_query .= "&$conjunction_name=".$$conjunction_name;
		$filter_sql .= " ".$$conjunction_name." ";
		$filter_fields .= '<input type="hidden" name="'.$conjunction_name.'" value="'.$$conjunction_name.'">'."\n";
	}
	if ( isset($$obracket_name) )
	{
		$filter_query .= "&$obracket_name=".$$obracket_name;
		$filter_sql .= str_repeat( "(", $$obracket_name );
		$filter_fields .= '<input type="hidden" name="'.$obracket_name.'" value="'.$$obracket_name.'">'."\n";
	}
	if ( isset($$attr_name) )
	{
		$filter_query .= "&$attr_name=".$$attr_name;
		$filter_fields .= '<input type="hidden" name="'.$attr_name.'" value="'.$$attr_name.'">'."\n";
		switch ( $$attr_name )
		{
			case 'DateTime':
				$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
				$filter_sql .= "E.StartTime ".$$op_name." '$dt_val'";
				$filter_query .= "&$op_name=".urlencode($$op_name);
				$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
				break;
			case 'Date':
				$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
				$filter_sql .= "to_days( E.StartTime ) ".$$op_name." to_days( '$dt_val' )";
				$filter_query .= "&$op_name=".urlencode($$op_name);
				$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
				break;
			case 'Time':
				$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
				$filter_sql .= "extract( hour_second from E.StartTime ) ".$$op_name." extract( hour_second from '$dt_val' )";
				$filter_query .= "&$op_name=".urlencode($$op_name);
				$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
				break;
			case 'Weekday':
				$dt_val = strftime( "%Y-%m-%d %H:%M:%S", strtotime( $$value_name ) );
				$filter_sql .= "weekday( E.StartTime ) ".$$op_name." weekday( '$dt_val' )";
				$filter_query .= "&$op_name=".urlencode($$op_name);
				$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
				break;
			case 'Length':
			case 'Frames':
			case 'AlarmFrames':
			case 'TotScore':
			case 'AvgScore':
			case 'MaxScore':
				$filter_sql .= "E.".$$attr_name." ".$$op_name." ".$$value_name;
				$filter_query .= "&$op_name=".urlencode($$op_name);
				$filter_fields .= '<input type="hidden" name="'.$op_name.'" value="'.$$op_name.'">'."\n";
				break;
			case 'Archived':
				$filter_sql .= "E.Archived = ".$$value_name;
				break;
		}
		$filter_query .= "&$value_name=".urlencode($$value_name);
		$filter_fields .= '<input type="hidden" name="'.$value_name.'" value="'.$$value_name.'">'."\n";
	}
	if ( isset($$cbracket_name) )
	{
		$filter_query .= "&$cbracket_name=".$$cbracket_name;
		$filter_sql .= str_repeat( ")", $$cbracket_name );
		$filter_fields .= '<input type="hidden" name="'.$cbracket_name.'" value="'.$$cbracket_name.'">'."\n";
	}
}
if ( $filter_sql )
{
	$count_sql .= " and ( $filter_sql )";
	$events_sql .= " and ( $filter_sql )";
}
$events_sql .= " order by $sort_column $sort_order";
if ( $page )
{
	$events_sql .= " limit ".(($page-1)*EVENT_HEADER_LINES).", ".EVENT_HEADER_LINES;
}

?>
<html>
<head>
<title>ZM - <?= $monitor['Name'] ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_styles.css" type="text/css">
<script language="JavaScript">
function newWindow(Url,Name,Width,Height)
{
   	var Name = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name)
{
	var Name = window.open(Url,Name,"resizable,width=<?= $monitor['Width']+$jws['event']['w'] ?>,height=<?= $monitor['Height']+$jws['event']['h'] ?>");
}
function filterWindow(Url,Name)
{
	var Name = window.open(Url,Name,"resizable,scrollbars,width=<?= $jws['filter']['w'] ?>,height=<?= $jws['filter']['h'] ?>");
}
function closeWindow()
{
	window.close();
	// This is a hack. The only way to close an existing window is to try and open it!
	var filterWindow = window.open( "<?= $PHP_SELF ?>?view=none", 'zmFilter<?= $monitor['Id'] ?>', 'width=1,height=1' );
	filterWindow.close();
}
function checkAll(form,name)
{
	for (var i = 0; i < form.elements.length; i++)
		if (form.elements[i].name.indexOf(name) == 0)
			form.elements[i].checked = 1;
	form.delete_btn.disabled = false;
<?php if ( LEARN_MODE ) { ?>
	form.learn_btn.disabled = false;
	form.learn_state.disabled = false;
<?php } ?>
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
<?php if ( LEARN_MODE ) { ?>
	form.learn_btn.disabled = !checked;
	form.learn_state.disabled = !checked;
<?php } ?>
}
window.focus();
<?php
if ( isset($filter) )
{
?>
//opener.location.reload(true);
filterWindow( '<?= $PHP_SELF ?>?view=filter&mid=<?= $mid ?>&page=<?= $page ?><?= $filter_query ?>', 'zmFilter<?= $monitor['Id'] ?>' );
location.replace( '<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=<?= $page ?><?= $filter_query ?>' );
</script>
</head>
</html>
<?php
}
else
{
	if ( !($result = mysql_query( $count_sql )) )
		die( mysql_error() );
	$row = mysql_fetch_assoc( $result );
	$n_events = $row['EventCount'];
?>
</script>
</head>
<body>
<form name="event_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="mid" value="<?= $mid ?>">
<input type="hidden" name="page" value="<?= $page ?>">
<?php if ( $filter_fields ) echo $filter_fields ?>
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="text" width="20%"><b><?= $monitor['Name'] ?> - <?= sprintf( $zmClangEventCount, $n_events, zmVlang( $zmVlangEvent, $n_events ) ) ?></b></td>
<?php
	$pages = (int)ceil($n_events/EVENT_HEADER_LINES);
	if ( $pages <= 1 )
	{
?>
<td align="center" class="text" width="60%">&nbsp;</td>
<td align="center" class="text" width="10%">&nbsp;</td>
<?php
	}
	else
	{
		if ( $page )
		{
			$max_shortcuts = 5;
?>
<td align="center" class="text" width="34%">
<?php
			if ( $page < 0 )
				$page = 1;
			if ( $page > $pages )
				$page = $pages;

			if ( $page > 1 )
			{
				$new_pages = array();
				$pages_used = array();
				$lo_exp = max(2,log($page-1)/log($max_shortcuts));
				for ( $i = 0; $i < $max_shortcuts; $i++ )
				{
					$new_page = round($page-pow($lo_exp,$i));
					if ( isset($pages_used[$new_page]) )
						continue;
					if ( $new_page <= 1 )
						break;
					$pages_used[$new_page] = true;
					array_unshift( $new_pages, $new_page );
				}
				if ( !isset($pages_used[1]) )
					array_unshift( $new_pages, 1 );

				foreach ( $new_pages as $new_page )
				{
?>
<a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=<?= $new_page ?>&<?= $filter_query ?><?= $sort_parms ?>&sort_field=<?= $sort_field ?>&sort_asc=<?= $sort_asc ?>"><?= $new_page ?></a>&nbsp;
<?php
				}
			}
?>
-&nbsp;<?= $page ?>&nbsp;-
<?php
			if ( $page < $pages )
			{
				$new_pages = array();
				$pages_used = array();
				$hi_exp = max(2,log($pages-$page)/log($max_shortcuts));
				for ( $i = 0; $i < $max_shortcuts; $i++ )
				{
					$new_page = round($page+pow($hi_exp,$i));
					if ( isset($pages_used[$new_page]) )
						continue;
					if ( $new_page > $pages )
						break;
					$pages_used[$new_page] = true;
					array_push( $new_pages, $new_page );
				}
				if ( !isset($pages_used[$pages]) )
					array_push( $new_pages, $pages );

				foreach ( $new_pages as $new_page )
				{
?>
&nbsp;<a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=<?= $new_page ?>&<?= $filter_query ?><?= $sort_parms ?>&sort_field=<?= $sort_field ?>&sort_asc=<?= $sort_asc ?>"><?= $new_page ?></a>
<?php
				}
			}
?>
</td>
<td align="right" class="text" width="10%"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=0&<?= $filter_query ?><?= $sort_parms ?>&sort_field=<?= $sort_field ?>&sort_asc=<?= $sort_asc ?>"><?= $zmSlangViewAll ?></a></td>
<?php
		}
		else
		{
?>
<td align="center" class="text" width="60%">&nbsp;</td>
<td align="center" class="text" width="10%"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=<?= $sort_field ?>&sort_asc=<?= $sort_asc ?>"><?= $zmSlangViewPaged ?></a></td>
<?php
		}
	}
?>
<td align="right" class="text" width="10%"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr><td colspan="4" class="text">&nbsp;</td></tr>
<tr>
<td align="left" class="text"><a href="javascript: location.reload(true);"><?= $zmSlangRefresh ?></td>
<td colspan="2" align="right" class="text"><a href="javascript: filterWindow( '<?= $PHP_SELF ?>?view=filter&mid=<?= $mid ?>&page=<?= $page ?><?= $filter_query ?>', 'zmFilter<?= $monitor['Id'] ?>' );"><?= $zmSlangShowFilterWindow ?></a></td>
<td align="right" class="text"><?php if ( canEdit( 'Events' ) ) { ?><a href="javascript: checkAll( document.event_form, 'mark_eids' );"><?= $zmSlangCheckAll ?></a><?php } else { ?>&nbsp;<?php } ?></td>
</tr>
<tr><td colspan="4" class="text">&nbsp;</td></tr>
<tr><td colspan="4"><table border="0" cellspacing="1" cellpadding="0" width="100%" bgcolor="#7F7FB2">
<?php
	flush();
	$count = 0;
	if ( !($result = mysql_query( $events_sql )) )
		die( mysql_error() );
	while( $event = mysql_fetch_assoc( $result ) )
	{
		if ( ($count++%EVENT_HEADER_LINES) == 0 )
		{
?>
<tr align="center" bgcolor="#FFFFFF">
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>"><?= $zmSlangId ?><?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=Name&sort_asc=<?= $sort_field == 'Name'?!$sort_asc:0 ?>"><?= $zmSlangName ?><?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=Time&sort_asc=<?= $sort_field == 'Time'?!$sort_asc:0 ?>"><?= $zmSlangTime ?><?php if ( $sort_field == "Time" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=Secs&sort_asc=<?= $sort_field == 'Secs'?!$sort_asc:0 ?>"><?= $zmSlangDuration ?><?php if ( $sort_field == "Secs" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=Frames&sort_asc=<?= $sort_field == 'Frames'?!$sort_asc:0 ?>"><?= $zmSlangFrames ?><?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=AlarmFrames&sort_asc=<?= $sort_field == 'AlarmFrames'?!$sort_asc:0 ?>"><?= $zmSlangAlarmBrFrames ?><?php if ( $sort_field == "AlarmFrames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=TotScore&sort_asc=<?= $sort_field == 'TotScore'?!$sort_asc:0 ?>"><?= $zmSlangTotalBrScore ?><?php if ( $sort_field == "TotScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=AvgScore&sort_asc=<?= $sort_field == 'AvgScore'?!$sort_asc:0 ?>"><?= $zmSlangAvgBrScore ?><?php if ( $sort_field == "AvgScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=events&mid=<?= $mid ?>&page=1&<?= $filter_query ?><?= $sort_parms ?>&sort_field=MaxScore&sort_asc=<?= $sort_field == 'MaxScore'?!$sort_asc:0 ?>"><?= $zmSlangMaxBrScore ?><?php if ( $sort_field == "MaxScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text">Mark</td>
</tr>
<?php
		}
		if ( $event['LearnState'] == '+' )
			$bgcolor = "#98FB98";
		elseif ( $event['LearnState'] == '-' )
			$bgcolor = "#FFC0CB";
		else
			unset( $bgcolor );
?>
<tr<?= ' bgcolor="'.(isset($bgcolor)?$bgcolor:"#FFFFFF").'"' ?> >
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>&page=1', 'zmEvent' );"><?= $event['Id'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>&page=1', 'zmEvent' );"><?= $event['Name'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
<td align="center" class="text"><?= strftime( "%m/%d %H:%M:%S", strtotime($event['StartTime']) ) ?></td>
<td align="center" class="text"><?= $event['Length'] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frames&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>', 'zmFrames', <?= $jws['frames']['w'] ?>, <?= $jws['frames']['h'] ?> );"><?= $event['Frames'] ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frames&mid=<?= $mid ?>&eid=<?= $event['Id'] ?>', 'zmFrames', <?= $jws['frames']['w'] ?>, <?= $jws['frames']['h'] ?> );"><?= $event['AlarmFrames'] ?></a></td>
<td align="center" class="text"><?= $event['TotScore'] ?></td>
<td align="center" class="text"><?= $event['AvgScore'] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $event['Id'] ?>&fid=0', 'zmImage', <?= $monitor['Width']+$jws['image']['w'] ?>, <?= $monitor['Height']+$jws['image']['h'] ?> );"><?= $event['MaxScore'] ?></a></td>
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $event['Id'] ?>" onClick="configureButton( document.event_form, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
	}
?>
</table></td></tr>
</table></td>
</tr>
<tr><td align="right"><?php if ( LEARN_MODE ) { ?><select name="learn_state" class="form" disabled><option value=""><?= $zmSlangIgnore ?></option><option value="-"><?= $zmSlangExclude ?></option><option value="+"><?= $zmSlangInclude ?></option></select>&nbsp;&nbsp;<input type="button" name="learn_btn" value="<?= $zmSlangSetLearnPrefs ?>" class="form" onClick="document.event_form.action.value = 'learn'; document.event_form.submit();" disabled>&nbsp;&nbsp;<?php } ?><input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" onClick="document.event_form.action.value = 'delete'; document.event_form.submit();" disabled></td></tr>
</table></center>
</form>
</body>
</html>
<?php
}
?>
