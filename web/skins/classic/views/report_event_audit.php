<?php
//
// ZoneMinder web console file, $Date$, $Revision$
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

$navbar = getNavBarHTML();
ob_start();
include('_monitor_filters.php');
$filterbar = ob_get_contents();
ob_end_clean();

noCacheHeaders();
xhtmlHeaders( __FILE__, translate('Console'));

if ( isset($_REQUEST['minTime']) ) {
  $minTime = validHtmlStr($_REQUEST['minTime']);
} else {
  $minTime = strftime('%FT%T', time() - (2*3600));
}
if ( isset($_REQUEST['maxTime']) ) {
  $maxTime = validHtmlStr($_REQUEST['maxTime']);
} else {
  $maxTime = strftime('%FT%T',time() - 3600);
}

$filter = array(
    'Query' => array(
      'terms' => array(
        array('attr'=>'StartDateTime', 'op'=>'>=', 'val'=>$minTime, 'obr'=>'1'),
        array('attr'=>'StartDateTime', 'op'=>'<=', 'val'=>$maxTime, 'cnj'=>'and', 'cbr'=>'1'),
      )
    ),
  );
if ( count($selected_monitor_ids) ) {
  $filter['Query']['terms'][] = (array('attr'=>'MonitorId', 'op'=>'IN', 'val'=>implode(',', $selected_monitor_ids), 'cnj'=>'and'));
} else if ( ( $group_id != 0 || isset($_SESSION['ServerId']) || isset($_SESSION['StorageId']) || isset($_SESSION['Status']) ) ) {
# this should be redundant
  for ( $i=0; $i < count($displayMonitors); $i++ ) {
    if ( $i == 0 ) {
      $filter['Query']['terms'][] = array('attr'=>'MonitorId', 'op'=>'=', 'val'=>$displayMonitors[$i]['Id'], 'cnj'=>'and', 'obr'=>'1');
    } else if ( $i == count($displayMonitors)-1 ) {
      $filter['Query']['terms'][] = array('attr'=>'MonitorId', 'op'=>'=', 'val'=>$displayMonitors[$i]['Id'], 'cnj'=>'or', 'cbr'=>'1');
    } else {
      $filter['Query']['terms'][] = array('attr'=>'MonitorId', 'op'=>'=', 'val'=>$displayMonitors[$i]['Id'], 'cnj'=>'or');
    }
  }
}
parseFilter($filter);
$filterQuery = $filter['query'];
ZM\Logger::Debug($filterQuery);

$eventsSql = 'SELECT *,
    UNIX_TIMESTAMP(E.StartTime) AS StartTimeSecs,
    UNIX_TIMESTAMP(EndTime) AS EndTimeSecs
  FROM Events AS E
  WHERE 1 > 0 
';
if ( !empty($user['MonitorIds']) ) {
  $eventsSql .= ' AND MonitorId IN ('.$user['MonitorIds'].')';
}
if ( count($selected_monitor_ids) ) {
  $eventsSql .= ' AND MonitorId IN ('.implode(',', $selected_monitor_ids).')';
}
if ( isset($minTime) && isset($maxTime) ) {
  $eventsSql .= " AND EndTime > '" . $minTime . "' AND StartTime < '" . $maxTime . "'";
}
$eventsSql .= ' ORDER BY Id ASC';

$result = dbQuery($eventsSql);
if ( !$result ) {
  ZM\Fatal('SQL-ERR');
  return;
}
$EventsByMonitor = array();
while ( $event = $result->fetch(PDO::FETCH_ASSOC) ) {
  $Event = new ZM\Event($event);
  if ( ! isset($EventsByMonitor[$event['MonitorId']]) )
    $EventsByMonitor[$event['MonitorId']] = array('Events'=>array(), 'MinGap'=>0, 'MaxGap'=>0, 'FileMissing'=>array(), 'ZeroSize'=>array());

  if ( count($EventsByMonitor[$event['MonitorId']]['Events']) ) {
    $last_event = end($EventsByMonitor[$event['MonitorId']]['Events']);
#Logger::Debug(print_r($last_event,true));
    $gap = $last_event->EndTimeSecs() - $event['StartTimeSecs'];
 
    if ( $gap < $EventsByMonitor[$event['MonitorId']]['MinGap'] )
      $EventsByMonitor[$event['MonitorId']]['MinGap'] = $gap;
    if ( $gap > $EventsByMonitor[$event['MonitorId']]['MaxGap'] )
      $EventsByMonitor[$event['MonitorId']]['MaxGap'] = $gap;

  } # end if has previous events
  if ( !$Event->file_exists() ) {
    $EventsByMonitor[$event['MonitorId']]['FileMissing'][] = $Event;
  } else if ( ! $Event->file_size() ) {
    $EventsByMonitor[$event['MonitorId']]['ZeroSize'][] = $Event;
  }
  $EventsByMonitor[$event['MonitorId']]['Events'][] = $Event;
} # end foreach event

