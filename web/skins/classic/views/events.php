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

if ( !canView('Events') || (!empty($_REQUEST['execute']) && !canEdit('Events')) ) {
  $view = 'error';
  return;
}

require_once('includes/Event.php');

$countSql = 'SELECT count(E.Id) AS EventCount FROM Monitors AS M INNER JOIN Events AS E ON (M.Id = E.MonitorId) WHERE';
$eventsSql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId) WHERE';
if ( $user['MonitorIds'] ) {
	$user_monitor_ids = ' M.Id in ('.$user['MonitorIds'].')';
	$countSql .= $user_monitor_ids;
	$eventsSql .= $user_monitor_ids;
} else {
  $countSql .= ' 1';
  $eventsSql .= ' 1';
}

parseSort();
parseFilter($_REQUEST['filter']);
$filterQuery = $_REQUEST['filter']['query'];

if ( $_REQUEST['filter']['sql'] ) {
  $countSql .= $_REQUEST['filter']['sql'];
  $eventsSql .= $_REQUEST['filter']['sql'];
}
$eventsSql .= " ORDER BY $sortColumn $sortOrder,Id $sortOrder";

$page = isset($_REQUEST['page']) ? validInt($_REQUEST['page']) : 0;
$limit = isset($_REQUEST['limit']) ? validInt($_REQUEST['limit']) : 0;

