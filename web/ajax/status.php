<?php
if (!isset($_REQUEST['entity'])) {
  Error("No entity pass to status request.");
  http_response_code(404);
  return;
} else {

}

if ($_REQUEST['entity'] == 'navBar') {
  global $bandwidth_options, $user;
  $data = array();
  if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
    $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
    $data['auth'] = $auth_hash;
    $data['auth_relay'] = get_auth_relay();
  }
  // Each widget on the navbar has its own function
  // Call the functions we want to dynamically update
  $data['getBandwidthHTML'] = getBandwidthHTML($bandwidth_options, $user);
  $data['getSysLoadHTML'] = getSysLoadHTML();
  $data['getCpuUsageHTML'] = getCpuUsageHTML();
  $data['getDbConHTML'] = getDbConHTML();
  $data['getStorageHTML'] = getStorageHTML();
  //$data['getShmHTML'] = getShmHTML();
  $data['getRamHTML'] = getRamHTML();

  ajaxResponse($data);
  return;
}

$statusData = array(
  'system' => array(
    'permission' => 'System',
    'table' => 'Monitors',
    'limit' => 1,
    'elements' => array(
      'MonitorCount' => array( 'sql' => 'count(*)' ),
      'ActiveMonitorCount' => array( 'sql' => 'count(if(`Capturing` != \'None\',1,NULL))' ),
      'State' => array( 'func' => 'daemonCheck()?\''.translate('Running').'\':\''.translate('Stopped').'\'' ),
      'Load' => array( 'func' => 'getLoad()' ),
      'Disk' => array( 'func' => 'getDiskPercent()' ),
    ),
  ),
  'monitor' => array(
    #'permission' => 'Monitors',
    'object'  => 'Monitor',
    'table' => 'Monitors',
    'limit' => 1,
    'selector' => 'Monitors.Id',
    'elements' => array(
      'Id' => array( 'sql' => 'Monitors.Id' ),
      'Name' => array( 'sql' => 'Monitors.Name' ),
      'Type' => true,
      'Capturing' => true,
      'Analysing' => true,
      'Recording' => true,
      'Enabled' => true,
      'LinkedMonitors' => true,
      'Triggers' => true,
      'Device' => true,
      'Channel' => true,
      'Format' => true,
      'Host' => true,
      'Port' => true,
      'Path' => true,
      'Width' => array( 'sql' => 'Monitors.Width' ),
      'Height' => array( 'sql' => 'Monitors.Height' ),
      'Palette' => true,
      'Orientation' => true,
      'Brightness' => true,
      'Contrast' => true,
      'Hue' => true,
      'Colour' => true,
      'EventPrefix' => true,
      'LabelFormat' => true,
      'LabelX' => true,
      'LabelY' => true,
      'LabelSize' => true,
      'ImageBufferCount' => true,
      'WarmupCount' => true,
      'PreEventCount' => true,
      'PostEventCount' => true,
      'AlarmFrameCount' => true,
      'SectionLength' => true,
      'FrameSkip' => true,
      'MotionFrameSkip' => true,
      'MaxFPS' => true,
      'AlarmMaxFPS' => true,
      'FPSReportInterval' => true,
      'RefBlendPerc' => true,
      'Controllable' => true,
      'ControlId' => true,
      'ControlDevice' => true,
      'ControlAddress' => true,
      'AutoStopTimeout' => true,
      'TrackMotion' => true,
      'TrackDelay' => true,
      'ReturnLocation' => true,
      'ReturnDelay' => true,
      'DefaultView' => true,
      'DefaultRate' => true,
      'DefaultScale' => true,
      'WebColour' => true,
      'Sequence' => true,
      'MinEventId' => array( 'sql' => '(SELECT min(Events.Id) FROM Events WHERE Events.MonitorId = Monitors.Id)' ),
      'MaxEventId' => array( 'sql' => '(SELECT max(Events.Id) FROM Events WHERE Events.MonitorId = Monitors.Id)' ),
      'TotalEvents' => array( 'sql' => '(SELECT count(Events.Id) FROM Events WHERE Events.MonitorId = Monitors.Id)' ),
      'Status' => (isset($_REQUEST['id'])?array( 'zmu' => '-m '.escapeshellarg($_REQUEST['id']).' -s' ):null),
      'FrameRate' => (isset($_REQUEST['id'])?array( 'zmu' => '-m '.escapeshellarg($_REQUEST['id']).' -f' ):null),
      'CaptureFPS' => [ 'sql'=>'(SELECT `CaptureFPS` FROM Monitor_Status WHERE MonitorId=Monitors.Id)' ],
      'AnalysisFPS' => [ 'sql'=>'(SELECT `AnalysisFPS` FROM Monitor_Status WHERE MonitorId=Monitors.Id)' ],
      'CaptureBandwidth' => [ 'sql'=>'(SELECT `CaptureBandwidth` FROM Monitor_Status WHERE MonitorId=Monitors.Id)' ],
    ),
  ),
  'events' => array(
    'permission' => 'Events',
    'table' => 'Events',
    'selector' => 'Events.MonitorId',
    'elements' => array(
      'Id' => true,
      'MonitorId' => true,
      'Name' => true,
      'Cause' => true,
      'Notes' => true,
      'StartDateTime' => true,
      'StartDateTimeFormatted' => array('postFunction'=>function($row){
        global $dateTimeFormatter;
        return $dateTimeFormatter->format(strtotime($row['StartDateTime']));
      }),
      # Left for backwards compatibility. Remove in 1.37
      'EndDateTime' => true,
      'EndDateTimeFormatted' => array('postFunction'=>function($row){
        global $dateTimeFormatter;
        return $dateTimeFormatter->format(strtotime($row['EndDateTime']));
      }),
      'Width' => true,
      'Height' => true,
      'Length' => true,
      'Frames' => true,
      'AlarmFrames' => true,
      'TotScore' => true,
      'AvgScore' => true,
      'MaxScore' => true,
    ),
  ),
  'event' => array(
    'permission' => 'Events',
    'table' => 'Events',
    'limit' => 1,
    'selector' => 'Events.Id',
    'elements' => array(
      'Id' => array( 'sql' => 'Events.Id' ),
      'MonitorId' => true,
      'MonitorName' => array('sql' => '(SELECT Monitors.Name FROM Monitors WHERE Monitors.Id = Events.MonitorId)'),
      'Name' => true,
      'Cause' => true,
      'DiskSpace' => true,
      'Storage' => array('sql' => '(SELECT Storage.Name FROM Storage WHERE Storage.Id=Events.StorageId)'),
      'StartDateTime' => true,
      'StartDateTimeFormatted' => array('postFunction'=>function($row){
        global $dateTimeFormatter;
        return $dateTimeFormatter->format(strtotime($row['StartDateTime']));
      }),
      # Left for backwards compatibility. Remove in 1.37
      'EndDateTime' => true,
      'EndDateTimeFormatted' => array('postFunction'=>function($row){
        global $dateTimeFormatter;
        return $dateTimeFormatter->format(strtotime($row['EndDateTime']));
      }),
      'Width' => true,
      'Height' => true,
      'Length' => true,
      'Frames' => true,
      'DefaultVideo' => true,
      'AlarmFrames' => true,
      'TotScore' => true,
      'AvgScore' => true,
      'MaxScore' => true,
      'Archived' => true,
      'Videoed' => true,
      'Uploaded' => true,
      'Emailed' => true,
      'Messaged' => true,
      'Executed' => true,
      'Notes' => true,
      'MinFrameId' => array( 'sql' => '(SELECT min(Frames.FrameId) FROM Frames WHERE EventId=Events.Id)' ),
      'MaxFrameId' => array( 'sql' => '(SELECT max(Frames.FrameId) FROM Frames WHERE Events.Id = Frames.EventId)' ),
      'MinFrameDelta' => array( 'sql' => '(SELECT min(Frames.Delta) FROM Frames WHERE Events.Id = Frames.EventId)' ),
      'MaxFrameDelta' => array( 'sql' => '(SELECT max(Frames.Delta) FROM Frames WHERE Events.Id = Frames.EventId)' ),
    ),
  ),
  'frames' => array(
    'permission' => 'Events',
    'table' => 'Frames',
    'selector' => 'EventId',
    'elements' => array(
      'EventId' => true,
      'FrameId' => true,
      'Type' => true,
      'Delta' => true,
    ),
  ),
  'frame' => array(
    'permission' => 'Events',
    'table' => 'Frames',
    'limit' => 1,
    'selector' => array( array( 'table' => 'Events', 'join' => 'Events.Id = Frames.EventId', 'selector'=>'Events.Id' ), 'Frames.FrameId' ),
    'elements' => array(
      //'Id' => array( 'sql' => 'Frames.FrameId' ),
      'FrameId' => true,
      'EventId' => true,
      'Type' => true,
      'TimeStamp' => true,
      'TimeStampShort' => array( 'sql' => 'date_format( StartDateTime, \''.MYSQL_FMT_DATETIME_SHORT.'\' )' ), 
      'Delta' => true,
      'Score' => true,
      //'Image' => array( 'postFunc' => 'getFrameImage' ),
    ),
  ),
  'frameimage' => array(
    'permission' => 'Events',
    'func' => 'getFrameImage()'
  ),
  'nearframe' => array(
    'permission' => 'Events',
    'func' => 'getNearFrame()'
  ),
  'nearevents' => array(
    'permission' => 'Events',
    'func' => 'getNearEvents()'
  )
);

