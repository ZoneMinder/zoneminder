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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

if ( !canView( 'Events' ) || (!empty($_REQUEST['execute']) && !canEdit('Events')) ) {
  $view = 'error';
  return;
}

require_once( 'includes/Event.php' );

if ( !empty($_REQUEST['execute']) ) {
  executeFilter( $tempFilterName );
}

$countSql = 'SELECT count(E.Id) AS EventCount FROM Monitors AS M INNER JOIN Events AS E ON (M.Id = E.MonitorId) WHERE';
$eventsSql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId) WHERE';
if ( $user['MonitorIds'] ) {
	$user_monitor_ids = ' M.Id in ('.$user['MonitorIds'].')';
	$countSql .= $user_monitor_ids;
	$eventsSql .= $user_monitor_ids;
} else {
  $countSql .= " 1";
  $eventsSql .= " 1";
}

parseSort();
parseFilter( $_REQUEST['filter'] );
$filterQuery = $_REQUEST['filter']['query'];

if ( $_REQUEST['filter']['sql'] ) {
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
if ( !empty($limit) && $nEvents > $limit ) {
  $nEvents = $limit;
}
$pages = (int)ceil($nEvents/ZM_WEB_EVENTS_PER_PAGE);
if ( !empty($page) ) {
  if ( $page < 0 )
    $page = 1;
  else if ( $page > $pages )
    $page = $pages;
}

if ( !empty($page) ) {
  $limitStart = (($page-1)*ZM_WEB_EVENTS_PER_PAGE);
  if ( empty( $limit ) ) {
    $limitAmount = ZM_WEB_EVENTS_PER_PAGE;
  } else {
    $limitLeft = $limit - $limitStart;
    $limitAmount = ($limitLeft>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limitLeft;
  }
  $eventsSql .= " limit $limitStart, $limitAmount";
} elseif ( !empty( $limit ) ) {
  $eventsSql .= ' limit 0, '.$limit;
}

$maxWidth = 0;
$maxHeight = 0;
$archived = false;
$unarchived = false;
$events = array();
foreach ( dbFetchAll( $eventsSql ) as $event_row ) {
  $events[] = $event = new Event( $event_row );

# Doesn this code do anything? 
  $scale = max( reScale( SCALE_BASE, $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
  $eventWidth = reScale( $event_row['Width'], $scale );
  $eventHeight = reScale( $event_row['Height'], $scale );
  if ( $maxWidth < $eventWidth ) $maxWidth = $eventWidth;
  if ( $maxHeight < $eventHeight ) $maxHeight = $eventHeight;
  if ( $event_row['Archived'] )
    $archived = true;
  else
    $unarchived = true;
}

$maxShortcuts = 5;
$pagination = getPagination( $pages, $page, $maxShortcuts, $filterQuery.$sortQuery.'&amp;limit='.$limit );

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Events') );

?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
<?php
if ( $pages > 1 ) {
  if ( !empty($page) ) {
?>
        <a href="?view=<?php echo $view ?>&amp;page=0<?php echo $filterQuery ?><?php echo $sortQuery ?>&amp;limit=<?php echo $limit ?>"><?php echo translate('ViewAll') ?></a>
<?php
  } else {
?>
        <a href="?view=<?php echo $view ?>&amp;page=1<?php echo $filterQuery ?><?php echo $sortQuery ?>&amp;limit=<?php echo $limit ?>"><?php echo translate('ViewPaged') ?></a>
<?php
  }
}
?>
        <a href="#" onclick="closeWindows();"><?php echo translate('Close') ?></a>
      </div>
      <h2><?php echo sprintf( $CLANG['EventCount'], $nEvents, zmVlang( $VLANG['Event'], $nEvents ) ) ?></h2>
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="post" action="">
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="page" value="<?php echo $page ?>"/>
        <?php echo $_REQUEST['filter']['fields'] ?>
        <input type="hidden" name="sort_field" value="<?php echo validHtmlStr($_REQUEST['sort_field']) ?>"/>
        <input type="hidden" name="sort_asc" value="<?php echo validHtmlStr($_REQUEST['sort_asc']) ?>"/>
        <input type="hidden" name="limit" value="<?php echo $limit ?>"/>
<?php
if ( $pagination ) {
?>
        <h3 class="pagination"><?php echo $pagination ?></h3>
<?php
}
?>
        <p id="controls">
          <a id="refreshLink" href="#" onclick="location.reload(true);"><?php echo translate('Refresh') ?></a>
          <a id="filterLink" href="#" onclick="createPopup( '?view=filter&amp;page=<?php echo $page ?><?php echo $filterQuery ?>', 'zmFilter', 'filter' );"><?php echo translate('ShowFilterWindow') ?></a>
          <a id="timelineLink" href="#" onclick="createPopup( '?view=timeline<?php echo $filterQuery ?>', 'zmTimeline', 'timeline' );"><?php echo translate('ShowTimeline') ?></a>
        </p>
        <table id="contentTable" class="major" cellspacing="0">
          <tbody>
<?php
$count = 0;
foreach ( $events as $event ) {
  if ( ($count++%ZM_WEB_EVENTS_PER_PAGE) == 0 ) {
?>
            <tr>
              <th class="colId"><a href="<?php echo sortHeader( 'Id' ) ?>"><?php echo translate('Id') ?><?php echo sortTag( 'Id' ) ?></a></th>
              <th class="colName"><a href="<?php echo sortHeader( 'Name' ) ?>"><?php echo translate('Name') ?><?php echo sortTag( 'Name' ) ?></a></th>
              <th class="colMonitor"><a href="<?php echo sortHeader( 'MonitorName' ) ?>"><?php echo translate('Monitor') ?><?php echo sortTag( 'MonitorName' ) ?></a></th>
              <th class="colCause"><a href="<?php echo sortHeader( 'Cause' ) ?>"><?php echo translate('Cause') ?><?php echo sortTag( 'Cause' ) ?></a></th>
              <th class="colTime"><a href="<?php echo sortHeader( 'StartTime' ) ?>"><?php echo translate('Time') ?><?php echo sortTag( 'StartTime' ) ?></a></th>
              <th class="colDuration"><a href="<?php echo sortHeader( 'Length' ) ?>"><?php echo translate('Duration') ?><?php echo sortTag( 'Length' ) ?></a></th>
              <th class="colFrames"><a href="<?php echo sortHeader( 'Frames' ) ?>"><?php echo translate('Frames') ?><?php echo sortTag( 'Frames' ) ?></a></th>
              <th class="colAlarmFrames"><a href="<?php echo sortHeader( 'AlarmFrames' ) ?>"><?php echo translate('AlarmBrFrames') ?><?php echo sortTag( 'AlarmFrames' ) ?></a></th>
              <th class="colTotScore"><a href="<?php echo sortHeader( 'TotScore' ) ?>"><?php echo translate('TotalBrScore') ?><?php echo sortTag( 'TotScore' ) ?></a></th>
              <th class="colAvgScore"><a href="<?php echo sortHeader( 'AvgScore' ) ?>"><?php echo translate('AvgBrScore') ?><?php echo sortTag( 'AvgScore' ) ?></a></th>
              <th class="colMaxScore"><a href="<?php echo sortHeader( 'MaxScore' ) ?>"><?php echo translate('MaxBrScore') ?><?php echo sortTag( 'MaxScore' ) ?></a></th>
<?php
    if ( ZM_WEB_EVENT_DISK_SPACE ) { ?>
              <th class="colDiskSpace"><a href="<?php echo sortHeader( 'DiskSpace' ) ?>"><?php echo translate('DiskSpace') ?><?php echo sortTag( 'DiskSpace' ) ?></a></th>
<?php
    }
    if ( ZM_WEB_LIST_THUMBS ) {
?>
              <th class="colThumbnail"><?php echo translate('Thumbnail') ?></th>
<?php
    }
?>
              <th class="colMark"><input type="checkbox" name="toggleCheck" value="1" onclick="toggleCheckbox( this, 'markEids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></th>
            </tr>
<?php
  }
  $scale = max( reScale( SCALE_BASE, $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
            <tr>
              <td class="colId"><?php echo makePopupLink( '?view=event&amp;eid='.$event->Id().$filterQuery.$sortQuery.'&amp;page=1', 'zmEvent', array( 'event', reScale( $event->Width(), $scale ), reScale( $event->Height(), $scale ) ), $event->Id().($event->Archived()?'*':'') ) ?></td>
              <td class="colName"><?php echo makePopupLink( '?view=event&amp;eid='.$event->Id().$filterQuery.$sortQuery.'&amp;page=1', 'zmEvent', array( 'event', reScale( $event->Width(), $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE ), reScale( $event->Height(), $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE ) ), validHtmlStr($event->Name()).($event->Archived()?'*':'' ) ) ?></td>
              <td class="colMonitorName"><?php echo $event->MonitorName() ?></td>
              <td class="colCause"><?php echo makePopupLink( '?view=eventdetail&amp;eid='.$event->Id(), 'zmEventDetail', 'eventdetail', validHtmlStr($event->Cause()), canEdit( 'Events' ), 'title="'.htmlspecialchars($event->Notes()).'"' ) ?></td>
              <td class="colTime"><?php echo strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event->StartTime()) ) ?></td>
              <td class="colDuration"><?php echo gmdate("H:i:s", $event->Length() ) ?></td>
              <td class="colFrames"><?php echo makePopupLink( '?view=frames&amp;eid='.$event->Id(), 'zmFrames', 'frames', $event->Frames() ) ?></td>
              <td class="colAlarmFrames"><?php echo makePopupLink( '?view=frames&amp;eid='.$event->Id(), 'zmFrames', 'frames', $event->AlarmFrames() ) ?></td>
              <td class="colTotScore"><?php echo $event->TotScore() ?></td>
              <td class="colAvgScore"><?php echo $event->AvgScore() ?></td>
              <td class="colMaxScore"><?php echo makePopupLink( '?view=frame&amp;eid='.$event->Id().'&amp;fid=0', 'zmImage', array( 'image', reScale( $event->Width(), $scale ), reScale( $event->Height(), $scale ) ), $event->MaxScore() ) ?></td>
<?php
  if ( ZM_WEB_EVENT_DISK_SPACE ) {
?>
              <td class="colDiskSpace"><?php echo human_filesize( $event->DiskSpace() ) ?></td>
<?php
  }
  if ( ZM_WEB_LIST_THUMBS ) {
    if ( $thumbData = $event->createListThumbnail() ) {
?>
              <td class="colThumbnail">
<?php 
      $imgSrc = '?view=image&amp;eid='.$event->Id().'&amp;fid='.$thumbData['FrameId'].'&amp;width='.$thumbData['Width'].'&amp;height='.$thumbData['Height'];
      $streamSrc = $event->getStreamSrc( array( 'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single') );

      $imgHtml = '<img id="thumbnail'.$event->id().'" src="'.$imgSrc.'" alt="'. validHtmlStr('Event '.$event->Id()) .'" style="width:'. validInt($thumbData['Width']) .'px;height:'. validInt( $thumbData['Height'] ).'px;" onmouseover="this.src=\''.$streamSrc.'\';" onmouseout="this.src=\''.$imgSrc.'\';"/>';

      echo makePopupLink( 
          '?view=frame&amp;eid='.$event->Id().'&amp;fid='.$thumbData['FrameId'],
          'zmImage',
          array( 'image', reScale( $event->Width(), $scale ), reScale( $event->Height(), $scale ) ),
          $imgHtml
        );
?>
              </td>
<?php
    } else {
?>
              <td class="colThumbnail">&nbsp;</td>
<?php
    }
  } // end if ZM_WEB_LIST_THUMBS
?>
              <td class="colMark"><input type="checkbox" name="markEids[]" value="<?php echo $event->Id() ?>" onclick="configureButton( this, 'markEids' );"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
<?php
if ( $pagination ) {
?>
        <h3 class="pagination"><?php echo $pagination ?></h3>
<?php
}
if ( true || canEdit( 'Events' ) ) {
?>
        <div id="contentButtons">
          <input type="button" name="viewBtn" value="<?php echo translate('View') ?>" onclick="viewEvents( this, 'markEids' );" disabled="disabled"/>
          <input type="button" name="archiveBtn" value="<?php echo translate('Archive') ?>" onclick="archiveEvents( this, 'markEids' )" disabled="disabled"/>
          <input type="button" name="unarchiveBtn" value="<?php echo translate('Unarchive') ?>" onclick="unarchiveEvents( this, 'markEids' );" disabled="disabled"/>
          <input type="button" name="editBtn" value="<?php echo translate('Edit') ?>" onclick="editEvents( this, 'markEids' )" disabled="disabled"/>
          <input type="button" name="exportBtn" value="<?php echo translate('Export') ?>" onclick="exportEvents( this, 'markEids' )" disabled="disabled"/>
          <input type="button" name="deleteBtn" value="<?php echo translate('Delete') ?>" onclick="deleteEvents( this, 'markEids' );" disabled="disabled"/>
        </div>
<?php
}
?>
      </form>
    </div>
  </div>
</body>
</html>
