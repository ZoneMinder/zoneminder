<?php
//
// ZoneMinder web event view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
    $_REQUEST['view'] = "error";
    return;
}

$midSql = '';
if ( $user['MonitorIds'] )
{
    $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}

$sql = 'select E.*,M.Name as MonitorName from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = ?'.$midSql;
$event = dbFetchOne( $sql, NULL, array($_REQUEST['eid']) );

if ( !empty($_REQUEST['fid']) )
{
    $frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventID = ? AND FrameId = ?', NULL, array($_REQUEST['eid'],$_REQUEST['fid']) );
}
elseif ( isset($_REQUEST['fid']) )
{
    $frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventID = ? AND Score = ?', NULL, array($_REQUEST['eid'],$event['MaxScore']) );
    $_REQUEST['fid'] = $frame['FrameId'];
}

parseSort( true, '&amp;' );
parseFilter( $_REQUEST['filter'], true, '&amp;' );
$filterQuery = $_REQUEST['filter']['query'];

if ( $sortOrder=='asc' ) {
	$sql = "select E.* from Events as E inner join Monitors as M on E.MonitorId = M.Id where $sortColumn <= ?".$_REQUEST['filter']['sql'].$midSql." order by $sortColumn desc";
} else {
	$sql = "select E.* from Events as E inner join Monitors as M on E.MonitorId = M.Id where $sortColumn >= ?".$_REQUEST['filter']['sql'].$midSql." order by $sortColumn asc";
} 
$result = dbQuery( $sql, array( $event[$_REQUEST['sort_field']] ) );
while ( $row = dbFetchNext( $result ) )
{
    if ( $row['Id'] == $_REQUEST['eid'] )
    {
        $prevEvent = dbFetchNext( $result );
        break;
    }
}

$sql = "select E.* from Events as E inner join Monitors as M on E.MonitorId = M.Id where $sortColumn ".($sortOrder=='asc'?'>=':'<=').' ?'.$_REQUEST['filter']['sql'].$midSql." order by $sortColumn $sortOrder";
$result = dbQuery( $sql, array($event[$_REQUEST['sort_field']]) );
while ( $row = dbFetchNext( $result ) )
{
    if ( $row['Id'] == $_REQUEST['eid'] )
    {
        $nextEvent = dbFetchNext( $result );
        break;
    }
}

$framesPerPage = 15;
$framesPerLine = 3;
$maxShortcuts = 3;

$paged = $event['Frames'] > $framesPerPage;

if ( $paged && !empty($_REQUEST['page']) )
{
    $loFrameId = (($_REQUEST['page']-1)*$framesPerPage)+1;
    $hiFrameId = min( $_REQUEST['page']*$framesPerPage, $event['Frames'] );
}
else
{
    $loFrameId = 1;
    $hiFrameId = $event['Frames'];
}

$sql = 'SELECT * FROM Frames WHERE EventID = ?';
if ( $paged && !empty($_REQUEST['page']) )
    $sql .= " and FrameId between $loFrameId and $hiFrameId";
$sql .= " order by FrameId";
$frames = dbFetchAll( $sql, NULL, array( $_REQUEST['eid'] ) );

$scale = getDeviceScale( $event['Width'], $event['Height'], $framesPerLine+0.3 );

$pages = (int)ceil($event['Frames']/$framesPerPage);
if ( !empty($_REQUEST['fid']) )
    $_REQUEST['page'] = ($_REQUEST['fid']/$framesPerPage)+1;

$pagination = getPagination( $pages, $_REQUEST['page'], $maxShortcuts, '&amp;eid='.$_REQUEST['eid'].$filterQuery.$sortQuery, '&amp;' );

xhtmlHeaders( __FILE__, $SLANG['Event'].' - '.$event['Name'] );
?>
<body>
  <div id="page">
    <div id="header">
<?php
if ( canEdit( 'Events' ) )
{
?>
      <div id="headerButtons">
        <a href="?view=events&amp;action=delete&amp;mark_eid=<?= $_REQUEST['eid'] ?><?= $filterQuery ?><?= $sortQuery ?>&amp;limit=<?= $_REQUEST['limit'] ?>&amp;page=<?= $_REQUEST['page'] ?>"><?= $SLANG['Delete'] ?></a>
      </div>
<?php
}
?>
      <h2><?= makeLink( '?view=eventdetails&amp;eid='.$_REQUEST['eid'], $event['Name'].($event['Archived']?'*':''), canEdit( 'Events' ) ) ?></h2>
    </div>
    <div id="content">
<?php
if ( $pagination )
{
?>
      <h3 class="pagination"><?= $pagination ?></h3>
<?php
}
?>
      <div id="eventFrames">
<?php
foreach ( $frames as $frame )
{
    $imageData = getImageSrc( $event, $frame, $scale );
?>
       <a href="?view=frame&amp;eid=<?= $_REQUEST['eid'] ?>&amp;fid=<?= $frame['FrameId'] ?>"><img src="<?= viewImagePath( $imageData['thumbPath'] ) ?>" class="<?= $imageData['imageClass'] ?>" alt="<?= $frame['Type'] ?>/<?= $frame['Type']=='Alarm'?$frame['Score']:0 ?>"/></a>
<?php
}
?>
      </div>
    </div>
  </div>
</body>
</html>
