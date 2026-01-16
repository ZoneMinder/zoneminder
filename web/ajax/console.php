<?php
$message = '';
$data = array();

// Handle query task for bootstrap-table AJAX requests
if (!empty($_REQUEST['task'])) {
  $task = $_REQUEST['task'];
  
  if ($task == 'query') {
    if (!canView('Monitors')) {
      ajaxError('Insufficient permissions for user '.$user->Username());
      return;
    }
    $data = queryRequest();
    ajaxResponse($data);
    return;
  }
}

// Handle legacy action-based requests
if ( canEdit('Monitors') ) {
  switch ( $_REQUEST['action'] ) {
  case 'sort' :
  {
    $monitor_ids = $_POST['monitor_ids'];
    # Two concurrent sorts could generate odd sorting... so lock the table.
    global $dbConn;
    $dbConn->beginTransaction();
    $dbConn->exec('LOCK TABLES Monitors WRITE');
    for ( $i = 0; $i < count($monitor_ids); $i += 1 ) {
      $monitor_id = $monitor_ids[$i];
      $monitor_id = preg_replace('/^monitor_id-/', '', $monitor_id);
      if ( ( !$monitor_id ) or ! ( is_integer($monitor_id) or ctype_digit($monitor_id) ) ) {
        Warning('Got '.$monitor_id.' from '.$monitor_ids[$i]);
        continue;
      }
      dbQuery('UPDATE Monitors SET Sequence=? WHERE Id=?', array($i, $monitor_id));
    } // end for each monitor_id
    $dbConn->commit();
    $dbConn->exec('UNLOCK TABLES');

    return;
  } // end case sort
  default:
    ZM\Warning('unknown action '.$_REQUEST['action']);
  }
} else {
  ZM\Warning('Cannot edit monitors');
}

ajaxError('Unrecognised action '.$_REQUEST['action'].' or insufficient permissions for user ' . $user->Username());

//
// FUNCTION DEFINITIONS
//

