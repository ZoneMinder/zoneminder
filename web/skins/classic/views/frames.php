<?php
//
// ZoneMinder web frames view file, $Date$, $Revision$
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

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

require_once('includes/Frame.php');
$eid = validInt($_REQUEST['eid']);
$Event = new ZM\Event($eid);
$Monitor = $Event->Monitor();

$countSql = 'SELECT COUNT(*) AS FrameCount FROM Frames AS F WHERE 1 ';
$frameSql = 'SELECT *, unix_timestamp( TimeStamp ) AS UnixTimeStamp FROM Frames AS F WHERE 1 ';

// override the sort_field handling in parseSort for frames
if ( empty($_REQUEST['sort_field']) )
  $_REQUEST['sort_field'] = 'FramesTimeStamp';

if ( !isset($_REQUEST['sort_asc']) )
  $_REQUEST['sort_asc'] = true;

if ( ! isset($_REQUEST['filter'])){
  // generate a dummy filter from the eid for pagination
  $_REQUEST['filter'] = array('Query' => array( 'terms' => array( ) ) );
  $_REQUEST['filter'] = addFilterTerm(
    $_REQUEST['filter'],
    0,
    array( 'cnj' => 'and', 'attr' => 'FramesEventId', 'op' => '=', 'val' => $eid )
  );
}

parseSort();
parseFilter($_REQUEST['filter']);
$filterQuery = $_REQUEST['filter']['query'];

if ( $_REQUEST['filter']['sql'] ) {
  $countSql .= $_REQUEST['filter']['sql'];
  $frameSql .= $_REQUEST['filter']['sql'];
}

$frameSql .= " ORDER BY $sortColumn $sortOrder,Id $sortOrder";

if ( isset( $_REQUEST['scale'] ) ) {
  $scale = validNum($_REQUEST['scale']);
} else if ( isset( $_COOKIE['zmWatchScale'.$Monitor->Id()] ) ) {
  $scale = validNum($_COOKIE['zmWatchScale'.$Monitor->Id()]);
} else if ( isset( $_COOKIE['zmWatchScale'] ) ) {
  $scale = validNum($_COOKIE['zmWatchScale']);
} else {
  $scale = max(reScale(SCALE_BASE, $Monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE), SCALE_BASE);
}

$page = isset($_REQUEST['page']) ? validInt($_REQUEST['page']) : 1;
$limit = isset($_REQUEST['limit']) ? validInt($_REQUEST['limit']) : 0;

$nFrames = dbFetchOne($countSql, 'FrameCount');

if ( !empty($limit) && ($nFrames > $limit) ) {
  $nFrames = $limit;
}

$pages = (int)ceil($nFrames/ZM_WEB_EVENTS_PER_PAGE);

if ( !empty($page) ) {
  if ( $page <= 0 )
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
  $frameSql .= " limit $limitStart, $limitAmount";
} elseif ( !empty($limit) ) {
  $frameSql .= ' limit 0, '.$limit;
}

$maxShortcuts = 5;
$totalQuery = $sortQuery.'&amp;eid='.$eid.$limitQuery.$filterQuery;
$pagination = getPagination($pages, $page, $maxShortcuts, $totalQuery);

$frames = dbFetchAll($frameSql);

$focusWindow = true;