?>
<body>
  <form name="monitorForm" method="get" action="?">
    <input type="hidden" name="view" value="<?php echo $view ?>"/>
    <input type="hidden" name="action" value=""/>

    <?php echo $navbar ?>
    <div class="filterBar">
      <?php echo $filterbar ?>
      <div id="DateTimeDiv">
        <label>Event Start Time</label>
        <input type="text" name="minTime" id="minTime" value="<?php echo preg_replace('/T/', ' ', $minTime) ?>" oninput="this.form.submit();"/> to 
        <input type="text" name="maxTime" id="maxTime" value="<?php echo preg_replace('/T/', ' ', $maxTime) ?>" oninput="this.form.submit();"/>
      </div>
    </div><!--FilterBar-->

    <div class="container-fluid">
      <table class="table table-striped table-hover table-condensed" id="consoleTable">
        <thead class="thead-highlight">
          <tr>
            <th class="colId"><?php echo translate('Id') ?></th>
            <th class="colName"><i class="material-icons md-18">videocam</i>&nbsp;<?php echo translate('Name') ?></th>
            <th class="colServer"><?php echo translate('Server') ?></th>
            <th class="colEvents"><?php echo translate('Events') ?></th>
            <th class="colFirstEvent"><?php echo translate('FirstEvent') ?></th>
            <th class="colLastEvent"><?php echo translate('LastEvent') ?></th>
            <th class="colMinGap"><?php echo translate('MinGap') ?></th> 
            <th class="colMaxGap"><?php echo translate('MaxGap') ?></th> 
            <th class="colMissingFiles"><?php echo translate('MissingFiles') ?></th> 
            <th class="colZeroSize"><?php echo translate('ZeroSize') ?></th> 
          </tr>
        </thead>
        <tbody>
<?php
for ( $monitor_i = 0; $monitor_i < count($displayMonitors); $monitor_i += 1 ) {
  $monitor = $displayMonitors[$monitor_i];
  $Monitor = new ZM\Monitor($monitor);
  $montagereview_link = '?view=montagereview&live=0&MonitorId='.$monitor['Id'].'&minTime='.$minTime.'&maxTime='.$maxTime;

  $monitor_filter = addFilterTerm(
      $filter,
      count($filter['Query']['terms']),
      array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'=', 'val'=>$monitor['Id'])
    );
  parseFilter($monitor_filter);

  if ( isset($EventsByMonitor[$Monitor->Id()]) ) {
    $EventCounts = $EventsByMonitor[$Monitor->Id()];
    $MinGap = $EventCounts['MinGap'];
    $MaxGap = $EventCounts['MaxGap'];
    $FileMissing = $EventCounts['FileMissing'];
    $ZeroSize = $EventCounts['ZeroSize'];
    $FirstEvent = $EventCounts['Events'][0];
    $LastEvent = end($EventCounts['Events']);
  } else {
    $MinGap = 0;
    $MaxGap = 0;
    $FileMissing = array();
    $ZeroSize = array();
    $FirstEvent = 0;
    $LastEvent = 0;
  }

  if ( count($FileMissing) ) {
    $FileMissing_filter = array(
        'Query' => array(
          'terms' => array(
            array('attr'=>'Id', 'op'=>'IN', 'val'=>implode(',', array_map(function($Event){return $Event->Id();}, $FileMissing)))
            )
          )
        );
    parseFilter($FileMissing_filter);
  }
  if ( count($ZeroSize) ) {
    $ZeroSize_filter = array(
        'Query' => array(
          'terms' => array(
            array('attr'=>'Id', 'op'=>'IN', 'val'=>implode(',', array_map(function($Event){return $Event->Id();}, $ZeroSize)))
            )
          )
        );
    parseFilter($ZeroSize_filter);
  }
?>
          <tr id="<?php echo 'monitor_id-'.$monitor['Id'] ?>" title="<?php echo $monitor['Id'] ?>">
            <td class="colId"><a href="<?php echo $montagereview_link ?>"><?php echo $monitor['Id'] ?></a></td>
            <td class="colName">
              <a href="<?php echo $montagereview_link ?>"><?php echo validHtmlStr($monitor['Name']) ?></a><br/>
              <div class="small text-nowrap text-muted">
              <?php echo implode('<br/>',
                  array_map(function($group_id){
                    $Group = new ZM\Group($group_id);
                    $Groups = $Group->Parents();
                    array_push($Groups, $Group);
                    return implode(' &gt; ', array_map(function($Group){ return '<a href="?view=montagereview&GroupId='.$Group->Id().'">'.$Group->Name().'</a>'; }, $Groups ));
                    }, $Monitor->GroupIds())); 
?>
            </div></td>
            <td class="colServer"><?php echo validHtmlStr($Monitor->Server()->Name())?></td>
            <td class="colEvents"><a href="?view=<?php echo ZM_WEB_EVENTS_VIEW ?>&amp;page=1<?php echo $monitor_filter['query'] ?>"><?php echo isset($EventsByMonitor[$Monitor->Id()])?count($EventsByMonitor[$Monitor->Id()]['Events']):0 ?></a></td>
            <td class="colFirstEvent"><?php echo $FirstEvent ? $FirstEvent->link_to($FirstEvent->Id().' at '.$FirstEvent->StartTime()) : 'none'?></td>
            <td class="colLastEvent"><?php echo $LastEvent ? $LastEvent->link_to($LastEvent->Id().' at '.$LastEvent->StartTime()) : 'none'?></td>
            <td class="colMinGap"><?php echo $MinGap ?></td>
            <td class="colMaxGap"><?php echo $MaxGap ?></td>
            <td class="colFileMissing<?php echo count($FileMissing) ? ' errorText' : ''?>">
            <?php echo count($FileMissing) ? '<a href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$FileMissing_filter['query'].'">'.count($FileMissing).'</a>' : '0' ?>
            </td>
            <td class="colZeroSize<?php echo count($ZeroSize) ? ' errorText' : ''?>">
            <?php echo count($ZeroSize) ? '<a href="?view='.ZM_WEB_EVENTS_VIEW.'&amp;page=1'.$ZeroSize_filter['query'].'">'.count($ZeroSize).'</a>' : '0' ?>
            </td>
          </tr>
<?php
} # end for each monitor
?>
        </tbody>
      </table>
    </div>
  </form>
<?php xhtmlFooter() ?>