function collectData() {
  global $statusData;

  $entity = strtolower(validJsStr($_REQUEST['entity']));
  $entitySpec = &$statusData[$entity];
  #print_r( $entitySpec );
  if (isset($entitySpec['permission'])) {
    if (!canView($entitySpec['permission'])) {
      ajaxError('Unrecognised action or insufficient permissions for '.$entity.' permission: '.$entitySpec['permission']);
      return;
    }
  }

  if ( !empty($entitySpec['func']) ) {
    $data = eval('return('.$entitySpec['func'].');');
    return $data;
  }

  $data = array();
  $postFuncs = array();
  $postFunctions = array();

  $fieldSql = array();
  $joinSql = array();
  $groupSql = array();
  $values = array();

  $elements = &$entitySpec['elements'];
  $lc_elements = array_change_key_case($elements);

  $id = false;
  if ( isset($_REQUEST['id']) )
    if ( !is_array($_REQUEST['id']) )
      $id = array( validJsStr($_REQUEST['id']) );
    else
      $id = array_values($_REQUEST['id']);

  if ( !isset($_REQUEST['element']) )
    $_REQUEST['element'] = array_keys($elements);
  else if ( !is_array($_REQUEST['element']) )
    $_REQUEST['element'] = array( validJsStr($_REQUEST['element']) );

  if ( isset($entitySpec['selector']) ) {
    if ( !is_array($entitySpec['selector']) )
      $entitySpec['selector'] = array( $entitySpec['selector'] );
    foreach( $entitySpec['selector'] as $selector )
      if ( is_array( $selector ) && isset($selector['table']) && isset($selector['join']) )
        $joinSql[] = 'left join '.$selector['table'].' on '.$selector['join'];
  }

  foreach ( $_REQUEST['element'] as $element ) {
    if ( !($elementData = $lc_elements[strtolower($element)]) ) {
      ajaxError('Bad '.validJsStr($_REQUEST['entity']).' element '.$element);
      continue;
    }
    if (isset($elementData['func'])) {
      $data[$element] = eval('return( '.$elementData['func'].' );');
    } else if ( isset($elementData['postFunc']) ) {
      $postFuncs[$element] = $elementData['postFunc'];
    } else if ( isset($elementData['postFunction']) ) {
      $postFunctions[$element] = $elementData['postFunction'];
    } else if ( isset($elementData['zmu']) ) {
      $command = escapeshellcmd(getZmuCommand(' '.$elementData['zmu']));
      $data[$element] = exec($command);
    } else {
      if ( isset($elementData['sql']) )
        $fieldSql[] = $elementData['sql'].' as '.$element;
      else
        $fieldSql[] = '`'.$element.'`';
      if ( isset($elementData['table']) && isset($elementData['join']) ) {
        $joinSql[] = 'left join '.$elementData['table'].' on '.$elementData['join'];
      }
      if ( isset($elementData['group']) ) {
        $groupSql[] = $elementData['group'];
      }
    }
  } # end foreach element

  if (isset($entitySpec['object'])) {
    $fieldSql[] = 'Id';
  }

  if ( count($fieldSql) ) {
    $sql = 'SELECT '.join(', ', $fieldSql).' FROM '.$entitySpec['table'];
    if ( $joinSql )
      $sql .= ' '.join(' ', array_unique($joinSql));
    if ( $id && !empty($entitySpec['selector']) ) {
      $index = 0;
      $where = array();
      foreach ( $entitySpec['selector'] as $selIndex => $selector ) {
        $selectorParamName = ':selector' . $selIndex;
        if ( is_array($selector) ) {
          $where[] = $selector['selector'].' = '.$selectorParamName;
          $values[$selectorParamName] = validInt($id[$index]);
        } else {
          $where[] = $selector.' = '.$selectorParamName;
          $values[$selectorParamName] = validInt($id[$index]);
        }
        $index++;
      }
      $sql .= ' WHERE '.join(' AND ', $where);
    }
    if ( $groupSql )
      $sql .= ' GROUP BY '.join(',', array_unique($groupSql));
    if ( !empty($_REQUEST['sort']) ) {
      $sql .= ' ORDER BY ';
      $sort_fields = explode(',', $_REQUEST['sort']);
      foreach ( $sort_fields as $sort_field ) {
        preg_match('/^`?(\w+)`?\s*(ASC|DESC)?( NULLS FIRST)?$/i', $sort_field, $matches);
        if ( count($matches) ) {
          if ( in_array($matches[1], $fieldSql) or  in_array('`'.$matches[1].'`', $fieldSql) ) {
            $sql .= $matches[1];
          } else {
            ZM\Error('Sort field '.$matches[1].' from ' .$sort_field.' not in SQL Fields: '.join(',', $sort_field));
          }
          if ( count($matches) > 2 ) {
            $sql .= ' '.strtoupper($matches[2]);
            if ( count($matches) > 3 )
              $sql .= ' '.strtoupper($matches[3]);
          }
        } else {
          ZM\Error('Sort field didn\'t match regexp '.$sort_field);
        }
      } # end foreach sort field
    } # end if has sort
    if ( !empty($entitySpec['limit']) )
      $limit = $entitySpec['limit'];
    elseif ( !empty($_REQUEST['count']) )
      $limit = validInt($_REQUEST['count']);
    $limit_offset = '';
    if ( !empty($_REQUEST['offset']) )
      $limit_offset = validInt($_REQUEST['offset']) . ', ';
    if ( !empty($limit) )
      $sql .= ' limit '.$limit_offset.$limit;
    if ( isset($limit) && ($limit == 1) ) {
      if ( $sqlData = dbFetchOne($sql, NULL, $values) ) {
        if (isset($entitySpec['object'])) {
          ZM\Debug("Have object".$entitySpec['object']);
          $object_name = 'ZM\\'.$entitySpec['object'];
          $object = new $object_name($sqlData);
          ZM\Debug("Canview:".$object->canView());
          if (!$object->canView()) {
            ajaxError('Unrecognised action or insufficient permissions for '.$entity.' '.print_r($object, true));
            return;
          }
        }
        foreach ( $postFuncs as $element=>$func )
          $sqlData[$element] = eval( 'return( '.$func.'( $sqlData ) );' );
        foreach ( $postFunctions as $element=>$function )
          $sqlData[$element] = $function($sqlData);
        $data = array_merge($data, $sqlData);
      }
    } else {
      $count = 0;
      foreach ( dbFetchAll($sql, NULL, $values) as $sqlData ) {

        if (isset($entitySpec['object'])) {
          ZM\Debug("Have object".$entitySpec['object']);
          $object_name = 'ZM\\'.$entitySpec['object'];
          $object = new $object_name($sqlData);
          ZM\Debug("Canview:".$object->canView());
          if (!$object->canView()) continue;
        }

        foreach ( $postFuncs as $element=>$func )
          $sqlData[$element] = eval('return( '.$func.'( $sqlData ) );');
        foreach ( $postFunctions as $element=>$function )
          $sqlData[$element] = $function($sqlData);
        $data[] = $sqlData;
        if ( isset($limit) && ++$count >= $limit )
          break;
      } # end foreach
    } # end if have limit == 1
  } else {
    ZM\Debug("No fieldSQL");
  }
  //ZM\Debug(print_r($data, true));
  return $data;
}

