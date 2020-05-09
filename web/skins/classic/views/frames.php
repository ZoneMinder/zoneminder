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
  <div id="page">
    <div id="header">
      <div id="headerButtons"><a href="#" data-on-click="closeWindow"><?php echo translate('Close') ?></a></div>
      <h2><?php echo translate('Frames') ?> - <?php echo $Event->Id() ?></h2>
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
        <a href="?view=<?php echo $view ?>&amp;page=0<?php echo $totalQuery ?>"><?php echo translate('ViewAll') ?></a>
<?php
  } else {
?>
        <a href="?view=<?php echo $view ?>&amp;page=1<?php echo $totalQuery ?>"><?php echo translate('ViewPaged') ?></a>
<?php
  }
}
?>
      </div>      
    </div>
    <div id="content">
      <form name="contentForm" id="contentForm" method="get" action="?">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="view" value="<?php echo $view ?>"/>
        <input type="hidden" name="action" value=""/>
        <input type="hidden" name="page" value="<?php echo $page ?>"/>
        <input type="hidden" name="eid" value="<?php echo $eid ?>"/>
        <?php echo $_REQUEST['filter']['fields'] ?>
        <input type="hidden" name="sort_field" value="<?php echo validHtmlStr($_REQUEST['sort_field']) ?>"/>
        <input type="hidden" name="sort_asc" value="<?php echo validHtmlStr($_REQUEST['sort_asc']) ?>"/>
        <input type="hidden" name="limit" value="<?php echo $limit ?>"/>
        <table id="contentTable" class="major">
          <thead>
            <tr>
            <th class="colId"><a href="<?php echo sortHeader('FramesFrameId') ?>"><?php echo translate('Frame Id') ?><?php echo sortTag('FramesFrameId') ?></a></th>
            <th class="colType"><a href="<?php echo sortHeader('FramesType') ?>"><?php echo translate('Type') ?><?php echo sortTag('FramesType') ?></a></th>
            <th class="colTimeStamp"><a href="<?php echo sortHeader('FramesTimeStamp') ?>"><?php echo translate('TimeStamp') ?><?php echo sortTag('FramesTimeStamp') ?></a></th>
            <th class="colTimeDelta"><a href="<?php echo sortHeader('FramesDelta') ?>"><?php echo translate('TimeDelta') ?><?php echo sortTag('FramesDelta') ?></a></th>
            <th class="colScore"><a href="<?php echo sortHeader('FramesScore') ?>"><?php echo translate('Score') ?><?php echo sortTag('FramesScore') ?></a></th>
<?php
        if ( ZM_WEB_LIST_THUMBS ) {
?>
              <th class="colThumbnail"><?php echo translate('Thumbnail') ?></th>
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

    $class = strtolower($frame['Type']);
?>
            <tr class="<?php echo $class ?>">
              <td class="colId"><?php echo makePopupLink(
                '?view=frame&amp;eid='.$Event->Id().'&amp;fid='.$frame['FrameId'], 'zmImage',
                array(
                  'frame',
                  ($scale ? $Event->Width()*$scale/100 : $Event->Width()),
                  ($scale ? $Event->Height()*$scale/100 : $Event->Height())
                ),
                $frame['FrameId'])
              ?></td>
              <td class="colType"><?php echo $frame['Type'] ?></td>
              <td class="colTimeStamp"><?php echo strftime(STRF_FMT_TIME, $frame['UnixTimeStamp']) ?></td>
              <td class="colTimeDelta"><?php echo number_format( $frame['Delta'], 2 ) ?></td>
<?php
    if ( ZM_RECORD_EVENT_STATS && ($frame['Type'] == 'Alarm') ) {
?>
              <td class="colScore"><?php echo makePopupLink('?view=stats&amp;eid='.$Event->Id().'&amp;fid='.$frame['FrameId'], 'zmStats', 'stats', $frame['Score']) ?></td>
<?php
    } else {
?> 
              <td class="colScore"><?php echo $frame['Score'] ?></td>
<?php
    }
    if ( ZM_WEB_LIST_THUMBS ) {
?>
              <td class="colThumbnail"><?php echo makePopupLink( '?view=frame&amp;eid='.$Event->Id().'&amp;fid='.$frame['FrameId'], 'zmImage', array('image', $Event->Width(), $Event->Height()), '<img src="?view=image&amp;fid='.$Frame->Id().'&amp;'.
(ZM_WEB_LIST_THUMB_WIDTH?'width='.ZM_WEB_LIST_THUMB_WIDTH.'&amp;':'').
(ZM_WEB_LIST_THUMB_HEIGHT?'height='.ZM_WEB_LIST_THUMB_HEIGHT.'&amp;':'').'filename='.$Event->MonitorId().'_'.$frame['EventId'].'_'.$frame['FrameId'].'.jpg" '.
(ZM_WEB_LIST_THUMB_WIDTH?'width="'.ZM_WEB_LIST_THUMB_WIDTH.'" ':'').
(ZM_WEB_LIST_THUMB_HEIGHT?'height="'.ZM_WEB_LIST_THUMB_HEIGHT.'" ':'').' alt="'.$frame['FrameId'].'"/>' ) ?></td>
<?php
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

<?php
if ( $pagination ) {
?>
        <h3 class="pagination"><?php echo $pagination ?></h3>
<?php
}
?>

        <div id="contentButtons">
        </div>
      </form>
    </div>
  </div>
</body>
</html>
