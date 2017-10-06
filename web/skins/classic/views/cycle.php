<?php
//
// ZoneMinder web cycle view file, $Date$, $Revision$
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

if ( !canView( 'Stream' ) ) {
  $view = 'error';
  return;
}

if ( empty($_REQUEST['mode']) ) {
  if ( canStream() )
    $mode = 'stream';
  else
    $mode = 'still';
} else {
  $mode = validHtmlStr($_REQUEST['mode']);
}

$group_id = 0;
if ( isset($_REQUEST['group']) ) { 
  $group_id = $_REQUEST['group'];
} else if ( isset($_COOKIE['zmGroup'] ) ) { 
  $group_id = $_COOKIE['zmGroup'];
} 

$subgroup_id = 0;
if ( isset($_REQUEST['subgroup']) ) { 
  $subgroup_id = $_REQUEST['subgroup'];
} else if ( isset($_COOKIE['zmSubGroup'] ) ) { 
  $subgroup_id = $_COOKIE['zmSubGroup'];
} 
$groupIds = null;
if ( $group_id ) {
$groupIds = array();
  if ( $group = dbFetchOne( 'SELECT MonitorIds FROM Groups WHERE Id = ?', NULL, array($group_id) ) )
    if ( $group['MonitorIds'] )
      $groupIds = explode( ',', $group['MonitorIds'] );
  if ( $subgroup_id ) {
    if ( $group = dbFetchOne( 'SELECT MonitorIds FROM Groups WHERE Id = ?', NULL, array($subgroup_id) ) )
      if ( $group['MonitorIds'] )
        $groupIds = array_merge( $groupIds, explode( ',', $group['MonitorIds'] ) );
  } else {
    foreach ( dbFetchAll( 'SELECT MonitorIds FROM Groups WHERE ParentId = ?', NULL, array($group_id) ) as $group )
      if ( $group['MonitorIds'] )
        $groupIds = array_merge( $groupIds, explode( ',', $group['MonitorIds'] ) );
  }
}
$groupSql = '';
if ( $groupIds )
  $groupSql = " and find_in_set( Id, '".implode( ',', $groupIds )."' )";

$sql = "SELECT * FROM Monitors WHERE Function != 'None'$groupSql ORDER BY Sequence";
$monitors = array();
$monIdx = 0;
foreach( dbFetchAll( $sql ) as $row ) {
  if ( !visibleMonitor( $row['Id'] ) )
    continue;
  if ( isset($_REQUEST['mid']) && $row['Id'] == $_REQUEST['mid'] )
    $monIdx = count($monitors);
  $row['ScaledWidth'] = reScale( $row['Width'], $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
  $row['ScaledHeight'] = reScale( $row['Height'], $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
  $monitors[] = new Monitor( $row );
}

if ( $monitors ) {
  $monitor = $monitors[$monIdx];
  $nextMid = $monIdx==(count($monitors)-1)?$monitors[0]->Id():$monitors[$monIdx+1]->Id();
  $montageWidth = $monitor->ScaledWidth();
  $montageHeight = $monitor->ScaledHeight();
  $widthScale = ($montageWidth*SCALE_BASE)/$monitor->Width();
  $heightScale = ($montageHeight*SCALE_BASE)/$monitor->Height();
  $scale = (int)(($widthScale<$heightScale)?$widthScale:$heightScale);
}

noCacheHeaders();
xhtmlHeaders(__FILE__, translate('CycleWatch') );
?>
<body>
  <div id="page">
<?php echo $navbar = getNavBarHTML(); ?>
    <div id="header">
      <div id="headerButtons">
<?php if ( $mode == "stream" ) { ?>
        <a href="?view=<?php echo $view ?>&amp;mode=still&amp;mid=<?php echo $monitor ? $monitor->Id() : '' ?>"><?php echo translate('Stills') ?></a>
<?php } else { ?>
        <a href="?view=<?php echo $view ?>&amp;mode=stream&amp;mid=<?php echo $monitor ? $monitor->Id() : '' ?>"><?php echo translate('Stream') ?></a>
<?php } ?>
      </div>
      <div class="controlHeader">
        <span id="groupControl"><label><?php echo translate('Group') ?>:</label>
<?php
  
  $groups = array(0=>'All');
  foreach ( Group::find_all( array('ParentId'=>null) ) as $Group ) { 
    $groups[$Group->Id()] = $Group->Name();
  } 
  echo htmlSelect( 'group', $groups, $group_id, 'changeGroup(this);' );
  $groups = array(0=>'All');
  if ( $group_id ) { 
    foreach ( Group::find_all( array('ParentId'=>$group_id) ) as $Group ) { 
      $groups[$Group->Id()] = $Group->Name();
    } 
  } 
  echo htmlSelect( 'subgroup', $groups, $subgroup_id, 'changeSubGroup(this);' );
?>
      </div>
    </div>
    <div id="content">
      <div id="imageFeed">
      <?php 
        if ( $monitor ) {
          echo getStreamHTML( $monitor, array( 'scale'=>$scale, 'mode'=>$mode ) );
        } else {
          echo "There are no monitors to view.";
        }
      ?>
      </div>
    </div>
  </div>
<?php xhtmlFooter() ?>
