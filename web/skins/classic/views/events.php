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
require_once('includes/Filter.php');

$eventsSql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultScale FROM Monitors AS M INNER JOIN Events AS E on (M.Id = E.MonitorId) WHERE';
if ( $user['MonitorIds'] ) {
  $user_monitor_ids = ' M.Id in ('.$user['MonitorIds'].')';
  $eventsSql .= $user_monitor_ids;
} else {
  $eventsSql .= ' 1';
}

parseSort();

$filter = ZM\Filter::parse($_REQUEST['filter']);
$filterQuery = $filter->querystring();
ZM\Logger::Debug("Filter ".print_r($filter, true));

if ( $filter->sql() ) {
  $eventsSql .= ' AND ('.$filter->sql().')';
}
$eventsSql .= " ORDER BY $sortColumn $sortOrder";
if ( $sortColumn != 'E.Id' ) $eventsSql .= ',E.Id '.$sortOrder;

$page = isset($_REQUEST['page']) ? validInt($_REQUEST['page']) : 0;
$limit = isset($_REQUEST['limit']) ? validInt($_REQUEST['limit']) : $filter['limit'];

if ( $_POST ) {
  // I think this is basically so that a refresh doesn't repost
  ZM\Logger::Debug('Redirecting to ' . $_SERVER['REQUEST_URI']);
  header('Location: ?view=' . $view.htmlspecialchars_decode($filterQuery).htmlspecialchars_decode($sortQuery).$limitQuery.'&page='.$page);
  exit();
}

$failed = !$filter->test_pre_sql_conditions();
if ( $failed ) {
  ZM\Logger::Debug("Pre conditions failed, not doing sql");
}

$results = $failed ? null : dbQuery($eventsSql);

$nEvents = $results ? $results->rowCount() : 0;
ZM\Logger::Debug("Pre conditions succeeded sql return $nEvents events");

if ( !empty($limit) && ($nEvents > $limit) ) {
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
  if ( empty($limit) ) {
    $limitAmount = ZM_WEB_EVENTS_PER_PAGE;
  } else {
    $limitLeft = $limit - $limitStart;
    $limitAmount = ($limitLeft>ZM_WEB_EVENTS_PER_PAGE)?ZM_WEB_EVENTS_PER_PAGE:$limitLeft;
  }
  $eventsSql .= " LIMIT $limitStart, $limitAmount";
} else if ( !empty($limit) ) {
  $eventsSql .= ' LIMIT 0, '.$limit;
}

$maxShortcuts = 5;
$pagination = getPagination($pages, $page, $maxShortcuts, $filterQuery.$sortQuery.$limitQuery);

$focusWindow = true;

$storage_areas = ZM\Storage::find();
$StorageById = array();
foreach ( $storage_areas as $S ) {
  $StorageById[$S->Id()] = $S;
}

xhtmlHeaders(__FILE__, translate('Events'));

