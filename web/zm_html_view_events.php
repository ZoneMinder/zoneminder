<?php
//
// ZoneMinder web events view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
	$view = "error";
	return;
}

$count_sql = "select count(E.Id) as EventCount from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
$events_sql = "select E.Id,E.MonitorId,M.Name As MonitorName,M.Width,M.Height,M.DefaultScale,E.Name,E.Cause,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived,E.LearnState from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
if ( $user['MonitorIds'] )
{
	$count_sql .= " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
	$events_sql .= " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
	$count_sql .= " 1";
	$events_sql .= " 1";
}

parseSort();
parseFilter();

if ( $filter_sql )
{
	$count_sql .= $filter_sql;
	$events_sql .= $filter_sql;
}
$events_sql .= " order by $sort_column $sort_order";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvents ?></title>
<link rel="stylesheet" href="zm_html_styles.css" type="text/css">
<script type="text/javascript">
function newWindow(Url,Name,Width,Height)
{
   	var Win = window.open(Url,Name,"resizable,scrollbars,width="+Width+",height="+Height);
}
function eventWindow(Url,Name,Width,Height)
{
	var Win = window.open(Url,Name,"resizable,width="+Width+",height="+Height );
}
function filterWindow(Url,Name)
{
	var Win = window.open(Url,Name,"resizable,scrollbars,width=<?= $jws['filter']['w'] ?>,height=<?= $jws['filter']['h'] ?>");
}
function timelineWindow(Url,Name)
{
	var Win = window.open(Url,Name,"resizable,scrollbars,width=<?= $jws['timeline']['w'] ?>,height=<?= $jws['timeline']['h'] ?>");
}
function closeWindow()
{
	window.close();
	// This is a hack. The only way to close an existing window is to try and open it!
	var filterWindow = window.open( "<?= $PHP_SELF ?>?view=none", 'zmFilter', 'width=1,height=1' );
	filterWindow.close();
}
<?php
if ( isset($filter) )
{
?>
//opener.location.reload(true);
filterWindow( '<?= $PHP_SELF ?>?view=filter&page=<?= $page ?><?= $filter_query ?>', 'zmFilter' );
location.replace( '<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $page ?><?= $filter_query ?>' );
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
	mysql_free_result( $result );
	$n_events = $row['EventCount'];
	if ( !empty($limit) && $n_events > $limit )
	{
		$n_events = $limit;
	}
	$pages = (int)ceil($n_events/ZM_WEB_EVENTS_PER_PAGE);
	if ( $pages > 1 )
	{
		if ( $page )
		{
			if ( $page < 0 )
				$page = 1;
			if ( $page > $pages )
				$page = $pages;
		}
	}
	if ( $page )
	{
		$limit_start = (($page-1)*ZM_WEB_EVENTS_PER_PAGE);
		if ( empty( $limit ) )
		{
			$limit_amount = ZM_WEB_EVENTS_PER_PAGE;
		}
		else
		{
			$limit_left = $limit - $limit_start;
			$limit_amount = ($limit_left>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limit_left;
		}
		$events_sql .= " limit $limit_start, $limit_amount";
	}
	elseif ( !empty( $limit ) )
	{
		$events_sql .= " limit 0, $limit";
	}

	if ( !($result = mysql_query( $events_sql )) )
		die( mysql_error() );
	$max_width = 0;
	$max_height = 0;
	$archived = false;
	$unarchived = false;
	$events = array();
	while( $event = mysql_fetch_assoc( $result ) )
	{
		$events[] = $event;
		$scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
		$event_width = reScale( $event['Width'], $scale );
		$event_height = reScale( $event['Height'], $scale );
		if ( $max_width < $event_width ) $max_width = $event_width;
		if ( $max_height < $event_height ) $max_height = $event_height;
		if ( $event['Archived'] )
			$archived = true;
		else
			$unarchived = true;
	}
	mysql_free_result( $result );
?>
function toggleCheck(element,name)
{
	var form = element.form;
	var checked = element.checked;
	for (var i = 0; i < form.elements.length; i++)
		if (form.elements[i].name.indexOf(name) == 0)
			form.elements[i].checked = checked;
	form.view_btn.disabled = !checked;
	form.edit_btn.disabled = !checked;
	form.archive_btn.disabled = <?= $unarchived?"!checked":"true" ?>;
	form.unarchive_btn.disabled = <?= $archived?"!checked":"true" ?>;
	form.export_btn.disabled = !checked;
	form.delete_btn.disabled = !checked;
<?php if ( LEARN_MODE ) { ?>
	form.learn_btn.disabled = !checked;
	form.learn_state.disabled = !checked;
<?php } ?>
}
function configureButton(element,name)
{
	var form = element.form;
	var checked = element.checked;
	if ( !checked )
	{
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
	}
	if ( !element.checked )
		form.toggle_check.checked = false;
	form.view_btn.disabled = !checked;
	form.edit_btn.disabled = !checked;
	form.archive_btn.disabled = (!checked)||<?= $unarchived?"false":"true" ?>;
	form.unarchive_btn.disabled = (!checked)||<?= $archived?"false":"true" ?>;
	form.export_btn.disabled = !checked;
	form.delete_btn.disabled = !checked;
<?php if ( LEARN_MODE ) { ?>
	form.learn_btn.disabled = !checked;
	form.learn_state.disabled = !checked;
<?php } ?>
}
function deleteEvents( form, name )
{
	var count = 0;
	for (var i = 0; i < form.elements.length; i++)
	{
		if (form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				count++;
				break;
			}
		}
	}
	if ( count > 0 )
	{
		if ( confirm( "<?= $zmSlangConfirmDeleteEvents ?>" ) )
		{
			form.action.value = 'delete';
			form.submit();
		}
	}
}
function editEvents( form, name )
{
	var eids = new Array();
	for (var i = 0; i < form.elements.length; i++)
	{
		if (form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				eids[eids.length] = 'eids[]='+form.elements[i].value;
			}
		}
	}
	var Win = newWindow( '<?= $PHP_SELF ?>?view=eventdetail&'+eids.join( '&' ), 'zmEventDetail', <?= $jws['eventdetail']['w'] ?>, <?= $jws['eventdetail']['h'] ?> );
}
function exportEvents( form, name )
{
	var eids = new Array();
	for (var i = 0; i < form.elements.length; i++)
	{
		if (form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				eids[eids.length] = 'eids[]='+form.elements[i].value;
			}
		}
	}
	var Win = newWindow( '<?= $PHP_SELF ?>?view=export&'+eids.join( '&' ), 'zmExport', <?= $jws['export']['w'] ?>, <?= $jws['export']['h'] ?> );
}
function viewEvents( form, name )
{
	var events = new Array();
	for (var i = 0; i < form.elements.length; i++)
	{
		if ( form.elements[i].name.indexOf(name) == 0)
		{
			if ( form.elements[i].checked )
			{
				events[events.length] = form.elements[i].value;
			}
		}
	}
	if ( events.length > 0 )
	{
		eventWindow( '<?= $PHP_SELF ?>?view=event&eid='+events[0]+'&trms=1&attr1=Id&op1=%3D%5B%5D&val1='+events.join('%2C')+'<?= $sort_query ?>&page=1&play=1', 'zmEvent', <?= $max_width+$jws['event']['w']  ?>, <?= $max_height+$jws['event']['h'] ?> );
	}
}
</script>
</head>
<body>
<form name="event_form" method="post" action="<?= $PHP_SELF ?>">
<input type="hidden" name="view" value="<?= $view ?>">
<input type="hidden" name="action" value="">
<input type="hidden" name="page" value="<?= $page ?>">
<?= $filter_fields ?>
<input type="hidden" name="sort_field" value="<?= $sort_field ?>">
<input type="hidden" name="sort_asc" value="<?= $sort_asc ?>">
<input type="hidden" name="limit" value="<?= $limit ?>">
<center><table width="96%" align="center" border="0" cellspacing="1" cellpadding="0">
<tr>
<td valign="top"><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
<td align="left" class="text" width="20%"><b><?= sprintf( $zmClangEventCount, $n_events, zmVlang( $zmVlangEvent, $n_events ) ) ?></b></td>
<?php
	$pages = (int)ceil($n_events/ZM_WEB_EVENTS_PER_PAGE);
	if ( $pages <= 1 )
	{
?>
<td align="center" class="text" width="40%">&nbsp;</td>
<td align="center" class="text" width="25%">&nbsp;</td>
<?php
	}
	else
	{
		if ( $page )
		{
			$max_shortcuts = 5;
?>
<td align="center" class="text" width="40%">
<?php
			if ( $page < 0 )
				$page = 1;
			if ( $page > $pages )
				$page = $pages;

			if ( $page > 1 )
			{
				if ( false && $page > 2 )
				{
?>
<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>">&lt;&lt;</a>
<?php
				}
?>
<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $page - 1 ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>">&lt;</a>
<?php
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
<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $new_page ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>"><?= $new_page ?></a>&nbsp;
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
&nbsp;<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $new_page ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>"><?= $new_page ?></a>
<?php
				}
?>
<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $page + 1 ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>">&gt;</a>
<?php
				if ( false && $page < ($pages-1) )
				{
?>
<a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=<?= $pages ?><?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>">&gt;&gt;</a>
<?php
				}
			}
?>
</td>
<td align="right" class="text" width="25%"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=0<?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>"><?= $zmSlangViewAll ?></a></td>
<?php
		}
		else
		{
?>
<td align="center" class="text" width="40%">&nbsp;</td>
<td align="center" class="text" width="25%"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?><?= $sort_query ?>&limit=<?= $limit ?>"><?= $zmSlangViewPaged ?></a></td>
<?php
		}
	}