xhtmlHeaders(__FILE__, translate('Frames').' - '.$Event->Id());
?>
<body>
  <?php echo getNavBarHTML() ?>
  <div id="page" class="container-fluid p-3">
    <!-- Toolbar button placement and styling handled by bootstrap-tables -->
    <div id="toolbar">
      <button id="backBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Back') ?>" disabled><i class="fa fa-arrow-left"></i></button>
      <button id="refreshBtn" class="btn btn-normal" data-toggle="tooltip" data-placement="top" title="<?php echo translate('Refresh') ?>" ><i class="fa fa-refresh"></i></button>
    </div>

    <!-- Table styling handled by bootstrap-tables -->
    <div class="row justify-content-center">      
      <table
        id="framesTable"
        data-toggle="table"
        data-pagination="true"
        data-show-pagination-switch="true"
        data-page-list="[10, 25, 50, 100, 200, All]"
        data-search="true"
        data-cookie="true"
        data-cookie-id-table="zmFramesTable"
        data-cookie-expire="2y"
        data-remember-order="true"
        data-show-columns="true"
        data-show-export="true"
        data-toolbar="#toolbar"
        data-show-fullscreen="true"
        data-maintain-meta-data="true"
        data-mobile-responsive="true"
        data-buttons-class="btn btn-normal"
        data-detail-view="true"
        data-detail-formatter="detailFormatter"
        data-show-toggle="true"
        class="table-sm table-borderless">

        <thead>
          <!-- Row styling is handled by bootstrap-tables -->
          <tr>
            <th data-align="center" data-sortable="false" data-field="EventId"><?php echo translate('EventId') ?></th>
            <th data-align="center" data-sortable="true" data-field="FramesId"><?php echo translate('FrameId') ?></th>
            <th data-align="center" data-sortable="true" data-field="FramesType"><?php echo translate('Type') ?></th>
            <th data-align="center" data-sortable="true" data-field="FramesTimeStamp"><?php echo translate('TimeStamp') ?></th>
            <th data-align="center" data-sortable="true" data-field="FramesDelta"><?php echo translate('TimeDelta') ?></th>
            <th data-align="center" data-sortable="true" data-field="FramesScore"><?php echo translate('Score') ?></th>
<?php
        if ( ZM_WEB_LIST_THUMBS ) {
?>
            <th data-align="center" data-sortable="false" data-field="Thumbnail"><?php echo translate('Thumbnail') ?></th>
<?php
        }
?>
          </tr>
        </thead>
        <tbody>
<?php
if ( count($frames) ) {
  foreach ( $frames as $frame ) {
    $Frame = new ZM\Frame($frame);
?>
            <tr<?php echo ( strtolower($frame['Type']) == "alarm" ) ? ' class="alarm"' : '' ?>>
              <td><?php echo $frame['EventId'] ?></td>
              <td><?php echo $frame['FrameId'] ?></td>
              <td><?php echo $frame['Type'] ?></td>
              <td><?php echo strftime(STRF_FMT_TIME, $frame['UnixTimeStamp']) ?></td>
              <td><?php echo number_format( $frame['Delta'], 2 ) ?></td>
              <td><?php echo $frame['Score'] ?></td>
<?php
    if ( ZM_WEB_LIST_THUMBS ) {
      $base_img_src = '?view=image&amp;fid=' .$Frame->Id();
      $thmb_width = ZM_WEB_LIST_THUMB_WIDTH ? 'width='.ZM_WEB_LIST_THUMB_WIDTH : '';
      $thmb_height = ZM_WEB_LIST_THUMB_HEIGHT ? 'height='.ZM_WEB_LIST_THUMB_HEIGHT : '';
      $thmb_fn = 'filename=' .$Event->MonitorId(). '_' .$frame['EventId']. '_' .$frame['FrameId']. '.jpg';
      $img_src = join('&amp;', array_filter(array($base_img_src, $thmb_width, $thmb_height, $thmb_fn)));
      $full_img_src = join('&amp;', array_filter(array($base_img_src, $thmb_fn)));
      $frame_src = '?view=frame&amp;eid=' .$Event->Id(). '&amp;fid=' .$frame['FrameId'];
      
      echo '<td class="colThumbnail zoom"><img src="' .$img_src. '" '.$thmb_width. ' ' .$thmb_height. 'img_src="' .$img_src. '" full_img_src="' .$full_img_src. '"></td>'.PHP_EOL;
    }
?>
            </tr>
<?php
  } // end foreach frame
} else {
?>
            <tr>
              <td colspan="5"><?php echo translate('NoFramesRecorded') ?></td>
            </tr>
<?php
}
?>
          </tbody>
        </table>
      </div>
  </div>
<!-- Load the statistics for each frame -->
<!-- This content gets hidden on init and only revailed on detail view -->
<?php
$row=0;
if ( count($frames) ) foreach ( $frames as $frame ) {
  $eid = $frame['EventId'];
  $fid = $frame['FrameId'];
  include('_stats_table.php');
  $row++;
}
?>
</body>
</html>