?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page" class="container-fluid p-3">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
      <button id="tlineBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('ShowTimeline') ?>" ><i class="fa fa-history"></i></button>
      <button id="viewBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('View') ?>" disabled><i class="fa fa-binoculars"></i></button>
      <button id="archiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Archive') ?>" disabled><i class="fa fa-archive"></i></button>
      <button id="unarchiveBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Unarchive') ?>" disabled><i class="fa fa-file-archive-o"></i></button>
      <button id="editBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Edit') ?>" disabled><i class="fa fa-pencil"></i></button>
      <button id="exportBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Export') ?>" disabled><i class="fa fa-external-link"></i></button>
      <button id="downloadBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('DownloadVideo') ?>" disabled><i class="fa fa-download"></i></button>
      <button id="deleteBtn" class="btn btn-danger" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Delete') ?>" disabled><i class="fa fa-trash"></i></button>
    </div>

    <!-- Table styling handled by bootstrap-tables -->
    <div class="row justify-content-center">
      <table
        id="eventTable"
        data-toggle="table"
        data-pagination="true"
        data-show-pagination-switch="true"
        data-page-list="[10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="zmEventsTable"
        data-cookie-expire="2y"
        data-click-to-select="true"
        data-remember-order="true"
        data-show-columns="true"
        data-show-export="true"
        data-uncheckAll="true"
        data-toolbar="#toolbar"
        data-show-fullscreen="true"
        data-click-to-select="true"
        data-maintain-meta-data="true"
        data-mobile-responsive="true"
        data-buttons-class="btn btn-normal"
        class="table-sm table-borderless"
        style="display:none;"
      >
        <thead>
            <!-- Row styling is handled by bootstrap-tables -->
            <tr>
              <th data-sortable="false" data-field="toggleCheck" data-checkbox="true"></th>
              <th data-sortable="true" data-field="Id"><?php echo translate('Id') ?></th>
              <th data-sortable="true" data-field="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="true" data-field="Archived"><?php echo translate('Archived') ?></th>
              <th data-sortable="true" data-field="Emailed"><?php echo translate('Emailed') ?></th>
              <th data-sortable="true" data-field="Monitor"><?php echo translate('Monitor') ?></th>
              <th data-sortable="true" data-field="Cause"><?php echo translate('Cause') ?></th>
              <th data-sortable="true" data-field="AttrStartTime"><?php echo translate('AttrStartTime') ?></th>
              <th data-sortable="true" data-field="AttrEndTime"><?php echo translate('AttrEndTime') ?></th>
              <th data-sortable="true" data-field="Duration"><?php echo translate('Duration') ?></th>
              <th data-sortable="true" data-field="Frames"><?php echo translate('Frames') ?></th>
              <th data-sortable="true" data-field="AlarmBrFrames"><?php echo translate('AlarmBrFrames') ?></th>
              <th data-sortable="true" data-field="TotalBrScore"><?php echo translate('TotalBrScore') ?></th>
              <th data-sortable="true" data-field="AvgBrScore"><?php echo translate('AvgBrScore') ?></th>
              <th data-sortable="true" data-field="MaxBrScore"><?php echo translate('MaxBrScore') ?></th>
<?php
    if ( count($storage_areas) > 1 ) { 
?>
              <th data-sortable="true" data-field="Storage"><?php echo translate('Storage') ?></th>
<?php
    }
    if ( ZM_WEB_EVENT_DISK_SPACE ) {
?>
              <th data-sortable="true" data-field="DiskSpace"><?php echo translate('DiskSpace') ?></th>
<?php
    }
    if ( ZM_WEB_LIST_THUMBS ) {
?>
              <th data-sortable="false" data-field="Thumbnail"><?php echo translate('Thumbnail') ?></th>
<?php
    }
?>
            </tr>
           </thead>
           <tbody>
