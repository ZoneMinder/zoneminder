<?php
if ( $_REQUEST['entity'] == 'navBar' ) {
  $data = array();
  if ( ZM_OPT_USE_AUTH && (ZM_AUTH_RELAY == 'hashed') ) {
    $auth_hash = generateAuthHash(ZM_AUTH_HASH_IPS);
    if ( isset($_REQUEST['auth']) and ($_REQUEST['auth'] != $auth_hash) ) {
      $data['auth'] = $auth_hash;
    }
  }
  $data['message'] = getNavBarHtml('reload');
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
      'ActiveMonitorCount' => array( 'sql' => 'count(if(`Function` != \'None\',1,NULL))' ),
      'State' => array( 'func' => 'daemonCheck()?'.translate('Running').':'.translate('Stopped') ),
      'Load' => array( 'func' => 'getLoad()' ),
      'Disk' => array( 'func' => 'getDiskPercent()' ),
    ),
  ),
  'monitor' => array(
    'permission' => 'Monitors',
    'table' => 'Monitors',
    'limit' => 1,
    'selector' => 'Monitors.Id',
    'elements' => array(
      'Id' => array( 'sql' => 'Monitors.Id' ),
      'Name' => array( 'sql' => 'Monitors.Name' ),
      'Type' => true,
      'Function' => true,
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
      'MinEventId' => array( 'sql' => '(SELECT min(Events.Id) FROM Events WHERE Events.MonitorId = Monitors.Id' ),
      'MaxEventId' => array( 'sql' => '(SELECT max(Events.Id) FROM Events WHERE Events.MonitorId = Monitors.Id' ),
      'TotalEvents' => array( 'sql' => '(SELECT count(Events.Id) FROM Events WHERE Events.MonitorId = Monitors.Id' ),
      'Status' => (isset($_REQUEST['id'])?array( 'zmu' => '-m '.escapeshellarg($_REQUEST['id'][0]).' -s' ):null),
      'FrameRate' => (isset($_REQUEST['id'])?array( 'zmu' => '-m '.escapeshellarg($_REQUEST['id'][0]).' -f' ):null),
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
      'StartTime' => true,
      'StartTimeShort' => array( 'sql' => 'date_format( StartTime, \''.MYSQL_FMT_DATETIME_SHORT.'\' )' ), 
      'EndTime' => true,
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
      'StartTime' => true,
      'StartTimeShort' => array( 'sql' => 'date_format( StartTime, \''.MYSQL_FMT_DATETIME_SHORT.'\' )' ), 
      'EndTime' => true,
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
      'TimeStampShort' => array( 'sql' => 'date_format( StartTime, \''.MYSQL_FMT_DATETIME_SHORT.'\' )' ), 
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

  $entitySpec = &$statusData[strtolower(validJsStr($_REQUEST['entity']))];
#print_r( $entitySpec );
  if ( !canView($entitySpec['permission']) )
    ajaxError('Unrecognised action or insufficient permissions');

  if ( !empty($entitySpec['func']) ) {
    $data = eval('return('.$entitySpec['func'].');');
  } else {
    $data = array();
    $postFuncs = array();

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
      if ( !($elementData = $lc_elements[strtolower($element)]) )
        ajaxError('Bad '.validJsStr($_REQUEST['entity']).' element '.$element);
      if ( isset($elementData['func']) )
        $data[$element] = eval('return( '.$elementData['func'].' );');
      else if ( isset($elementData['postFunc']) )
        $postFuncs[$element] = $elementData['postFunc'];
      else if ( isset($elementData['zmu']) )
        $data[$element] = exec(escapeshellcmd(getZmuCommand(' '.$elementData['zmu'])));
      else {
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

    if ( count($fieldSql) ) {
      $sql = 'SELECT '.join(', ', $fieldSql).' FROM '.$entitySpec['table'];
      if ( $joinSql )
        $sql .= ' '.join(' ', array_unique($joinSql));
      if ( $id && !empty($entitySpec['selector']) ) {
        $index = 0;
        $where = array();
        foreach( $entitySpec['selector'] as $selIndex => $selector ) {
          $selectorParamName = ':selector' . $selIndex;
          if ( is_array( $selector ) ) {
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
        $sort_fields = explode(',',$_REQUEST['sort']);
        foreach ( $sort_fields as $sort_field ) {
          
          preg_match('/^(\w+)\s*(ASC|DESC)?( NULLS FIRST)?$/i', $sort_field, $matches);
          if ( count($matches) ) {
            if ( in_array($matches[1], $fieldSql) ) {
              $sql .= $matches[1];
            } else {
              ZM\Error('Sort field ' . $matches[1] . ' not in SQL Fields');
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
      $limit_offset='';
      if ( !empty($_REQUEST['offset']) )
        $limit_offset = validInt($_REQUEST['offset']) . ', ';
      if ( !empty( $limit ) )
        $sql .= ' limit '.$limit_offset.$limit;
      if ( isset($limit) && $limit == 1 ) {
        if ( $sqlData = dbFetchOne($sql, NULL, $values) ) {
          foreach ( $postFuncs as $element=>$func )
            $sqlData[$element] = eval( 'return( '.$func.'( $sqlData ) );' );
          $data = array_merge( $data, $sqlData );
        }
      } else {
        $count = 0;
        foreach( dbFetchAll( $sql, NULL, $values ) as $sqlData ) {
          foreach ( $postFuncs as $element=>$func )
            $sqlData[$element] = eval( 'return( '.$func.'( $sqlData ) );' );
          $data[] = $sqlData;
          if ( isset($limi) && ++$count >= $limit )
            break;
        }
      }
    }
  }
  #ZM\Logger::Debug(print_r($data, true));
  return $data;
}

$data = collectData();

if ( !isset($_REQUEST['layout']) ) {
  $_REQUEST['layout'] = 'json';
}

switch( $_REQUEST['layout'] ) {
  case 'xml NOT CURRENTLY SUPPORTED' :
      header('Content-type: application/xml');
      echo('<?xml version="1.0" encoding="iso-8859-1"?>
');
      echo '<'.strtolower($_REQUEST['entity']).'>
';
      foreach ( $data as $key=>$value ) {
        $key = strtolower($key);
        echo "<$key>".htmlentities($value)."</$key>\n";
      }
      echo '</'.strtolower($_REQUEST['entity']).">\n";
      break;
  case 'json' :
    {
      $response = array( strtolower(validJsStr($_REQUEST['entity'])) => $data );
      if ( isset($_REQUEST['loopback']) )
        $response['loopback'] = validJsStr($_REQUEST['loopback']);
      ajaxResponse($response);
      break;
    }
  case 'text' :
      header('Content-type: text/plain' );
      echo join( ' ', array_values( $data ) );
      break;
  default:
    ZM\Error('Unsupported layout: '. $_REQUEST['layout']);
}

function getFrameImage() {
  $eventId = $_REQUEST['id'][0];
  $frameId = $_REQUEST['id'][1];

  $sql = 'SELECT * FROM Frames WHERE EventId = ? AND FrameId = ?';
  if ( !($frame = dbFetchOne( $sql, NULL, array($eventId, $frameId ) )) ) {
    $frame = array();
    $frame['EventId'] = $eventId;
    $frame['FrameId'] = $frameId;
    $frame['Type'] = 'Virtual';
  }
  $event = dbFetchOne( 'select * from Events where Id = ?', NULL, array( $frame['EventId'] ) );
  $frame['Image'] = getImageSrc( $event, $frame, SCALE_BASE );
  return( $frame );
}

function getNearFrame() {
  $eventId = $_REQUEST['id'][0];
  $frameId = $_REQUEST['id'][1];

  $sql = 'select FrameId from Frames where EventId = ? and FrameId <= ? order by FrameId desc limit 1';
  if ( !$nearFrameId = dbFetchOne( $sql, 'FrameId', array( $eventId, $frameId ) ) ) {
    $sql = 'select * from Frames where EventId = ? and FrameId > ? order by FrameId asc limit 1';
    if ( !$nearFrameId = dbFetchOne( $sql, 'FrameId', array( $eventId, $frameId ) ) ) {
      return( array() );
    }
  }
  $_REQUEST['entity'] = 'frame';
  $_REQUEST['id'][1] = $nearFrameId;
  return( collectData() );
}

function getNearEvents() {
  global $user, $sortColumn, $sortOrder;

  $eventId = $_REQUEST['id'];
  $event = dbFetchOne('SELECT * FROM Events WHERE Id=?', NULL, array($eventId));

  parseFilter($_REQUEST['filter']);
  parseSort();

  if ( $user['MonitorIds'] )
    $midSql = ' AND MonitorId IN ('.join( ',', preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).')';
  else
    $midSql = '';

  # When listing, it may make sense to list them in descending order.  But when viewing Prev should timewise earlier and Next should be after.
  if ( $sortColumn == 'E.Id' or $sortColumn == 'E.StartTime' ) {
    $sortOrder = 'asc';
  }

  $sql = "SELECT E.Id AS Id, E.StartTime AS StartTime FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE $sortColumn ".($sortOrder=='asc'?'<=':'>=')." '".$event[$_REQUEST['sort_field']]."'".$_REQUEST['filter']['sql'].$midSql.' AND E.Id<'.$event['Id'] . " ORDER BY $sortColumn ".($sortOrder=='asc'?'desc':'asc');
  if ( $sortColumn != 'E.Id' ) {
    # When sorting by starttime, if we have two events with the same starttime (diffreent monitors) then we should sort secondly by Id
    $sql .= ', E.Id DESC';
  }
  $sql .= ' LIMIT 1';
  $result = dbQuery($sql);
  $prevEvent = dbFetchNext($result);

  $sql = "SELECT E.Id AS Id, E.StartTime AS StartTime FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE $sortColumn ".($sortOrder=='asc'?'>=':'<=')." '".$event[$_REQUEST['sort_field']]."'".$_REQUEST['filter']['sql'].$midSql.' AND E.Id>'.$event['Id'] . " ORDER BY $sortColumn $sortOrder";
  if ( $sortColumn != 'E.Id' ) {
    # When sorting by starttime, if we have two events with the same starttime (diffreent monitors) then we should sort secondly by Id
    $sql .= ', E.Id ASC';
  }
  $sql .= ' LIMIT 1';
  $result = dbQuery( $sql );
  $nextEvent = dbFetchNext( $result );

  $result = array( 'EventId'=>$eventId );
  if ( $prevEvent ) {
    $result['PrevEventId'] = $prevEvent['Id'];
    $result['PrevEventStartTime'] = $prevEvent['StartTime'];
    $result['PrevEventDefVideoPath'] = getEventDefaultVideoPath($prevEvent['Id']);
  } else {
    $result['PrevEventId'] = $result['PrevEventStartTime'] = $result['PrevEventDefVideoPath'] = 0;
  }
  if ( $nextEvent ) {
    $result['NextEventId'] = $nextEvent['Id'];
    $result['NextEventStartTime'] = $nextEvent['StartTime'];
    $result['NextEventDefVideoPath'] = getEventDefaultVideoPath($nextEvent['Id']);
  } else {
    $result['NextEventId'] = $result['NextEventStartTime'] = $result['NextEventDefVideoPath'] = 0;
  }
  return $result;
}

?>