?>
<td align="right" class="text" width="15%"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></td>
</tr>
<tr><td colspan="4" class="text">&nbsp;</td></tr>
<tr>
<td align="left" class="text"><a href="javascript: location.reload(true);"><?= $zmSlangRefresh ?></a></td>
<td colspan="1" align="center" class="text"><a href="javascript: filterWindow( '<?= $PHP_SELF ?>?view=filter&page=<?= $page ?><?= $filter_query ?>', 'zmFilter' );"><?= $zmSlangShowFilterWindow ?></a></td>
<td colspan="2" align="right" class="text"><a href="javascript: timelineWindow( '<?= $PHP_SELF ?>?view=timeline<?= $filter_query ?>', 'zmTimeline' );"><?= $zmSlangShowTimeline ?></a></td>
</tr>
<tr><td colspan="4" class="text">&nbsp;</td></tr>
<tr><td colspan="4"><table border="0" cellspacing="1" cellpadding="0" width="100%" bgcolor="#7F7FB2">
<?php
	flush();
	$count = 0;
	foreach ( $events as $event )
	{
		if ( ($count++%ZM_WEB_EVENTS_PER_PAGE) == 0 )
		{
?>
<tr align="center" bgcolor="#FFFFFF">
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=Id&sort_asc=<?= $sort_field == 'Id'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangId ?><?php if ( $sort_field == "Id" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=Name&sort_asc=<?= $sort_field == 'Name'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangName ?><?php if ( $sort_field == "Name" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=MonitorName&sort_asc=<?= $sort_field == 'MonitorName'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangMonitor ?><?php if ( $sort_field == "MonitorName" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=Cause&sort_asc=<?= $sort_field == 'Cause'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangCause ?><?php if ( $sort_field == "Cause" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=StartTime&sort_asc=<?= $sort_field == 'StartTime'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangTime ?><?php if ( $sort_field == "StartTime" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=Length&sort_asc=<?= $sort_field == 'Length'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangDuration ?><?php if ( $sort_field == "Length" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=Frames&sort_asc=<?= $sort_field == 'Frames'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangFrames ?><?php if ( $sort_field == "Frames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=AlarmFrames&sort_asc=<?= $sort_field == 'AlarmFrames'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangAlarmBrFrames ?><?php if ( $sort_field == "AlarmFrames" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=TotScore&sort_asc=<?= $sort_field == 'TotScore'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangTotalBrScore ?><?php if ( $sort_field == "TotScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=AvgScore&sort_asc=<?= $sort_field == 'AvgScore'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangAvgBrScore ?><?php if ( $sort_field == "AvgScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<td class="text"><a href="<?= $PHP_SELF ?>?view=<?= $view ?>&page=1<?= $filter_query ?>&sort_field=MaxScore&sort_asc=<?= $sort_field == 'MaxScore'?!$sort_asc:0 ?>&limit=<?= $limit ?>"><?= $zmSlangMaxBrScore ?><?php if ( $sort_field == "MaxScore" ) if ( $sort_asc ) echo "(^)"; else echo "(v)"; ?></a></td>
<?php
		if ( ZM_WEB_LIST_THUMBS )
		{
?>
<td class="text"><?= $zmSlangThumbnail ?></td>
<?php
		}
?>
<td class="text"><input type="checkbox" name="toggle_check" value="1" onClick="toggleCheck( this, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
		}
		if ( $event['LearnState'] == '+' )
			$bgcolor = "#98FB98";
		elseif ( $event['LearnState'] == '-' )
			$bgcolor = "#FFC0CB";
		else
			unset( $bgcolor );

		$scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
<tr<?= ' bgcolor="'.(isset($bgcolor)?$bgcolor:"#FFFFFF").'"' ?> >
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&eid=<?= $event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&page=1', 'zmEvent', <?= reScale( $event['Width'], $scale )+$jws['event']['w']  ?>, <?= reScale( $event['Height'], $scale )+$jws['event']['h'] ?> );"><?= $event['Id'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
<td align="center" class="text"><a href="javascript: eventWindow( '<?= $PHP_SELF ?>?view=event&eid=<?= $event['Id'] ?><?= $filter_query ?><?= $sort_query ?>&page=1', 'zmEvent', <?= reScale( $event['Width'], $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE )+$jws['event']['w']  ?>, <?= reScale( $event['Height'], $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE )+$jws['event']['h'] ?> );"><?= $event['Name'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
<td align="center" class="text"><?= $event['MonitorName'] ?></td>
<td align="center" class="text"><?= makeLink( "javascript: newWindow( '$PHP_SELF?view=eventdetail&eid=".$event['Id']."', 'zmEventDetail', ".$jws['eventdetail']['w'].", ".$jws['eventdetail']['h']." );", $event['Cause'], canEdit( 'Events' ) ) ?></td>
<td align="center" class="text"><?= strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event['StartTime']) ) ?></td>
<td align="center" class="text"><?= $event['Length'] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frames&eid=<?= $event['Id'] ?>', 'zmFrames', <?= $jws['frames']['w'] ?>, <?= $jws['frames']['h'] ?> );"><?= $event['Frames'] ?></a></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frames&eid=<?= $event['Id'] ?>', 'zmFrames', <?= $jws['frames']['w'] ?>, <?= $jws['frames']['h'] ?> );"><?= $event['AlarmFrames'] ?></a></td>
<td align="center" class="text"><?= $event['TotScore'] ?></td>
<td align="center" class="text"><?= $event['AvgScore'] ?></td>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $event['Id'] ?>&fid=0', 'zmImage', <?= reScale( $event['Width'], $scale )+$jws['image']['w']  ?>, <?= reScale( $event['Height'], $scale )+$jws['image']['h']  ?> );"><?= $event['MaxScore'] ?></a></td>
<?php
	if ( ZM_WEB_LIST_THUMBS )
	{
		$thumb_data = createListThumbnail( $event );
?>
<td align="center" class="text"><a href="javascript: newWindow( '<?= $PHP_SELF ?>?view=frame&eid=<?= $event['Id'] ?>&fid=<?= $thumb_data['FrameId'] ?>', 'zmImage', <?= reScale( $event['Width'], $scale )+$jws['image']['w']  ?>, <?= reScale( $event['Height'], $scale )+$jws['image']['h']  ?> );"><img src="<?= $thumb_data['Path'] ?>" width="<?= $thumb_data['Width'] ?>" height="<?= $thumb_data['Height'] ?>" border="0" alt="<?= $thumb_data['FrameId'] ?>/<?= $event['MaxScore'] ?>"></a></td>
<?php
	}
?>
<td align="center" class="text"><input type="checkbox" name="mark_eids[]" value="<?= $event['Id'] ?>" onClick="configureButton( this, 'mark_eids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>></td>
</tr>
<?php
	}
?>
</table></td></tr>
</table></td>
</tr>
<?php if ( true || canEdit( 'Events' ) ) { ?>
<tr><td align="right">
<?php if ( LEARN_MODE ) { ?><select name="learn_state" class="form" disabled><option value=""><?= $zmSlangIgnore ?></option><option value="-"><?= $zmSlangExclude ?></option><option value="+"><?= $zmSlangInclude ?></option></select>&nbsp;&nbsp;<input type="button" name="learn_btn" value="<?= $zmSlangSetLearnPrefs ?>" class="form" onClick="document.event_form.action.value = 'learn'; document.event_form.submit();" disabled>&nbsp;&nbsp;<?php } ?>
<input type="button" name="view_btn" value="<?= $zmSlangView ?>" class="form" onClick="viewEvents( document.event_form, 'mark_eids' );" disabled>
&nbsp;&nbsp;<input type="button" name="archive_btn" value="<?= $zmSlangArchive ?>" class="form" onClick="document.event_form.action.value = 'archive'; document.event_form.submit();" disabled>
&nbsp;&nbsp;<input type="button" name="unarchive_btn" value="<?= $zmSlangUnarchive ?>" class="form" onClick="document.event_form.action.value = 'unarchive'; document.event_form.submit();" disabled>
&nbsp;&nbsp;<input type="button" name="edit_btn" value="<?= $zmSlangEdit ?>" class="form" onClick="editEvents( document.event_form, 'mark_eids' )" disabled>
&nbsp;&nbsp;<input type="button" name="export_btn" value="<?= $zmSlangExport ?>" class="form" onClick="exportEvents( document.event_form, 'mark_eids' )" disabled>
&nbsp;&nbsp;<input type="button" name="delete_btn" value="<?= $zmSlangDelete ?>" class="form" onClick="deleteEvents( document.event_form, 'mark_eids' );" disabled>
</td></tr>
<?php } ?>
</table></center>
</form>
<script type="text/javascript">
window.setTimeout( "window.focus()", 500 );
</script>
</body>
</html>
<?php
}
?>