<?php
$count = 0;
$disk_space_total = 0;
if ( $results ) {

  while ( $event_row = dbFetchNext($results) ) {
    $event = new ZM\Event($event_row);

    if ( !$filter->test_post_sql_conditions($event) ) {
      ZM\Logger::Debug("Failed post conditions");
      continue;
    }

    $scale = max(reScale(SCALE_BASE, $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE), SCALE_BASE);
?>
            <tr<?php echo $event->Archived() ? ' class="archived"' : '' ?>>
              <td data-checkbox="true"></td>            
              <td><a href="?view=event&amp;eid=<?php echo $event->Id().$filterQuery.$sortQuery.'&amp;page=1">'.$event->Id() ?></a></td>

              <td><a href="?view=event&amp;eid=<?php echo $event->Id().$filterQuery.$sortQuery.'&amp;page=1">'.validHtmlStr($event->Name())?></a>
              <?php 
              $archived = $event->Archived() ? translate('Archived') : '';
              $emailed = $event->Emailed() ? ' '.translate('Emailed') : '';
              echo '<br/><div class="small text-nowrap text-muted">'.$archived.$emailed.'</div>';
              ?>
              </td>

              <td class="text-center"><?php echo ( $event->Archived() ) ? 'Yes' : 'No' ?></td>
              <td class="text-center"><?php echo ( $event->Emailed() ) ? 'Yes' : 'No' ?></td>
              <td><?php echo makePopupLink( '?view=monitor&amp;mid='.$event->MonitorId(), 'zmMonitor'.$event->MonitorId(), 'monitor', $event->MonitorName(), canEdit( 'Monitors' ) ) ?></td>
              <td><?php echo makePopupLink( '?view=eventdetail&amp;eid='.$event->Id(), 'zmEventDetail', 'eventdetail', validHtmlStr($event->Cause()), canEdit( 'Events' ), 'title="'.htmlspecialchars($event->Notes()).'"' ) ?>
              <?php
              # display notes as small text
              if ( $event->Notes() ) {
                # if notes include detection objects, then link it to objdetect.jpg
                if ( strpos($event->Notes(), 'detected:') !== false ) {
                  # make a link
                  echo makePopupLink( '?view=image&amp;eid='.$event->Id().'&amp;fid=objdetect', 'zmImage',
                  array('image', reScale($event->Width(), $scale), reScale($event->Height(), $scale)),
                  '<div class="small text-nowrap text-muted"><u>'.$event->Notes().'</u></div>');
                } else if ( $event->Notes() != 'Forced Web: ' ) {
                  echo '<br/><div class="small text-nowrap text-muted">'.$event->Notes().'</div>';
                }
              }
              ?>
              </td>
              
              <td><?php echo strftime(STRF_FMT_DATETIME_SHORTER, strtotime($event->StartTime())) ?></td>
              <td><?php echo strftime(STRF_FMT_DATETIME_SHORTER, strtotime($event->EndTime())) ?></td>
              <td><?php echo gmdate('H:i:s', $event->Length() ) ?></td>
              <td><a href="?view=frames&amp;eid=<?php echo $event->Id() ?>"><?php echo $event->Frames() ?></a></td>
              <td><a href="?view=frames&amp;eid=<?php echo $event->Id() ?>"><?php echo $event->AlarmFrames() ?></a></td>
              <td><?php echo $event->TotScore() ?></td>
              <td><?php echo $event->AvgScore() ?></td>
              <td><?php echo makePopupLink(
                '?view=frame&amp;eid='.$event->Id().'&amp;fid=0', 'zmImage',
                array('image', reScale($event->Width(), $scale), reScale($event->Height(), $scale)), $event->MaxScore()
              ); ?></td>
<?php
  if ( count($storage_areas) > 1 ) { 
?>
              <td>
<?php
    if ( $event->StorageId() ) {
      echo isset($StorageById[$event->StorageId()]) ? $StorageById[$event->StorageId()]->Name() : 'Unknown Storage Id: '.$event->StorageId();
    } else {
      echo 'Default';
    }
    if ( $event->SecondaryStorageId() ) {
      echo '<br/>'.(isset($StorageById[$event->SecondaryStorageId()]) ? $StorageById[$event->SecondaryStorageId()]->Name() : 'Unknown Storage Id '.$event->SecondaryStorageId());
    }
 ?>
</td>
          
<?php
  }
  if ( ZM_WEB_EVENT_DISK_SPACE ) {
    $disk_space_total += $event->DiskSpace();
?>
              <td class="colDiskSpace"><?php echo human_filesize($event->DiskSpace()) ?></td>
<?php
  }
  if ( ZM_WEB_LIST_THUMBS ) {
      echo '<td class="colThumbnail zoom">';
      $imgSrc = $event->getThumbnailSrc(array(),'&amp;');
      $streamSrc = $event->getStreamSrc(array(
        'mode'=>'jpeg', 'scale'=>$scale, 'maxfps'=>ZM_WEB_VIDEO_MAXFPS, 'replay'=>'single', 'rate'=>'400'), '&amp;');

      $imgHtml = '<img id="thumbnail'.$event->Id().'" src="'.$imgSrc.'" alt="'. validHtmlStr('Event '.$event->Id()) .'" style="width:'. validInt($event->ThumbnailWidth()) .'px;height:'. validInt($event->ThumbnailHeight()).'px;" stream_src="'.$streamSrc.'" still_src="'.$imgSrc.'"/>';
      echo '<a href="?view=event&amp;eid='. $event->Id().$filterQuery.$sortQuery.'&amp;page=1">'.$imgHtml.'</a>';
      echo '</td>';
  } // end if ZM_WEB_LIST_THUMBS
?>
            </tr>
<?php
}
?>
          </tbody>
<?php
} # end if $results
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
      </div>       
  </div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirm" class="modal fade" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Delete Confirmation</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><?php echo translate('ConfirmDeleteEvents') ?></p>
      </div>
      <div class="modal-footer">
        <button id="delCancelBtn" type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo translate('Cancel') ?></button>
        <button id ="delConfirmBtn" type="button" class="btn btn-danger"><?php echo translate('Delete') ?></button>
      </div>
    </div>
  </div>
</div>

</body>
</html>