function formatDateTime($dt) {
  return $dateTimeFormatter->format(strtotime($dt));
}

$data = collectData();

if ( !isset($_REQUEST['layout']) ) {
  $_REQUEST['layout'] = 'json';
}

switch ( $_REQUEST['layout'] ) {
  case 'xml NOT CURRENTLY SUPPORTED' :
    header('Content-type: application/xml');
    echo('<?xml version="1.0" encoding="iso-8859-1"?>
      ');
    $entity = strtolower($_REQUEST['entity']);
    $entity = preg_replace('/[^A-Za-z0-9]/', '', $entity);
    echo '<'.$entity.'>
';
    foreach ( $data as $key=>$value ) {
      $key = strtolower($key);
      echo "<$key>".htmlentities($value)."</$key>\n";
    }
    echo '</'.$entity.">\n";
    break;
  case 'json' :
    {
      $response = array( strtolower(validJsStr($_REQUEST['entity'])) => $data );
      if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
        $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
        $response['auth'] = $auth_hash;
        $response['auth_relay'] = get_auth_relay();
      }
      if ( isset($_REQUEST['loopback']) )
        $response['loopback'] = validJsStr($_REQUEST['loopback']);
        #ZM\Warning(print_r($response, true));
      ajaxResponse($response);
      break;
    }
  case 'text' :
    header('Content-type: text/plain');
    echo join(' ', array_values($data));
    break;
  default:
    ZM\Error('Unsupported layout: '.$_REQUEST['layout']);
}

