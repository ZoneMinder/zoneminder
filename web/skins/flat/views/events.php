<?php
//
// ZoneMinder web events view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) || (!empty($_REQUEST['execute']) && !canEdit('Events')) )
{
    $view = "error";
    return;
}

if ( !empty($_REQUEST['execute']) )
{
    executeFilter( $tempFilterName );
}

$countSql = 'SELECT count(E.Id) AS EventCount FROM Monitors AS M INNER JOIN Events AS E ON (M.Id = E.MonitorId) WHERE';
$eventsSql = 'SELECT E.Id,E.MonitorId,M.Name AS MonitorName,M.DefaultScale,E.Name,E.Width,E.Height,E.Cause,E.Notes,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId) WHERE';
if ( $user['MonitorIds'] ) {
	$user_monitor_ids = " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
	$countSql .= $user_monitor_ids;
	$eventsSql .= $user_monitor_ids;
} else {
    $countSql .= " 1";
    $eventsSql .= " 1";
}

parseSort();
parseFilter( $_REQUEST['filter'] );
$filterQuery = $_REQUEST['filter']['query'];

if ( $_REQUEST['filter']['sql'] )
{
    $countSql .= $_REQUEST['filter']['sql'];
    $eventsSql .= $_REQUEST['filter']['sql'];
}
$eventsSql .= " ORDER BY $sortColumn $sortOrder";

if ( isset($_REQUEST['page']) )
    $page = validInt($_REQUEST['page']);
else
    $page = 0;
if ( isset($_REQUEST['limit']) )
    $limit = validInt($_REQUEST['limit']);
else
    $limit = 0;

