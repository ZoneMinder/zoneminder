<?php

# Moved up here because it is used in several spots.
# These are the valid columns that you can filter on.
$filterFields = array( 'Component', 'ServerId', 'Pid', 'Level', 'File', 'Line' );

switch ( $_REQUEST['task'] ) {
  case 'create' :
  {
    // Silently ignore bogus requests
    if ( !empty($_POST['level']) && !empty($_POST['message']) ) {
      logInit(array('id'=>'web_js'));

      $string = $_POST['message'];

      $file = !empty($_POST['file']) ? preg_replace( '/\w+:\/\/\w+\//', '', $_POST['file'] ) : '';
      if ( !empty( $_POST['line'] ) )
        $line = $_POST['line'];
      else
        $line = NULL;

      $levels = array_flip(Logger::$codes);
      if ( !isset($levels[$_POST['level']]) )
        Panic("Unexpected logger level '".$_POST['level']."'");
      $level = $levels[$_POST['level']];
      Logger::fetch()->logPrint($level, $string, $file, $line);
    }
    ajaxResponse();
    break;
  }
  case 'query' :
  {
    if ( !canView('System') )
      ajaxError('Insufficient permissions to view log entries');

    $servers = Server::find();
    $servers_by_Id = array();
    # There is probably a better way to do this.
    foreach ( $servers as $server ) {
      $servers_by_Id[$server->Id()] = $server;
    }

    $minTime = isset($_REQUEST['minTime'])?$_REQUEST['minTime']:NULL;
    $maxTime = isset($_REQUEST['maxTime'])?$_REQUEST['maxTime']:NULL;

    $limit = 100;
    if ( isset($_REQUEST['limit']) ) {
      if ( ( !is_integer($_REQUEST['limit']) and !ctype_digit($_REQUEST['limit']) ) ) {
        Error('Invalid value for limit ' . $_REQUEST['limit']);
      } else {
        $limit = $_REQUEST['limit'];
      }
    }
    $sortField = 'TimeKey';
    if ( isset($_REQUEST['sortField']) ) {
      if ( !in_array($_REQUEST['sortField'], $filterFields) and ( $_REQUEST['sortField'] != 'TimeKey' ) ) {
        Error("Invalid sort field " . $_REQUEST['sortField']);
      } else {
        $sortField = $_REQUEST['sortField'];
      }
    }
    $sortOrder = (isset($_REQUEST['sortOrder']) and ($_REQUEST['sortOrder'] == 'asc')) ? 'asc' : 'desc';
    $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : array();

    $total = dbFetchOne('SELECT count(*) AS Total FROM Logs', 'Total');
    $sql = 'SELECT * FROM Logs';
    $where = array();
    $values = array();
    if ( $minTime ) {
      $where[] = 'TimeKey > ?';
      $values[] = $minTime;
    } elseif ( $maxTime ) {
      $where[] = 'TimeKey < ?';
      $values[] = $maxTime;
    }

    foreach ( $filter as $field=>$value ) {
      if ( ! in_array($field, $filterFields) ) {
        Error("$field is not in valid filter fields");
        continue;
      }
      if ( $field == 'Level' ){
        $where[] = $field.' <= ?';
        $values[] = $value;
      } else {
        $where[] = $field.' = ?';
        $values[] = $value;
      }
    }
    $options = array();
    if ( count($where) )
      $sql.= ' WHERE '.join(' AND ', $where);
    $sql .= ' ORDER BY '.$sortField.' '.$sortOrder.' LIMIT '.$limit;
    $logs = array();
    foreach ( dbFetchAll($sql, NULL, $values) as $log ) {

      $log['DateTime'] = strftime('%Y-%m-%d %H:%M:%S', intval($log['TimeKey']));
      #Warning("TimeKey: " . $log['TimeKey'] . 'Intval:'.intval($log['TimeKey']).' DateTime:'.$log['DateTime']);
      #$log['DateTime'] = preg_replace('/^\d+/', strftime('%Y-%m-%d %H:%M:%S', intval($log['TimeKey'])), $log['TimeKey']);
      $log['Server'] = ( $log['ServerId'] and isset($servers_by_Id[$log['ServerId']]) ) ? $servers_by_Id[$log['ServerId']]->Name() : '';
      $log['Message'] = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $log['Message']);
      foreach( $filterFields as $field ) {
        if ( !isset($options[$field]) ) 
          $options[$field] = array();
        $value = $log[$field];

        if ( $field == 'Level' ) {
          if ( $value <= Logger::INFO )
            $options[$field][$value] = Logger::$codes[$value];
          else
            $options[$field][$value] = 'DB'.$value;
        } else if ( $field == 'ServerId' ) {
            $options['ServerId'][$value] = ( $value and isset($servers_by_Id[$value]) ) ? $servers_by_Id[$value]->Name() : '';
        } else if ( isset($log[$field]) ) {
          $options[$field][$log[$field]] = $value;
        }
      }
      $logs[] = $log;
    }

    $available = count($logs);
    ajaxResponse( array(
      'updated' => preg_match('/%/', DATE_FMT_CONSOLE_LONG)?strftime(DATE_FMT_CONSOLE_LONG):date(DATE_FMT_CONSOLE_LONG), 
      'total' => $total,
      'available' => isset($available) ? $available : $total,
      'logs' => $logs,
      'state' => logState(),
      'options' => $options,
    ) );
    break;
  }
  case 'export' :
  {
    if ( !canView('System') )
      ajaxError('Insufficient permissions to export logs');

    $minTime = isset($_POST['minTime'])?$_POST['minTime']:NULL;
    $maxTime = isset($_POST['maxTime'])?$_POST['maxTime']:NULL;
    if ( !is_null($minTime) && !is_null($maxTime) && $minTime > $maxTime ) {
      $tempTime = $minTime;
      $minTime = $maxTime;
      $maxTime = $tempTime;
    }
    //$limit = isset($_POST['limit'])?$_POST['limit']:1000;
    $filter = isset($_POST['filter'])?$_POST['filter']:array();
    $sortField = 'TimeKey';
    if ( isset($_POST['sortField']) ) {
      if ( ! in_array( $_POST['sortField'], $filterFields ) and ( $_POST['sortField'] != 'TimeKey' ) ) {
        Error("Invalid sort field " . $_POST['sortField'] );
      } else {
        $sortField = $_POST['sortField'];
      }
    }
    $sortOrder = (isset($_POST['sortOrder']) and $_POST['sortOrder']) == 'asc' ? 'asc':'desc';

    $servers = Server::find();
    $servers_by_Id = array();
    # There is probably a better way to do this.
    foreach ( $servers as $server ) {
      $servers_by_Id[$server->Id()] = $server;
    }

    $sql = 'SELECT * FROM Logs';
    $where = array();
    $values = array();
    if ( $minTime ) {
      Logger::Debug("MinTime: $minTime");
      if ( preg_match('/(.+)(\.\d+)/', $minTime, $matches) ) {
        # This handles sub second precision
        $minTime = strtotime($matches[1]).$matches[2];
        Logger::Debug("MinTime: $minTime");
      } else {
        $minTime = strtotime($minTime);
      }
      $where[] = 'TimeKey >= ?';
      $values[] = $minTime;
    }
    if ( $maxTime ) {
      if ( preg_match('/(.+)(\.\d+)/', $maxTime, $matches) ) {
        $maxTime = strtotime($matches[1]).$matches[2];
      } else {
        $maxTime = strtotime($maxTime);
      }
      $where[] = 'TimeKey <= ?';
      $values[] = $maxTime;
    }
    foreach ( $filter as $field=>$value ) {
      if ( $value != '' ) {
        if ( $field == 'Level' ) {
          $where[] = $field.' <= ?';
          $values[] = $value;
        } else {
          $where[] = $field.' = ?';
          $values[] = $value;
        }
      }
    }
    if ( count($where) )
      $sql.= ' WHERE '.join( ' AND ', $where );
    $sql .= ' ORDER BY '.$sortField.' '.$sortOrder;
    //$sql .= " limit ".dbEscape($limit);
    $format = isset($_POST['format'])?$_POST['format']:'text';
    switch( $format ) {
      case 'text' :
        $exportExt = 'txt';
        break;
      case 'tsv' :
        $exportExt = 'tsv';
        break;
      case 'html' :
        $exportExt = 'html';
        break;
      case 'xml' :
        $exportExt = 'xml';
        break;
      default :
        Fatal("Unrecognised log export format '$format'");
    }
    $exportKey = substr(md5(rand()),0,8);
    $exportFile = "zm-log.$exportExt";
    if ( ! file_exists(ZM_DIR_EXPORTS) ) {
      Logger::Debug('Creating ' . ZM_DIR_EXPORTS);
      if ( ! mkdir(ZM_DIR_EXPORTS) ) {
        Fatal("Can't create exports dir at '".ZM_DIR_EXPORTS."'");
      }
    }
    $exportPath = ZM_DIR_EXPORTS."/zm-log-$exportKey.$exportExt";
    Logger::Debug("Exporting to $exportPath");
    if ( !($exportFP = fopen($exportPath, 'w')) )
      Fatal("Unable to open log export file $exportPath");
    $logs = array();
    foreach ( dbFetchAll($sql, NULL, $values) as $log ) {
      $log['DateTime'] = preg_replace('/^\d+/', strftime( "%Y-%m-%d %H:%M:%S", intval($log['TimeKey']) ), $log['TimeKey']);
      $log['Server'] = ( $log['ServerId'] and isset($servers_by_Id[$log['ServerId']]) ) ? $servers_by_Id[$log['ServerId']]->Name() : '';
      $logs[] = $log;
    }
    Logger::Debug(count($logs)." lines being exported by $sql " . implode(',',$values));

  switch( $format ) {
    case 'text' :
    {
      foreach ( $logs as $log ) {
        if ( $log['Line'] )
          fprintf( $exportFP, "%s %s[%d].%s-%s/%d [%s]\n",
            $log['DateTime'], $log['Component'], $log['Pid'], $log['Code'], $log['File'], $log['Line'], $log['Message'] );
        else
          fprintf( $exportFP, "%s %s[%d].%s-%s [%s]\n",
            $log['DateTime'], $log['Component'], $log['Pid'], $log['Code'], $log['File'], $log['Message'] );
      }
      break;
    }
    case 'tsv' :
    {
      # This line doesn't need fprintf, it could use fwrite
      fprintf( $exportFP, join( "\t",
        translate('DateTime'),
        translate('Component'),
        translate('Server'),
        translate('Pid'),
        translate('Level'),
        translate('Message'),
        translate('File'),
        translate('Line')
      )."\n" );
      foreach ( $logs as $log ) {
        fprintf( $exportFP, "%s\t%s\t%s\t%d\t%s\t%s\t%s\t%s\n", $log['DateTime'], $log['Component'], $log['Server'], $log['Pid'], $log['Code'], $log['Message'], $log['File'], $log['Line'] );
      }
      break;
    }
    case 'html' :
    {
    fwrite( $exportFP,
      '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <title>'.translate('ZoneMinderLog').'</title>
      <style type="text/css">
      body, h3, p, table, td {
      font-family: Verdana, Arial, Helvetica, sans-serif;
      font-size: 11px;
  }
  table {
  border-collapse: collapse;
  width: 100%;
  }
  th {
  font-weight: bold;
  }
  th, td {
  border: 1px solid #888888;
  padding: 1px 2px;
  }
  tr.log-fat td {
  background-color:#ffcccc;
  font-weight: bold;
  font-style: italic;
  }
  tr.log-err td {
  background-color:#ffcccc;
  }
  tr.log-war td {
  background-color: #ffe4b5;
  }
  tr.log-dbg td {
  color: #666666;
  font-style: italic;
  }
  </style>
    </head>
    <body>
    <h3>'.translate('ZoneMinderLog').'</h3>
    <p>'.htmlspecialchars(preg_match( '/%/', DATE_FMT_CONSOLE_LONG )?strftime( DATE_FMT_CONSOLE_LONG ):date( DATE_FMT_CONSOLE_LONG )).'</p>
    <p>'.count($logs).' '.translate('Logs').'</p>
    <table>
    <tbody>
    <tr><th>'.translate('DateTime').'</th><th>'.translate('Component').'</th><th>'.translate('Server').'</th><th>'.translate('Pid').'</th><th>'.translate('Level').'</th><th>'.translate('Message').'</th><th>'.translate('File').'</th><th>'.translate('Line').'</th></tr>
    ' );
    foreach ( $logs as $log ) {
      $classLevel = $log['Level'];
      if ( $classLevel < Logger::FATAL )
        $classLevel = Logger::FATAL;
      elseif ( $classLevel > Logger::DEBUG )
        $classLevel = Logger::DEBUG;
      $logClass = 'log-'.strtolower(Logger::$codes[$classLevel]);
      fprintf( $exportFP, "        <tr class=\"%s\"><td>%s</td><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n", $logClass, $log['DateTime'], $log['Component'], $log['Server'], $log['Pid'], $log['Code'], $log['Message'], $log['File'], $log['Line'] );
    }
    fwrite( $exportFP, 
      '      </tbody>
      </table>
      </body>
      </html>' );
      break;
    }
    case 'xml' :
    {
        fwrite( $exportFP,
          '<?xml version="1.0" encoding="utf-8"?>
          <logexport title="'.translate('ZoneMinderLog').'" date="'.htmlspecialchars(preg_match( '/%/', DATE_FMT_CONSOLE_LONG )?strftime( DATE_FMT_CONSOLE_LONG ):date( DATE_FMT_CONSOLE_LONG )).'">
          <selector>'.$_POST['selector'].'</selector>' );
        foreach ( $filter as $field=>$value )
          if ( $value != '' )
            fwrite( $exportFP, 
            '  <filter>
            <'.strtolower($field).'>'.htmlspecialchars($value).'</'.strtolower($field).'>
            </filter>' );
        fwrite( $exportFP, 
          '  <columns>
          <column field="datetime">'.translate('DateTime').'</column><column field="component">'.translate('Component').'</column><column field="'.translate('Server').'</column><column field="pid">'.translate('Pid').'</column><column field="level">'.translate('Level').'</column><column field="message">'.translate('Message').'</column><column field="file">'.translate('File').'</column><column field="line">'.translate('Line').'</column>
          </columns>
          <logs count="'.count($logs).'">
          ' );
      foreach ( $logs as $log ) {
        fprintf( $exportFP, 
          "    <log>
          <datetime>%s</datetime>
          <component>%s</component>
          <server>%s</server>
          <pid>%d</pid>
          <level>%s</level>
          <message><![CDATA[%s]]></message>
          <file>%s</file>
          <line>%d</line>
          </log>\n", $log['DateTime'], $log['Component'], $log['Server'], $log['Pid'], $log['Code'], utf8_decode( $log['Message'] ), $log['File'], $log['Line'] );
      }
      fwrite( $exportFP, 
        '  </logs>
        </logexport>' );
      break;
    }
    $exportExt = 'xml';
    break;
    }
    fclose( $exportFP );
    ajaxResponse( array(
      'key' => $exportKey,
      'format' => $format,
    ) );
    break;
  }
  case 'download' :
  {
    if ( !canView('System') )
      ajaxError('Insufficient permissions to download logs');

    if ( empty($_REQUEST['key']) )
      Fatal('No log export key given');
    $exportKey = $_REQUEST['key'];
    if ( empty($_REQUEST['format']) )
      Fatal('No log export format given');
    $format = $_REQUEST['format'];

    switch( $format ) {
    case 'text' :
      $exportExt = 'txt';
      break;
    case 'tsv' :
      $exportExt = 'tsv';
      break;
    case 'html' :
      $exportExt = 'html';
      break;
    case 'xml' :
      $exportExt = 'xml';
      break;
    default :
      Fatal("Unrecognised log export format '$format'");
    }

    $exportFile = "zm-log.$exportExt";
    $exportPath = ZM_DIR_EXPORTS."/zm-log-$exportKey.$exportExt";

    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false ); // required by certain browsers
    header('Content-Description: File Transfer');
    header('Content-Disposition: attachment; filename="'.$exportFile.'"' );
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: application/force-download');
    header('Content-Length: '.filesize($exportPath));
    readfile($exportPath);
    exit(0);
    break;
  }
}
ajaxError('Unrecognised action or insufficient permissions');
?>
