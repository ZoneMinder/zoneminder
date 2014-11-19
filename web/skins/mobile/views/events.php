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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

$sql = "select * from Monitors";
foreach ( dbFetchAll( $sql ) as $row )
{
    $monitors[$row['Id']] = $row;
}

if ( isset($_REQUEST['filterName']) )
{
    $dbFilter = dbFetchOne( 'SELECT * FROM Filters WHERE Name = ?', NULL, array($_REQUEST['filterName']) );
    $_REQUEST['filter'] = jsonDecode( $dbFilter['Query'] );
    $_REQUEST['sort_field'] = isset($_REQUEST['filter']['sort_field'])?$_REQUEST['filter']['sort_field']:"DateTime";
    $_REQUEST['sort_asc'] = isset($_REQUEST['filter']['sort_asc'])?$_REQUEST['filter']['sort_asc']:"1";
    $_REQUEST['limit'] = isset($_REQUEST['filter']['limit'])?$_REQUEST['filter']['limit']:"";
    unset( $_REQUEST['filter']['sort_field'] );
    unset( $_REQUEST['filter']['sort_asc'] );
    unset( $_REQUEST['filter']['limit'] );
}

if ( empty($_REQUEST['sort_field']) )
    $_REQUEST['sort_field'] = "DateTime";
if ( empty($_REQUEST['sort_asc']) )
    $_REQUEST['sort_asc'] = "1";

$countSql = "select count(E.Id) as EventCount from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
$eventsSql = "select E.Id,E.MonitorId,M.Name As MonitorName,E.Name,E.StartTime,E.Length,E.Frames,E.AlarmFrames,E.TotScore,E.AvgScore,E.MaxScore,E.Archived from Monitors as M inner join Events as E on (M.Id = E.MonitorId) where";
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

parseSort( true, '&amp;' );
parseFilter( $_REQUEST['filter'], true, '&amp;' );
$filterQuery = $_REQUEST['filter']['query'];

if ( $_REQUEST['filter']['sql'] )
{
    $countSql .= $_REQUEST['filter']['sql'];
    $eventsSql .= $_REQUEST['filter']['sql'];
}
$eventsSql .= " order by $sortColumn $sortOrder";

$deviceLines = (isset($device)&&!empty($device['lines']))?$device['lines']:DEVICE_LINES;
// Allow for headers etc
$deviceLines -= 2;

if ( !empty($_REQUEST['page']) )
{
    $limitStart = (($_REQUEST['page']-1)*$deviceLines);
    if ( empty($_REQUEST['limit']) )
    {
        $limitAmount = $deviceLines;
    }
    else
    {
        $limitLeft = $_REQUEST['limit'] - $limitStart;
        $limitAmount = ($limitLeft>$deviceLines)?$deviceLines:$limitLeft;
    }
    $eventsSql .= " limit $limitStart, $limitAmount";
}
elseif ( !empty( $_REQUEST['limit'] ) )
{
    $eventsSql .= " limit 0, ".$_REQUEST['limit'];
}

$nEvents = dbFetchOne( $countSql, 'EventCount' );
if ( !empty($limit) && $nEvents > $_REQUEST['limit'] )
{
    $nEvents = $_REQUEST['limit'];
}
$pages = (int)ceil($nEvents/$deviceLines);

$maxShortcuts = 3;
$pagination = getPagination( $pages, $_REQUEST['page'], $maxShortcuts, $filterQuery.$sortQuery.'&amp;limit='.$_REQUEST['limit'], '&amp;' );

xhtmlHeaders( __FILE__, $SLANG['Events'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons"><?= makeLink( "?view=filter", empty($_REQUEST['filterName'])?$SLANG['ChooseFilter']:$_REQUEST['filterName'], canView( 'Events' ) ) ?></div>
      <h2><?= sprintf( $CLANG['EventCount'], $nEvents, zmVlang( $VLANG['Event'], $nEvents ) ) ?></h2>
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
      <table id="contentTable" class="major">
<?php
    $count = 0;
    foreach ( dbFetchAll( $eventsSql ) as $event )
    {
        if ( ($count++%$deviceLines) == 0 )
        {
?>
        <tr>
          <th class="colId"><a href="<?= sortHeader( 'Id', '&amp;' ) ?>"><?= substr( $SLANG['Id'], 0, 3 ) ?><?= sortTag( 'Id' ) ?></a></th>
          <th class="colTime"><a href="<?= sortHeader( 'StartTime', '&amp;' ) ?>"><?= substr( $SLANG['Time'], 0, 3 ) ?><?= sortTag( 'StartTime' ) ?></a></th>
          <th class="colDuration"><a href="<?= sortHeader( 'Length', '&amp;' ) ?>"><?= substr( $SLANG['Duration'], 0, 3 ) ?><?= sortTag( 'Length' ) ?></a></th>
          <th class="colFrames"><a href="<?= sortHeader( 'Frames', '&amp;' ) ?>"><?= substr( $SLANG['Frames'], 0, 3 ) ?><?= sortTag( 'Frames' ) ?></a></th>
          <th class="colScore"><a href="<?= sortHeader( 'TotScore', '&amp;' ) ?>"><?= substr( $SLANG['Score'], 0, 3 ) ?><?= sortTag( 'TotScore' ) ?></a></th>
        </tr>
<?php
        }
?>
        <tr>
          <td class="colId"><a href="?view=eventdetails&amp;eid=<?= $event['Id'] ?>&amp;page=1"><?= $event['Id'] ?><?php if ( $event['Archived'] ) echo "*" ?></a></td>
          <td class="colTime"><?= strftime( "%d/%H:%M", strtotime($event['StartTime']) ) ?></td>
          <td class="colDuration"><?= sprintf( "%d", $event['Length'] ) ?></td>
          <td class="colFrames"><a href="?view=event&amp;eid=<?= $event['Id'] ?>&amp;page=1"><?= $event['AlarmFrames'] ?></a></td>
          <td class="colScore"><a href="?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=0"><?= $event['MaxScore'] ?></a></td>
        </tr>
<?php
    }
?>
      </table>
      <p><a href="?view=console"><?= $SLANG['Console'] ?></a></p>
    </div>
  </div>
</body>
</html>