$nEvents = dbFetchOne( $countSql, 'EventCount' );
if ( !empty($limit) && $nEvents > $limit )
{
    $nEvents = $limit;
}
$pages = (int)ceil($nEvents/ZM_WEB_EVENTS_PER_PAGE);
if ( $pages > 1 ) {
    if ( !empty($page) ) {
        if ( $page < 0 )
            $page = 1;
        if ( $page > $pages )
            $page = $pages;
    }
}
if ( !empty($page) ) {
    $limitStart = (($page-1)*ZM_WEB_EVENTS_PER_PAGE);
    if ( empty( $limit ) )
    {
        $limitAmount = ZM_WEB_EVENTS_PER_PAGE;
    }
    else
    {
        $limitLeft = $limit - $limitStart;
        $limitAmount = ($limitLeft>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limitLeft;
    }
    $eventsSql .= " limit $limitStart, $limitAmount";
} elseif ( !empty( $limit ) ) {
    $eventsSql .= " limit 0, ".$limit;
}

$maxWidth = 0;
$maxHeight = 0;
$archived = false;
$unarchived = false;
$events = array();
foreach ( dbFetchAll( $eventsSql ) as $event )
{
    $events[] = $event;
    $scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
    $eventWidth = reScale( $event['Width'], $scale );
    $eventHeight = reScale( $event['Height'], $scale );
    if ( $maxWidth < $eventWidth ) $maxWidth = $eventWidth;
    if ( $maxHeight < $eventHeight ) $maxHeight = $eventHeight;
    if ( $event['Archived'] )
        $archived = true;
    else
        $unarchived = true;
}

$maxShortcuts = 5;
$pagination = getPagination( $pages, $page, $maxShortcuts, $filterQuery.$sortQuery.'&amp;limit='.$limit );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Events'] );

?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
<?php
if ( $pages > 1 )
{
    if ( !empty($page) )
    {
?>
        <a href="?view=<?= $view ?>&amp;page=0<?= $filterQuery ?><?= $sortQuery ?>&amp;limit=<?= $limit ?>"><?= $SLANG['ViewAll'] ?></a>
<?php
    }
    else
    {
?>
        <a href="?view=<?= $view ?>&amp;page=1<?= $filterQuery ?><?= $sortQuery ?>&amp;limit=<?= $limit ?>"><?= $SLANG['ViewPaged'] ?></a>
<?php
    }
}
?>
        <a href="#" onclick="closeWindows();"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= sprintf( $CLANG['EventCount'], $nEvents, zmVlang( $VLANG['Event'], $nEvents ) ) ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="page" value="<?= $page ?>"/>
        <?= $_REQUEST['filter']['fields'] ?>
        <input type="hidden" name="sort_field" value="<?= validHtmlStr($_REQUEST['sort_field']) ?>"/>
        <input type="hidden" name="sort_asc" value="<?= validHtmlStr($_REQUEST['sort_asc']) ?>"/>
        <input type="hidden" name="limit" value="<?= $limit ?>"/>
<?php
if ( $pagination )
{
?>
        <h3 class="pagination"><?= $pagination ?></h3>
<?php
}
?>
        <p id="controls">
          <a id="refreshLink" href="#" onclick="location.reload(true);"><?= $SLANG['Refresh'] ?></a>
          <a id="filterLink" href="#" onclick="createPopup( '?view=filter&amp;page=<?= $page ?><?= $filterQuery ?>', 'zmFilter', 'filter' );"><?= $SLANG['ShowFilterWindow'] ?></a>
          <a id="timelineLink" href="#" onclick="createPopup( '?view=timeline<?= $filterQuery ?>', 'zmTimeline', 'timeline' );"><?= $SLANG['ShowTimeline'] ?></a>
        </p>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
<?php
$count = 0;
foreach ( $events as $event )
{
    if ( ($count++%ZM_WEB_EVENTS_PER_PAGE) == 0 )
    {
?>
            <tr>
              <th class="colId"><a href="<?= sortHeader( 'Id' ) ?>"><?= $SLANG['Id'] ?><?= sortTag( 'Id' ) ?></a></th>
              <th class="colName"><a href="<?= sortHeader( 'Name' ) ?>"><?= $SLANG['Name'] ?><?= sortTag( 'Name' ) ?></a></th>
              <th class="colMonitor"><a href="<?= sortHeader( 'MonitorName' ) ?>"><?= $SLANG['Monitor'] ?><?= sortTag( 'MonitorName' ) ?></a></th>
              <th class="colCause"><a href="<?= sortHeader( 'Cause' ) ?>"><?= $SLANG['Cause'] ?><?= sortTag( 'Cause' ) ?></a></th>
              <th class="colTime"><a href="<?= sortHeader( 'StartTime' ) ?>"><?= $SLANG['Time'] ?><?= sortTag( 'StartTime' ) ?></a></th>
              <th class="colDuration"><a href="<?= sortHeader( 'Length' ) ?>"><?= $SLANG['Duration'] ?><?= sortTag( 'Length' ) ?></a></th>
              <th class="colFrames"><a href="<?= sortHeader( 'Frames' ) ?>"><?= $SLANG['Frames'] ?><?= sortTag( 'Frames' ) ?></a></th>
              <th class="colAlarmFrames"><a href="<?= sortHeader( 'AlarmFrames' ) ?>"><?= $SLANG['AlarmBrFrames'] ?><?= sortTag( 'AlarmFrames' ) ?></a></th>
              <th class="colTotScore"><a href="<?= sortHeader( 'TotScore' ) ?>"><?= $SLANG['TotalBrScore'] ?><?= sortTag( 'TotScore' ) ?></a></th>
              <th class="colAvgScore"><a href="<?= sortHeader( 'AvgScore' ) ?>"><?= $SLANG['AvgBrScore'] ?><?= sortTag( 'AvgScore' ) ?></a></th>
              <th class="colMaxScore"><a href="<?= sortHeader( 'MaxScore' ) ?>"><?= $SLANG['MaxBrScore'] ?><?= sortTag( 'MaxScore' ) ?></a></th>
<?php
        if ( ZM_WEB_LIST_THUMBS )
        {
?>
              <th class="colThumbnail"><?= $SLANG['Thumbnail'] ?></th>
<?php
        }
?>
              <th class="colMark"><input type="checkbox" name="toggleCheck" value="1" onclick="toggleCheckbox( this, 'markEids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></th>
            </tr>
<?php
    }
        $scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
            <tr>
              <td class="colId"><?= makePopupLink( '?view=event&amp;eid='.$event['Id'].$filterQuery.$sortQuery.'&amp;page=1', 'zmEvent', array( 'event', reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) ), $event['Id'].($event['Archived']?'*':'') ) ?></td>
              <td class="colName"><?= makePopupLink( '?view=event&amp;eid='.$event['Id'].$filterQuery.$sortQuery.'&amp;page=1', 'zmEvent', array( 'event', reScale( $event['Width'], $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), reScale( $event['Height'], $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ) ), validHtmlStr($event['Name']).($event['Archived']?'*':'' ) ) ?></td>
              <td class="colMonitorName"><?= $event['MonitorName'] ?></td>
              <td class="colCause"><?= makePopupLink( '?view=eventdetail&amp;eid='.$event['Id'], 'zmEventDetail', 'eventdetail', validHtmlStr($event['Cause']), canEdit( 'Events' ), 'title="'.htmlspecialchars($event['Notes']).'"' ) ?></td>
              <td class="colTime"><?= strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event['StartTime']) ) ?></td>
              <td class="colDuration"><?= $event['Length'] ?></td>
              <td class="colFrames"><?= makePopupLink( '?view=frames&amp;eid='.$event['Id'], 'zmFrames', 'frames', $event['Frames'] ) ?></td>
              <td class="colAlarmFrames"><?= makePopupLink( '?view=frames&amp;eid='.$event['Id'], 'zmFrames', 'frames', $event['AlarmFrames'] ) ?></td>
              <td class="colTotScore"><?= $event['TotScore'] ?></td>
              <td class="colAvgScore"><?= $event['AvgScore'] ?></td>
              <td class="colMaxScore"><?= makePopupLink( '?view=frame&amp;eid='.$event['Id'].'&amp;fid=0', 'zmImage', array( 'image', reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) ), $event['MaxScore'] ) ?></td>
