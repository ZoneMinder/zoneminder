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

if ( !canView( 'Events' ) || (!empty($_REQUEST['execute']) && !canEdit('Events')) )
{
    $_REQUEST['view'] = "error";
    return;
}

if ( !empty($_REQUEST['execute']) )
{
    executeFilter( $tempFilterName );
}

$countSql = "select count(E.Id) as EventCount from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
$eventsSql = "select E.Id,E.MonitorId,M.Name As MonitorName,M.Width,M.Height,M.DefaultScale,E.Name,E.Cause,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
if ( $user['MonitorIds'] )
{
    $countSql .= " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
    $eventsSql .= " M.Id in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
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
$eventsSql .= " order by $sortColumn $sortOrder";

$nEvents = dbFetchOne( $countSql, 'EventCount' );
if ( !empty($_REQUEST['limit']) && $nEvents > $_REQUEST['limit'] )
{
    $nEvents = $_REQUEST['limit'];
}
$pages = (int)ceil($nEvents/ZM_WEB_EVENTS_PER_PAGE);
if ( $pages > 1 )
{
    if ( !empty($_REQUEST['page']) )
    {
        if ( $_REQUEST['page'] < 0 )
            $_REQUEST['page'] = 1;
        if ( $_REQUEST['page'] > $pages )
            $_REQUEST['page'] = $pages;
    }
}
if ( !empty($_REQUEST['page']) )
{
    $limit_start = (($_REQUEST['page']-1)*ZM_WEB_EVENTS_PER_PAGE);
    if ( empty( $_REQUEST['limit'] ) )
    {
        $limit_amount = ZM_WEB_EVENTS_PER_PAGE;
    }
    else
    {
        $limit_left = $_REQUEST['limit'] - $limit_start;
        $limit_amount = ($limit_left>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limit_left;
    }
    $eventsSql .= " limit $limit_start, $limit_amount";
}
elseif ( !empty( $_REQUEST['limit'] ) )
{
    $eventsSql .= " limit 0, ".$_REQUEST['limit'];
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
$pagination = getPagination( $pages, $_REQUEST['page'], $maxShortcuts, $filterQuery.$sortQuery.'&limit='.$_REQUEST['limit'] );

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
    if ( !empty($_REQUEST['page']) )
    {
?>
        <a href="?view=<?= $_REQUEST['view'] ?>&page=0<?= $filterQuery ?><?= $sortQuery ?>&limit=<?= $_REQUEST['limit'] ?>"><?= $SLANG['ViewAll'] ?></a>
<?php
    }
    else
    {
?>
        <a href="?view=<?= $_REQUEST['view'] ?>&page=1<?= $filterQuery ?><?= $sortQuery ?>&limit=<?= $_REQUEST['limit'] ?>"><?= $SLANG['ViewPaged'] ?></a>
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
        <input type="hidden" name="view" value="<?= $_REQUEST['view'] ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="page" value="<?= $_REQUEST['page'] ?>"/>
        <?= $_REQUEST['filter']['fields'] ?>
        <input type="hidden" name="sort_field" value="<?= $_REQUEST['sort_field'] ?>"/>
        <input type="hidden" name="sort_asc" value="<?= $_REQUEST['sort_asc'] ?>"/>
        <input type="hidden" name="limit" value="<?= $_REQUEST['limit'] ?>"/>
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
          <a id="filterLink" href="#" onclick="createPopup( '?view=filter&page=<?= $_REQUEST['page'] ?><?= $filterQuery ?>', 'zmFilter', 'filter' );"><?= $SLANG['ShowFilterWindow'] ?></a>
          <a id="timelineLink" href="#" onclick="createPopup( '?view=timeline<?= $filterQuery ?>', 'zmTimeline', 'timeline' );"><?= $SLANG['ShowTimeline'] ?></a>
        </p>
        <table id="contentTable" class="major" cellspacing="0"/>
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
              <td class="colId"><?= makePopupLink( '?view=event&eid='.$event['Id'].$filterQuery.$sortQuery.'&page=1', 'zmEvent', array( 'event', reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) ), $event['Id'].($event['Archived']?'*':'') ) ?></td>
              <td class="colName"><?= makePopupLink( '?view=event&eid='.$event['Id'].$filterQuery.$sortQuery.'&page=1', 'zmEvent', array( 'event', reScale( $event['Width'], $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), reScale( $event['Height'], $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ) ), $event['Name'].($event['Archived']?'*':'' ) ) ?></td>
              <td class="colMonitorName"><?= $event['MonitorName'] ?></td>
              <td class="colCause"><?= makePopupLink( '?view=eventdetail&eid='.$event['Id'], 'zmEventDetail', 'eventdetail', $event['Cause'], canEdit( 'Events' ) ) ?></td>
              <td class="colTime"><?= strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event['StartTime']) ) ?></td>
              <td class="colDuration"><?= $event['Length'] ?></td>
              <td class="colFrames"><?= makePopupLink( '?view=frames&eid='.$event['Id'], 'zmFrames', 'frames', $event['Frames'] ) ?></td>
              <td class="colAlarmFrames"><?= makePopupLink( '?view=frames&eid='.$event['Id'], 'zmFrames', 'frames', $event['AlarmFrames'] ) ?></a></td>
              <td class="colTotScore"><?= $event['TotScore'] ?></td>
              <td class="colAvgScore"><?= $event['AvgScore'] ?></td>
              <td class="colMaxScore"><?= makePopupLink( '?view=frame&eid='.$event['Id'].'&fid=0', 'zmImage', array( 'image', reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) ), $event['MaxScore'] ) ?></td>
<?php
    if ( ZM_WEB_LIST_THUMBS )
    {
        $thumb_data = createListThumbnail( $event );
?>
              <td class="colThumbnail"><?= makePopupLink( '?view=frame&eid='.$event['Id'].'&fid='.$thumb_data['FrameId'], 'zmImage', array( 'image', reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) ), '<img src="'.$thumb_data['Path'].'" width="'.$thumb_data['Width'].'" height="'.$thumb_data['Height'].'" alt="'.$thumb_data['FrameId'].'/'.$event['MaxScore'].'"/>' ) ?></td>
<?php
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