function queryRequest() {
  global $user, $Servers;
  require_once('includes/Monitor.php');
  require_once('includes/Group_Monitor.php');
  
  $data = array(
    'total' => 0,
    'totalNotFiltered' => 0,
    'rows' => array()
  );
  
  // Get pagination parameters
  $offset = 0;
  if (isset($_REQUEST['offset']) and ($_REQUEST['offset'] != 'NaN')) {
    if ((!is_int($_REQUEST['offset']) and !ctype_digit($_REQUEST['offset']))) {
      ZM\Error('Invalid value for offset: ' . $_REQUEST['offset']);
    } else {
      $offset = $_REQUEST['offset'];
    }
  }
  
  $limit = 0;
  if (isset($_REQUEST['limit']) and ($_REQUEST['limit'] != 'NaN')) {
    if ((!is_int($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']))) {
      ZM\Error('Invalid value for limit: ' . $_REQUEST['limit']);
    } else {
      $limit = $_REQUEST['limit'];
    }
  }
  
  // Get search parameter
  $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
  
  // Get sort parameters
  $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'Sequence';
  $order = isset($_REQUEST['order']) ? strtoupper($_REQUEST['order']) : 'ASC';
  
  // Build monitor query with filters from request parameters (stateless)
  $conditions = array();
  $values = array();
  
  // Get filter values directly from request
  $request_filters = array(
    'GroupId' => isset($_REQUEST['GroupId']) ? $_REQUEST['GroupId'] : null,
    'ServerId' => isset($_REQUEST['ServerId']) ? $_REQUEST['ServerId'] : null,
    'StorageId' => isset($_REQUEST['StorageId']) ? $_REQUEST['StorageId'] : null,
    'Capturing' => isset($_REQUEST['Capturing']) ? $_REQUEST['Capturing'] : null,
    'Analysing' => isset($_REQUEST['Analysing']) ? $_REQUEST['Analysing'] : null,
    'Recording' => isset($_REQUEST['Recording']) ? $_REQUEST['Recording'] : null,
    'Status' => isset($_REQUEST['Status']) ? $_REQUEST['Status'] : null,
    'MonitorId' => isset($_REQUEST['MonitorId']) ? $_REQUEST['MonitorId'] : null,
    'MonitorName' => isset($_REQUEST['MonitorName']) ? $_REQUEST['MonitorName'] : null,
    'Source' => isset($_REQUEST['Source']) ? $_REQUEST['Source'] : null
  );
  
  // Apply request filters to SQL
  if ($request_filters['GroupId']) {
    $GroupIds = is_array($request_filters['GroupId']) ? $request_filters['GroupId'] : array($request_filters['GroupId']);
    $conditions[] = 'M.Id IN (SELECT MonitorId FROM Groups_Monitors WHERE GroupId IN (' . implode(',', array_fill(0, count($GroupIds), '?')) . '))';
    $values = array_merge($values, $GroupIds);
  }
  
  foreach (array('ServerId','StorageId') as $filter) {
    if ($request_filters[$filter]) {
      $filter_values = is_array($request_filters[$filter]) ? $request_filters[$filter] : array($request_filters[$filter]);
      if (count($filter_values)) {
        $conditions[] = 'M.'.$filter.' IN (' . implode(',', array_fill(0, count($filter_values), '?')) . ')';
        $values = array_merge($values, $filter_values);
      }
    }
  }
  
  foreach (array('Capturing','Analysing','Recording') as $filter) {
    if ($request_filters[$filter]) {
      $filter_values = is_array($request_filters[$filter]) ? $request_filters[$filter] : array($request_filters[$filter]);
      if (count($filter_values)) {
        $conditions[] = 'M.'.$filter.' IN (' . implode(',', array_fill(0, count($filter_values), '?')) . ')';
        $values = array_merge($values, $filter_values);
      }
    }
  }
  
  if ($request_filters['Status']) {
    $status_values = is_array($request_filters['Status']) ? $request_filters['Status'] : array($request_filters['Status']);
    if (count($status_values)) {
      $conditions[] = 'COALESCE(S.Status, IF(M.Type="WebSite","Running","NotRunning")) IN (' . implode(',', array_fill(0, count($status_values), '?')) . ')';
      $values = array_merge($values, $status_values);
    }
  }
  
  // Build SQL query
  $sql = 'SELECT M.*, S.*, E.*
    FROM Monitors AS M
    LEFT JOIN Monitor_Status AS S ON S.MonitorId=M.Id 
    LEFT JOIN Event_Summaries AS E ON E.MonitorId=M.Id 
    WHERE M.`Deleted`=false';
  
  if (count($conditions)) {
    $sql .= ' AND ' . implode(' AND ', $conditions);
  }
  
  // Get total count before filtering
  $monitors = dbFetchAll($sql, null, $values);
  $unfiltered_monitors = array();
  foreach ($monitors as $monitor) {
    if (visibleMonitor($monitor['Id'])) {
      $unfiltered_monitors[] = $monitor;
    }
  }
  $data['totalNotFiltered'] = count($unfiltered_monitors);
  
  // Apply search filter
  $filtered_monitors = $unfiltered_monitors;
  if ($search != '') {
    $search_lower = strtolower($search);
    $filtered_monitors = array_filter($unfiltered_monitors, function($monitor) use ($search_lower) {
      // Search across common fields without creating Monitor object
      return (
        stripos($monitor['Name'], $search_lower) !== false ||
        stripos($monitor['Function'], $search_lower) !== false ||
        stripos($monitor['Path'], $search_lower) !== false ||
        stripos($monitor['Device'], $search_lower) !== false ||
        stripos($monitor['Host'], $search_lower) !== false ||
        stripos($monitor['Id'], $search_lower) !== false ||
        (isset($monitor['Status']) && stripos($monitor['Status'], $search_lower) !== false)
      );
    });
  }
  
  // Apply MonitorName and Source request filters
  if ($request_filters['MonitorName']) {
    $regexp = $request_filters['MonitorName'];
    if (!strpos($regexp, '/')) $regexp = '/'.$regexp.'/i';
    $filtered_monitors = array_filter($filtered_monitors, function($monitor) use ($regexp) {
      return @preg_match($regexp, $monitor['Name']);
    });
  }
  
  if ($request_filters['Source']) {
    $regexp = $request_filters['Source'];
    if (!preg_match("/^\/.+\/[a-z]*$/i", $regexp))
      $regexp = '/'.$regexp.'/i';
    $filtered_monitors = array_filter($filtered_monitors, function($monitor) use ($regexp) {
      // Match against Path field directly instead of creating Monitor object
      return (preg_match($regexp, $monitor['Path']) || preg_match($regexp, $monitor['Device']) || preg_match($regexp, $monitor['Host']));
    });
  }
  
  // Apply MonitorId filter
  if ($request_filters['MonitorId']) {
    $monitor_ids = is_array($request_filters['MonitorId']) ? $request_filters['MonitorId'] : array($request_filters['MonitorId']);
    $filtered_monitors = array_filter($filtered_monitors, function($monitor) use ($monitor_ids) {
      return in_array($monitor['Id'], $monitor_ids);
    });
  }
  
  $data['total'] = count($filtered_monitors);
  
  // Sort monitors
  usort($filtered_monitors, function($a, $b) use ($sort, $order) {
    $aVal = isset($a[$sort]) ? $a[$sort] : '';
    $bVal = isset($b[$sort]) ? $b[$sort] : '';
    
    if (is_numeric($aVal) && is_numeric($bVal)) {
      $result = $aVal - $bVal;
    } else {
      $result = strcasecmp($aVal, $bVal);
    }
    
    return $order == 'ASC' ? $result : -$result;
  });
  
  // Apply pagination
  if ($limit > 0) {
    $filtered_monitors = array_slice($filtered_monitors, $offset, $limit);
  } else {
    $filtered_monitors = array_slice($filtered_monitors, $offset);
  }
  
  // Get storage areas and servers
  $storage_areas = ZM\Storage::find();
  $StorageById = array();
  foreach ($storage_areas as $S) {
    $StorageById[$S->Id()] = $S;
  }
  
  $ServersById = array();
  foreach ($Servers as $s) {
    $ServersById[$s->Id()] = $s;
  }
  
  // Get group IDs for each monitor
  $monitor_ids = array_map(function($m) { return $m['Id']; }, $filtered_monitors);
  $group_ids_by_monitor_id = array();
  if (count($monitor_ids)) {
    foreach (ZM\Group_Monitor::find(array('MonitorId'=>$monitor_ids)) as $GM) {
      if (!isset($group_ids_by_monitor_id[$GM->MonitorId()]))
        $group_ids_by_monitor_id[$GM->MonitorId()] = array();
      $group_ids_by_monitor_id[$GM->MonitorId()][] = $GM->GroupId();
    }
  }
  
  // Process each monitor and build row data
  $footer_totals = array(
    'monitor_count' => count($filtered_monitors),
    'total_bandwidth' => 0,
    'total_fps' => 0,
    'total_analysis_fps' => 0,
    'total_zones' => 0,
    'event_totals' => array(
      'Total' => array('events' => 0, 'diskspace' => 0),
      'Hour' => array('events' => 0, 'diskspace' => 0),
      'Day' => array('events' => 0, 'diskspace' => 0),
      'Week' => array('events' => 0, 'diskspace' => 0),
      'Month' => array('events' => 0, 'diskspace' => 0),
      'Archived' => array('events' => 0, 'diskspace' => 0)
    )
  );
  
  foreach ($filtered_monitors as $monitor) {
    $Monitor = new ZM\Monitor($monitor);
    $Monitor->GroupIds(isset($group_ids_by_monitor_id[$Monitor->Id()]) ? $group_ids_by_monitor_id[$Monitor->Id()] : array());
    
    // Accumulate footer totals
    $footer_totals['total_bandwidth'] += isset($monitor['CaptureBandwidth']) ? $monitor['CaptureBandwidth'] : 0;
    $footer_totals['total_fps'] += isset($monitor['CaptureFPS']) ? floatval($monitor['CaptureFPS']) : 0;
    $footer_totals['total_analysis_fps'] += isset($monitor['AnalysisFPS']) ? floatval($monitor['AnalysisFPS']) : 0;
    $footer_totals['total_zones'] += isset($monitor['ZoneCount']) ? intval($monitor['ZoneCount']) : 0;
    
    foreach (array('Total', 'Hour', 'Day', 'Week', 'Month', 'Archived') as $period) {
      $footer_totals['event_totals'][$period]['events'] += isset($monitor[$period.'Events']) ? intval($monitor[$period.'Events']) : 0;
      $footer_totals['event_totals'][$period]['diskspace'] += isset($monitor[$period.'EventDiskSpace']) ? intval($monitor[$period.'EventDiskSpace']) : 0;
    }
    
    $row = array();
    $row['Id'] = $monitor['Id'];
    $row['Name'] = validHtmlStr($monitor['Name']);
    $row['Function'] = $monitor['Function'];
    $row['Enabled'] = $monitor['Enabled'];
    $row['Sequence'] = isset($monitor['Sequence']) ? $monitor['Sequence'] : 0;
    
    // Status
    if (!$monitor['Status']) {
      if ($monitor['Type'] == 'WebSite')
        $monitor['Status'] = 'Running';
      else
        $monitor['Status'] = 'NotRunning';
    }
    $row['Status'] = $monitor['Status'];
    
    // Server
    if (count($Servers)) {
      $Server = isset($ServersById[$monitor['ServerId']]) ? $ServersById[$monitor['ServerId']] : new ZM\Server($monitor['ServerId']);
      $row['Server'] = validHtmlStr($Server->Name());
      $row['ServerId'] = $monitor['ServerId'];
    }
    
    // Source
    $row['Source'] = validHtmlStr($Monitor->Source());
    $row['Width'] = $Monitor->Width();
    $row['Height'] = $Monitor->Height();
    
    // Storage
    if (isset($StorageById[$monitor['StorageId']])) {
      $row['Storage'] = validHtmlStr($StorageById[$monitor['StorageId']]->Name());
    } else if ($monitor['StorageId']) {
      $row['Storage'] = '<span class="error">Deleted '.$monitor['StorageId'].'</span>';
    } else {
      $row['Storage'] = '';
    }
    
    // Event counts with filter querystrings
    $eventCounts = array(
      'Total' => array(
        'filter' => array('Query' => array('terms' => array()))
      ),
      'Hour' => array(
        'filter' => array('Query' => array('terms' => array(
          array('cnj' => 'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 hour')
        )))
      ),
      'Day' => array(
        'filter' => array('Query' => array('terms' => array(
          array('cnj' => 'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 day')
        )))
      ),
      'Week' => array(
        'filter' => array('Query' => array('terms' => array(
          array('cnj' => 'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-7 day')
        )))
      ),
      'Month' => array(
        'filter' => array('Query' => array('terms' => array(
          array('cnj' => 'and', 'attr' => 'StartDateTime', 'op' => '>=', 'val' => '-1 month')
        )))
      ),
      'Archived' => array(
        'filter' => array('Query' => array('terms' => array(
          array('cnj' => 'and', 'attr' => 'Archived', 'op' => '=', 'val' => '1')
        )))
      )
    );
    
    foreach ($eventCounts as $period => $eventCount) {
      $row[$period.'Events'] = (int)$monitor[$period.'Events'];
      $row[$period.'EventDiskSpace'] = human_filesize($monitor[$period.'EventDiskSpace']);
      
      // Generate filter querystring for this period and monitor
      $filter = addFilterTerm(
        $eventCount['filter'],
        count($eventCount['filter']['Query']['terms']),
        array('cnj' => 'and', 'attr' => 'Monitor', 'op' => '=', 'val' => $monitor['Id'])
      );
      parseFilter($filter);
      $row[$period.'FilterQuery'] = $filter['querystring'];
    }
    
    // Zone count
    $row['ZoneCount'] = $monitor['ZoneCount'];
    
    // FPS and bandwidth
    $row['CaptureFPS'] = isset($monitor['CaptureFPS']) ? $monitor['CaptureFPS'] : '0.00';
    $row['AnalysisFPS'] = isset($monitor['AnalysisFPS']) ? $monitor['AnalysisFPS'] : '0.00';

    // Format bandwidth with units (bytes per second) - use human_filesize for consistency
    $bandwidth = isset($monitor['CaptureBandwidth']) ? $monitor['CaptureBandwidth'] : 0;
    if ($bandwidth > 0) {
      $row['CaptureBandwidth'] = human_filesize($bandwidth).'/s';
    } else {
      $row['CaptureBandwidth'] = '';
    }
    $row['Analysing'] = isset($monitor['Analysing']) ? $monitor['Analysing'] : 'None';
    $row['Recording'] = isset($monitor['Recording']) ? $monitor['Recording'] : 'None';
    $row['ONVIF_Event_Listener'] = isset($monitor['ONVIF_Event_Listener']) ? $monitor['ONVIF_Event_Listener'] : 0;
    $row['UpdatedOn'] = isset($monitor['UpdatedOn']) ? $monitor['UpdatedOn'] : '';
    $row['Type'] = $monitor['Type'];
    $row['Capturing'] = isset($monitor['Capturing']) ? $monitor['Capturing'] : 'None';
    
    // Groups
    if (canView('Groups')) {
      $groups_html = implode('<br/>',
        array_map(function($group_id) {
          $Group = ZM\Group::find_one(array('Id'=>$group_id));
          if ($Group) {
            $Groups = $Group->Parents();
            array_push($Groups, $Group);
          } else {
            $Groups = array();
          }
          return implode(' &gt; ', array_map(function($Group) {
            if (canView('Stream')) {
              return '<a href="?view=montagereview&amp;GroupId='.$Group->Id().'">'.validHtmlStr($Group->Name()).'</a>';
            } else {
              return validHtmlStr($Group->Name());
            }
          }, $Groups));
        }, $Monitor->GroupIds())
      );
      $row['Groups'] = $groups_html;
    } else {
      $row['Groups'] = '';
    }
    
    // Thumbnail
    $row['Thumbnail'] = '';
    if (ZM_WEB_LIST_THUMBS && ($monitor['Capturing'] != 'None') && canView('Stream')) {
      $options = array();
      $ratio_factor = $Monitor->ViewWidth() ? $Monitor->ViewHeight() / $Monitor->ViewWidth() : 1;
      $options['width'] = ZM_WEB_LIST_THUMB_WIDTH;
      $options['height'] = ZM_WEB_LIST_THUMB_HEIGHT ? ZM_WEB_LIST_THUMB_HEIGHT : ZM_WEB_LIST_THUMB_WIDTH*$ratio_factor;
      $options['scale'] = $Monitor->ViewWidth() ? intval(100*ZM_WEB_LIST_THUMB_WIDTH / $Monitor->ViewWidth()) : 100;
      $options['mode'] = 'jpeg';
      $options['frames'] = 1;
      
      $stillSrc = $Monitor->getStreamSrc($options);
      $streamSrc = $Monitor->getStreamSrc(array('scale'=>($options['scale'] > 20 ? 100 : $options['scale']*5)));
      
      $thmbWidth = ($options['width']) ? 'width:'.$options['width'].'px;' : '';
      $thmbHeight = ($options['height']) ? 'height:'.$options['height'].'px;' : '';
      
      $row['Thumbnail'] = '<div class="colThumbnail" style="'.$thmbHeight.'"><a href="?view=watch&amp;mid='.$monitor['Id'].'">'.
        '<img id="thumbnail'.$Monitor->Id().'" src="'.$stillSrc.'" style="'.$thmbWidth.$thmbHeight.
        '" stream_src="'.$streamSrc.'" still_src="'.$stillSrc.'"'.
        ($options['width'] ? ' width="'.$options['width'].'"' : '').
        ($options['height'] ? ' height="'.$options['height'].'"' : '').
        ' loading="lazy" /></a></div>';
    }
    
    $data['rows'][] = $row;
  }
  
  // Add footer totals to response
  $data['footer'] = array(
    'monitor_count' => $footer_totals['monitor_count'],
    'bandwidth_fps' => human_filesize($footer_totals['total_bandwidth']).'/s '.
                       round($footer_totals['total_fps'], 2).' fps / '.
                       round($footer_totals['total_analysis_fps'], 2).' fps',
    'total_zones' => $footer_totals['total_zones']
  );
  
  // Add formatted event totals to footer
  foreach (array('Total', 'Hour', 'Day', 'Week', 'Month', 'Archived') as $period) {
    $data['footer'][$period.'Events'] = $footer_totals['event_totals'][$period]['events'];
    $data['footer'][$period.'EventDiskSpace'] = human_filesize($footer_totals['event_totals'][$period]['diskspace']);
  }
  
  return $data;
}
?>