<?php
    if ( ZM_WEB_LIST_THUMBS )
    {
        if ( $thumbData = createListThumbnail( $event ) )
        {
?>
              <td class="colThumbnail"><?= makePopupLink( '?view=frame&amp;eid='.$event['Id'].'&amp;fid='.$thumbData['FrameId'], 'zmImage', array( 'image', reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) ), '<img src="'.viewImagePath( $thumbData['Path'] ).'" width="'.$thumbData['Width'].'" height="'.$thumbData['Height'].'" alt="'.$thumbData['FrameId'].'/'.$event['MaxScore'].'"/>' ) ?></td>
<?php
        }
        else
        {
?>
              <td class="colThumbnail">&nbsp;</td>
<?php
        }
    }
?>
              <td class="colMark"><input type="checkbox" name="markEids[]" value="<?= $event['Id'] ?>" onclick="configureButton( this, 'markEids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
<?php
if ( $pagination )
{
?>
        <h3 class="pagination"><?= $pagination ?></h3>
<?php
}
if ( true || canEdit( 'Events' ) )
{
?>
        <div id="contentButtons">
          <input type="button" name="viewBtn" value="<?= $SLANG['View'] ?>" onclick="viewEvents( this, 'markEids' );" disabled="disabled"/>
          <input type="button" name="archiveBtn" value="<?= $SLANG['Archive'] ?>" onclick="archiveEvents( this, 'markEids' )" disabled="disabled"/>
          <input type="button" name="unarchiveBtn" value="<?= $SLANG['Unarchive'] ?>" onclick="unarchiveEvents( this, 'markEids' );" disabled="disabled"/>
          <input type="button" name="editBtn" value="<?= $SLANG['Edit'] ?>" onclick="editEvents( this, 'markEids' )" disabled="disabled"/>
          <input type="button" name="exportBtn" value="<?= $SLANG['Export'] ?>" onclick="exportEvents( this, 'markEids' )" disabled="disabled"/>
          <input type="button" name="deleteBtn" value="<?= $SLANG['Delete'] ?>" onclick="deleteEvents( this, 'markEids' );" disabled="disabled"/>
        </div>
<?php
}
?>
      </form>
    </div>
  </div>
</body>
</html>