function getFrameImage() {
  $eventId = validCardinal($_REQUEST['eid']);
  $frameId = validCardinal($_REQUEST['fid']);

  $sql = 'SELECT * FROM Frames WHERE EventId = ? AND FrameId = ?';
  if ( !($frame = dbFetchOne($sql, NULL, array($eventId, $frameId))) ) {
    ZM\Debug("Frame not found for event $eventId frame $frameId");
    $frame = array();
    $frame['EventId'] = $eventId;
    $frame['FrameId'] = $frameId;
    $frame['Type'] = 'Virtual';
  }
  $event = new ZM\Event($frame['EventId']);
  $frame['Image'] = $event->getImageSrc($frame, SCALE_BASE);
  return $frame;
}

function getNearFrame() {
  $eventId = $_REQUEST['id'][0];
  $frameId = $_REQUEST['id'][1];

  $sql = 'SELECT FrameId FROM Frames WHERE EventId = ? AND FrameId <= ? ORDER BY FrameId DESC LIMIT 1';
  if ( !$nearFrameId = dbFetchOne($sql, 'FrameId', array($eventId, $frameId)) ) {
    $sql = 'SELECT * FROM Frames WHERE EventId = ? AND FrameId > ? ORDER BY FrameId ASC LIMIT 1';
    if ( !$nearFrameId = dbFetchOne($sql, 'FrameId', array($eventId, $frameId)) ) {
      return( array() );
    }
  }
  $_REQUEST['entity'] = 'frame';
  $_REQUEST['id'][1] = $nearFrameId;
  return collectData();
}