$nEvents = dbFetchOne($countSql, 'EventCount');
if ( !empty($limit) && $nEvents > $limit ) {
  $nEvents = $limit;
}
$pages = (int)ceil($nEvents/ZM_WEB_EVENTS_PER_PAGE);
#Logger::Debug("Page $page Limit $limit #vents: $nEvents pages: $pages ");
if ( !empty($page) ) {
  if ( $page < 0 )
    $page = 1;
  else if ( $pages and ( $page > $pages ) )
    $page = $pages;

  $limitStart = (($page-1)*ZM_WEB_EVENTS_PER_PAGE);
  if ( empty( $limit ) ) {
    $limitAmount = ZM_WEB_EVENTS_PER_PAGE;
  } else {
    $limitLeft = $limit - $limitStart;
    $limitAmount = ($limitLeft>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limitLeft;
  }
  $eventsSql .= " limit $limitStart, $limitAmount";
} elseif ( !empty($limit) ) {
  $eventsSql .= ' limit 0, '.$limit;
}

$maxShortcuts = 5;
$pagination = getPagination($pages, $page, $maxShortcuts, $filterQuery.$sortQuery.$limitQuery);

$focusWindow = true;

if ( $_POST ) {
  header('Location: ' . $_SERVER['REQUEST_URI'].htmlspecialchars_decode($filterQuery).htmlspecialchars_decode($sortQuery).$limitQuery.'&page='.$page);
  exit();
}

$storage_areas = Storage::find();
$StorageById = array();
foreach ( $storage_areas as $S ) {
  $StorageById[$S->Id()] = $S;
}

xhtmlHeaders(__FILE__, translate('Events') );

?>
<body>
  <div id="page">
    <?php echo getNavBarHTML() ?>
    <div id="header">
      <div id="info">
        <h2><?php echo sprintf($CLANG['EventCount'], $nEvents, zmVlang($VLANG['Event'], $nEvents)) ?></h2>
        <a id="refreshLink" href="#" onclick="location.reload(true);"><?php echo translate('Refresh') ?></a>
      </div>
      <div id="pagination">
<?php
if ( $pagination ) {
?>
        <h2 class="pagination"><?php echo $pagination ?></h2>
<?php
}
?>
<?php
if ( $pages > 1 ) {
  if ( !empty($page) ) {
?>
        <a href="?view=<?php echo $view ?>&amp;page=0<?php echo $filterQuery ?><?php echo $sortQuery.$limitQuery ?>"><?php echo translate('ViewAll') ?></a>
<?php
  } else {
?>
        <a href="?view=<?php echo $view ?>&amp;page=1<?php echo $filterQuery ?><?php echo $sortQuery.$limitQuery ?>"><?php echo translate('ViewPaged') ?></a>
<?php
  }
}
?>
      </div>
      <div id="controls">
        <a href="#" onclick="window.history.back();return false;"><?php echo translate('Back') ?></a>
        <a id="timelineLink" href="?view=timeline<?php echo $filterQuery ?>"><?php echo translate('ShowTimeline') ?></a>
      </div>
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
        <table id="contentTable" class="major">
          <tbody>
<?php
$count = 0;
$disk_space_total = 0;

$results = dbQuery($eventsSql);
while ( $event_row = dbFetchNext($results) ) {
  $event = new Event($event_row);
  if ( $event_row['Archived'] )
    $archived = true;
  else
    $unarchived = true;

  if ( ($count++%ZM_WEB_EVENTS_PER_PAGE) == 0 ) {
?>
            <tr>
              <th class="colId"><a href="<?php echo sortHeader('Id') ?>"><?php echo translate('Id') ?><?php echo sortTag('Id') ?></a></th>
              <th class="colName"><a href="<?php echo sortHeader('Name') ?>"><?php echo translate('Name') ?><?php echo sortTag('Name') ?></a></th>
              <th class="colMonitor"><a href="<?php echo sortHeader('MonitorName') ?>"><?php echo translate('Monitor') ?><?php echo sortTag('MonitorName') ?></a></th>
              <th class="colCause"><a href="<?php echo sortHeader('Cause') ?>"><?php echo translate('Cause') ?><?php echo sortTag('Cause') ?></a></th>
              <th class="colTime"><a href="<?php echo sortHeader('StartTime') ?>"><?php echo translate('Time') ?><?php echo sortTag('StartTime') ?></a></th>
              <th class="colDuration"><a href="<?php echo sortHeader('Length') ?>"><?php echo translate('Duration') ?><?php echo sortTag('Length') ?></a></th>
              <th class="colFrames"><a href="<?php echo sortHeader('Frames') ?>"><?php echo translate('Frames') ?><?php echo sortTag('Frames') ?></a></th>
              <th class="colAlarmFrames"><a href="<?php echo sortHeader('AlarmFrames') ?>"><?php echo translate('AlarmBrFrames') ?><?php echo sortTag('AlarmFrames') ?></a></th>
              <th class="colTotScore"><a href="<?php echo sortHeader('TotScore') ?>"><?php echo translate('TotalBrScore') ?><?php echo sortTag('TotScore') ?></a></th>
              <th class="colAvgScore"><a href="<?php echo sortHeader('AvgScore') ?>"><?php echo translate('AvgBrScore') ?><?php echo sortTag('AvgScore') ?></a></th>
              <th class="colMaxScore"><a href="<?php echo sortHeader('MaxScore') ?>"><?php echo translate('MaxBrScore') ?><?php echo sortTag('MaxScore') ?></a></th>
<?php
    if ( count($storage_areas) > 1 ) { 
?>
              <th class="colStorage"><?php echo translate('Storage') ?></th>
<?php
    }
    if ( ZM_WEB_EVENT_DISK_SPACE ) {
?>
              <th class="colDiskSpace"><a href="<?php echo sortHeader('DiskSpace') ?>"><?php echo translate('DiskSpace') ?><?php echo sortTag('DiskSpace') ?></a></th>
<?php
    }
    if ( ZM_WEB_LIST_THUMBS ) {
?>
              <th class="colThumbnail"><?php echo translate('Thumbnail') ?></th>
<?php
    }
?>
              <th class="colMark"><input type="checkbox" name="toggleCheck" value="1" onclick="toggleCheckbox(this, 'markEids');"/></th>
            </tr>
<?php
  }
  $scale = max( reScale( SCALE_BASE, $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
            <tr<?php if ($event->Archived()) echo ' class="archived"' ?>>
              <td class="colId"><a href="?view=event&amp;eid=<?php echo $event->Id().$filterQuery.$sortQuery.'&amp;page=1"> '.$event->Id().($event->Archived()?'*':'') ?></a></td>
              <td class="colName"><a href="?view=event&amp;eid=<?php echo $event->Id().$filterQuery.$sortQuery.'&amp;page=1"> '.validHtmlStr($event->Name()).($event->Archived()?'*':'') ?></a></td>
              <td class="colMonitorName"><?php echo makePopupLink( '?view=monitor&amp;mid='.$event->MonitorId(), 'zmMonitor'.$event->Monitorid(), 'monitor', $event->MonitorName(), canEdit( 'Monitors' ) ) ?></td>
              <td class="colCause"><?php echo makePopupLink( '?view=eventdetail&amp;eid='.$event->Id(), 'zmEventDetail', 'eventdetail', validHtmlStr($event->Cause()), canEdit( 'Events' ), 'title="'.htmlspecialchars($event->Notes()).'"' ) ?></td>
              <td class="colTime"><?php echo strftime(STRF_FMT_DATETIME_SHORTER, strtotime($event->StartTime())) . 
( $event->EndTime() ? ' until ' . strftime(STRF_FMT_DATETIME_SHORTER, strtotime($event->EndTime()) ) : '' ) ?>
              </td>
              <td class="colDuration"><?php echo gmdate("H:i:s", $event->Length() ) ?></td>
              <td class="colFrames"><?php echo makePopupLink( '?view=frames&amp;eid='.$event->Id(), 'zmFrames', 'frames', $event->Frames() ) ?></td>
              <td class="colAlarmFrames"><?php echo makePopupLink( '?view=frames&amp;eid='.$event->Id(), 'zmFrames', 'frames', $event->AlarmFrames() ) ?></td>
              <td class="colTotScore"><?php echo $event->TotScore() ?></td>
              <td class="colAvgScore"><?php echo $event->AvgScore() ?></td>
              <td class="colMaxScore"><?php echo makePopupLink(
                '?view=frame&amp;eid='.$event->Id().'&amp;fid=0', 'zmImage',
                array('image', reScale($event->Width(), $scale), reScale($event->Height(), $scale)), $event->MaxScore()
              ); ?></td>
<?php
  if ( count($storage_areas) > 1 ) { 
?>
              <td class="colStorage"><?php echo isset($StorageById[$event->StorageId()]) ? $StorageById[$event->StorageId()]->Name() : '' ?></td>
          
<?php
  }
  if ( ZM_WEB_EVENT_DISK_SPACE ) {
    $disk_space_total += $event->DiskSpace();
?>
              <td class="colDiskSpace"><?php echo human_filesize($event->DiskSpace()) ?></td>
<?php
  }
  if ( ZM_WEB_LIST_THUMBS ) {
#Logger::Debug(print_r($thumbData,true));
      echo '<td class="colThumbnail">';
      $imgSrc = $event->getThumbnailSrc();
      $streamSrc = $event->getStreamSrc(array(
        'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single'));

      $imgHtml = '<img id="thumbnail'.$event->id().'" src="'.$imgSrc.'" alt="'. validHtmlStr('Event '.$event->Id()) .'" style="width:'. validInt($event->ThumbnailWidth()) .'px;height:'. validInt($event->ThumbnailHeight()).'px;" onmouseover="this.src=\''.$streamSrc.'\';" onmouseout="this.src=\''.$imgSrc.'\';"/>';
      echo '<a href="?view=event&amp;eid='. $event->Id().$filterQuery.$sortQuery.'&amp;page=1">'.$imgHtml.'</a>';
      echo '</td>';
  } // end if ZM_WEB_LIST_THUMBS
?>
              <td class="colMark"><input type="checkbox" name="markEids[]" value="<?php echo $event->Id() ?>" onclick="configureButton(this, 'markEids');"/></td>
            </tr>
<?php
}
?>
          </tbody>
<?php
  if ( ZM_WEB_EVENT_DISK_SPACE ) {
?>
          <tfoot>
            <tr>
              <td colspan="11">Totals:</td>
<?php
  if ( count($storage_areas)>1 ) {
?>
              <td class="colStorage"></td>
<?php
}
?>
              <td class="colDiskSpace"><?php echo human_filesize($disk_space_total) ?></td>
<?php
  if ( ZM_WEB_LIST_THUMBS ) {
?>
              <td></td>
<?php
}
?>
              <td></td>
            </tr>
          </tfoot>
<?php
  }
?>
        </table>
<?php
if ( $pagination ) {
?>
        <h3 class="pagination"><?php echo $pagination ?></h3>
<?php
}
?>
        <div id="contentButtons">
          <button type="button" name="viewBtn" value="View" onclick="viewEvents(this, 'markEids');" disabled="disabled">
          <?php echo translate('View') ?>
          </button>
          <button type="button" name="archiveBtn" value="Archive" onclick="archiveEvents(this, 'markEids')" disabled="disabled">
          <?php echo translate('Archive') ?>
          </button>
          <button type="button" name="unarchiveBtn" value="Unarchive" onclick="unarchiveEvents(this, 'markEids');" disabled="disabled">
          <?php echo translate('Unarchive') ?>
          </button>
          <button type="button" name="editBtn" value="Edit" onclick="editEvents(this, 'markEids')" disabled="disabled">
          <?php echo translate('Edit') ?>
          </button>
          <button type="button" name="exportBtn" value="Export" onclick="exportEvents(this, 'markEids')" disabled="disabled">
          <?php echo translate('Export') ?>
          </button>
          <button type="button" name="downloadBtn" value="DownloadVideo" onclick="downloadVideo(this, 'markEids')" disabled="disabled">
          <?php echo translate('DownloadVideo') ?>
          </button>
          <button type="button" name="deleteBtn" value="Delete" onclick="deleteEvents(this, 'markEids');" disabled="disabled">
          <?php echo translate('Delete') ?>
          </button>
        </div>
      </form>
    </div>
  </div>
<script type="text/javascript">
  // These are defined in the .js.php but need to be updated down here.
  archivedEvents = <?php echo !empty($archived)?'true':'false' ?>;
  unarchivedEvents = <?php echo !empty($unarchived)?'true':'false' ?>;
</script>
</body>
</html>
