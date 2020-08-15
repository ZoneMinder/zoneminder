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
  $eventsSql .= " LIMIT $limitStart, $limitAmount";
} elseif ( !empty($limit) ) {
  $eventsSql .= ' LIMIT 0, '.$limit;
}

$maxShortcuts = 5;
$pagination = getPagination($pages, $page, $maxShortcuts, $filterQuery.$sortQuery.$limitQuery);

$focusWindow = true;

if ( $_POST ) {
  // I think this is basically so that a refresh doesn't repost
  ZM\Logger::Debug('Redirecting to ' . $_SERVER['REQUEST_URI']);
  header('Location: ?view=' . $view.htmlspecialchars_decode($filterQuery).htmlspecialchars_decode($sortQuery).$limitQuery.'&page='.$page);
  exit();
}

$storage_areas = ZM\Storage::find();
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
        <a id="refreshLink" href="#"><?php echo translate('Refresh') ?></a>
        <a id="timelineLink" href="?view=timeline<?php echo $filterQuery ?>"><?php echo translate('ShowTimeline') ?></a>
        <a href="#" id="backLink"><?php echo translate('Back') ?></a>
    </div>
    
    <div id="toolbar">
      <button id="viewBtn" class="btn btn-primary btn-sm" disabled><i class="fa fa-binoculars"></i> View</button>
      <button id="archiveBtn" class="btn btn-primary btn-sm" disabled><i class="fa fa-archive"></i> Archive</button>
      <button id="unarchiveBtn" class="btn btn-primary btn-sm" disabled><i class="fa fa-file-archive-o"></i> Unarchive</button>
      <button id="editBtn" class="btn btn-primary btn-sm" disabled><i class="fa fa-pencil"></i> Edit</button>
      <button id="exportBtn" class="btn btn-primary btn-sm" disabled><i class="fa fa-external-link"></i> Export</button>
      <button id="downloadBtn" class="btn btn-primary btn-sm" disabled><i class="fa fa-download"></i> Download Video</button>
      <button id="deleteBtn" class="btn btn-danger btn-sm" disabled><i class="fa fa-trash"></i> Delete</button>
    </div>

    <div class="table-responsive-sm p-3">
      <table
        id="eventTable"
        data-toggle="table"
        data-pagination="true"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="zmEventTable"
        data-click-to-select="true"
        data-remember-order="true"
        data-show-columns="true"
        data-uncheckAll="true"
        data-toolbar="#toolbar"
        data-show-fullscreen="true"
        data-click-to-select="true"
        data-maintain-meta-data="true"
        data-mobile-responsive="true"
        class="table-sm table-borderless">
        <thead>
<?php
$count = 0;
$disk_space_total = 0;

$results = dbQuery($eventsSql);
while ( $event_row = dbFetchNext($results) ) {
  $event = new ZM\Event($event_row);
  if ( $event_row['Archived'] )
    $archived = true;
  else
    $unarchived = true;

  if ( ($count++%ZM_WEB_EVENTS_PER_PAGE) == 0 ) {
?>
            <tr>
              <th data-sortable="false" data-field="toggleCheck" data-field="state" data-checkbox="true" name="toggleCheck" value="1" data-checkbox-name="eids[]" data-on-click-this="updateFormCheckboxesByName"></th>            
              <th data-sortable="true" data-field="Id"><?php echo translate('Id') ?></th>
              <th data-sortable="true" data-field="Name"><?php echo translate('Name') ?></th>
              <th data-sortable="true" data-field="Archived"><?php echo translate('Archived') ?></th>
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
  }
  $scale = max( reScale( SCALE_BASE, $event->DefaultScale(), ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
?>
            <tr<?php echo ( $event->Archived() ) ? ' class="archived"' : '' ?>>
              <td data-checkbox="true"></td>            
              <td><a href="?view=event&amp;eid=<?php echo $event->Id().$filterQuery.$sortQuery.'&amp;page=1">'.$event->Id() ?></a></td>
              <td><a href="?view=event&amp;eid=<?php echo $event->Id().$filterQuery.$sortQuery.'&amp;page=1">'.validHtmlStr($event->Name()).($event->Archived()?'*':'') ?></a><br/>
<?php
							if ( $event->Emailed() )
								echo 'Emailed ';
?>
							</td>
              <td><?php echo ( $event->Archived() ) ? 'Yes' : 'No' ?></td>
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
              <td><?php echo strftime(STRF_FMT_DATETIME_SHORTER, strtotime($event->EndTime()) ) ?></td>
              <td><?php echo gmdate("H:i:s", $event->Length() ) ?></td>
              <td><?php echo makePopupLink( '?view=frames&amp;eid='.$event->Id(), 'zmFrames', 
              ( ZM_WEB_LIST_THUMBS ? array('frames', ZM_WEB_LIST_THUMB_WIDTH, ZM_WEB_LIST_THUMB_HEIGHT) : 'frames'),
              $event->Frames() ) ?></td>
              <td><?php echo makePopupLink( '?view=frames&amp;eid='.$event->Id(), 'zmFrames',
              ( ZM_WEB_LIST_THUMBS ? array('frames', ZM_WEB_LIST_THUMB_WIDTH, ZM_WEB_LIST_THUMB_HEIGHT) : 'frames'),
              $event->AlarmFrames() ) ?></td>
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
<script nonce="<?php echo $cspNonce;?>">
  // These are defined in the .js.php but need to be updated down here.
// This might be better done by selecting through the dom for the archived class
  archivedEvents = <?php echo !empty($archived)?'true':'false' ?>;
  unarchivedEvents = <?php echo !empty($unarchived)?'true':'false' ?>;
</script>
</body>
</html>