function getNearEvents() {
  global $user, $sortColumn, $sortOrder;

  $eventId = $_REQUEST['id'];
  $NearEvents = array('EventId'=>$eventId);

  $event = dbFetchOne('SELECT * FROM Events WHERE Id=?', NULL, array($eventId));
  if ( !$event ) return $NearEvents;

  $filter = ZM\Filter::parse($_REQUEST['filter']);
  parseSort();
  if ( count($user->unviewableMonitorIds()) ) {
    $filter = $filter->addTerm(array('cnj'=>'and', 'attr'=>'MonitorId', 'op'=>'IN', 'val'=>$user->viewableMonitorIds()));
  }
  $filter_sql = $filter->sql();

  # When listing, it may make sense to list them in descending order.
  # But when viewing Prev should timewise earlier and Next should be after.
  if ( $sortColumn == 'E.Id' or $sortColumn == 'E.StartDateTime' ) {
    $sortOrder = 'ASC';
  }

  $sql = '
  SELECT 
    E.Id 
      AS Id, 
    E.StartDateTime 
      AS StartDateTime 
  FROM Events 
    AS E 
  INNER JOIN Monitors 
    AS M 
    ON E.MonitorId = M.Id 
  LEFT JOIN Events_Tags 
    AS ET 
    ON E.Id = ET.EventId 
  LEFT JOIN Tags 
    AS T 
    ON T.Id = ET.TagId 
  WHERE '.$sortColumn.' 
  '.($sortOrder=='ASC'?'<=':'>=').' \''.$event[$_REQUEST['sort_field']].'\'';
  if ($filter->sql()) {
    $sql .= ' AND ('.$filter->sql().')';
  }
  $sql .= ' AND E.Id<'.$event['Id'] . ' ORDER BY '.$sortColumn.' '.($sortOrder=='ASC'?'DESC':'ASC');
  if ( $sortColumn != 'E.Id' ) {
    # When sorting by starttime, if we have two events with the same starttime (different monitors) then we should sort secondly by Id
    $sql .= ', E.Id DESC';
  }
  $sql .= ' LIMIT 1';
  $result = dbQuery($sql);
  if ( !$result ) {
    ZM\Error('Failed to load previous event using '.$sql);
    return $NearEvents;
  }

  $prevEvent = dbFetchNext($result);

  $sql = '
  SELECT 
    E.Id 
      AS Id, 
    E.StartDateTime 
      AS StartDateTime 
  FROM Events 
    AS E 
  INNER JOIN Monitors 
    AS M 
    ON E.MonitorId = M.Id 
  LEFT JOIN Events_Tags 
    AS ET 
    ON E.Id = ET.EventId 
  LEFT JOIN Tags 
    AS T 
    ON T.Id = ET.TagId 
  WHERE '.$sortColumn.' 
  '.($sortOrder=='ASC'?'>=':'<=').' \''.$event[$_REQUEST['sort_field']].'\'';
  if ($filter->sql()) {
    $sql .= ' AND ('.$filter->sql().')';
  }
  $sql .= ' AND E.Id>'.$event['Id'] . ' ORDER BY '.$sortColumn.' '.($sortOrder=='ASC'?'ASC':'DESC');
  if ( $sortColumn != 'E.Id' ) {
    # When sorting by starttime, if we have two events with the same starttime (different monitors) then we should sort secondly by Id
    $sql .= ', E.Id ASC';
  }
  $sql .= ' LIMIT 1';
  $result = dbQuery($sql);
  if ( !$result ) {
    ZM\Error('Failed to load next event using '.$sql);
    return $NearEvents;
  }
  $nextEvent = dbFetchNext($result);

  if ( $prevEvent ) {
    $NearEvents['PrevEventId'] = $prevEvent['Id'];
    $NearEvents['PrevEventStartTime'] = $prevEvent['StartDateTime'];
    $NearEvents['PrevEventDefVideoPath'] = getEventDefaultVideoPath($prevEvent['Id']);
  } else {
    $NearEvents['PrevEventId'] = $NearEvents['PrevEventStartTime'] = $NearEvents['PrevEventDefVideoPath'] = 0;
  }
  if ( $nextEvent ) {
    $NearEvents['NextEventId'] = $nextEvent['Id'];
    $NearEvents['NextEventStartTime'] = $nextEvent['StartDateTime'];
    $NearEvents['NextEventDefVideoPath'] = getEventDefaultVideoPath($nextEvent['Id']);
  } else {
    $NearEvents['NextEventId'] = $NearEvents['NextEventStartTime'] = $NearEvents['NextEventDefVideoPath'] = 0;
  }
  return $NearEvents;
} # end function getNearEvents()

?>
