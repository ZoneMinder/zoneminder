<?php
require_once('includes/monitor_probe.php');

$defaultMonitor = new ZM\Monitor();

function probe(&$url_bits) {
  $cameras = probeNetwork();
  return $cameras;
}

function tprobe( &$url_bits ) {
  error_reporting(0);
  global $defaultMonitor;
  $available_streams = array();
  if ( ! isset($url_bits['port']) ) {

    $cam_list_html = file_get_contents('http://'.$url_bits['host'].':5000/monitoring/');
    if ( $cam_list_html ) {
      ZM\Debug("Have content at port 5000/monitoring");
      $matches_count = preg_match_all(
          '/<a href="http:\/\/([.[:digit:]]+):([[:digit:]]+)\/\?action=stream" target="_blank">([^<]+)<\/a>/',
          $cam_list_html, $cam_list );
      ZM\Debug(print_r($cam_list,true));
    }
    if ( $matches_count ) {
      for( $index = 0; $index < $matches_count; $index ++ ) {
        $new_stream = $url_bits; // make a copy
        $new_stream['port'] = $cam_list[2][$index];
        $new_stream['Name'] = trim($cam_list[3][$index]);
        if ( ! isset($new_stream['scheme'] ) )
          $new_stream['scheme'] = 'http';
        $available_streams[] = $new_stream;          
ZM\Debug("Have new stream " . print_r($new_stream,true) );
      }
    } else {
      ZM\Info('No matches');
    }
if ( 0 ) {
    // No port given, do a port scan
    foreach ( range( 2000, 2007 ) as $port ) {
      $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
      socket_set_option( $socket,
          SOL_SOCKET,  // socket level
          SO_SNDTIMEO, // timeout option
          array(
            'sec'=>0, // Timeout in seconds
            'usec'=>500  // I assume timeout in microseconds
            )
          );
      $new_stream = null;
      Info('Testing connection to '.$url_bits['host'].':'.$port);
      if ( socket_connect( $socket, $url_bits['host'], $port ) ) {
        $new_stream = $url_bits; // make a copy
        $new_stream['port'] = $port;
      } else {
        socket_close($socket); 
        ZM\Info('No connection to '.$url_bits['host'].' on port '.$port);
        continue;
      }
      if ( $new_stream ) {
        if ( ! isset($new_stream['scheme'] ) )
          $new_stream['scheme'] = 'http';
        $url = unparse_url($new_stream, array('path'=>'/', 'query'=>'action=snapshot'));
        list($width, $height, $type, $attr) = getimagesize( $url );
        ZM\Info("Got $width x $height from $url");
        $new_stream['Width'] = $width;
        $new_stream['Height'] = $height;

        //try {
          //if ( $response = do_request( 'GET', $url ) ) {
            //$new_stream['path'] = '/';
            //$new_stream['query'] = '?action=stream';
//$image = imagecreatefromstring($response);
            ////$size = getimagesize( $image );
            //
          //} else {
            //Info("No response from $url");
          //}
        //} catch ( EXception $e ) {
          //Info("No response from $url");
        //}
        $available_streams[] = $new_stream;          
      } // end if new_Stream
    } // end foreach port to scan
} # end if 0
  } else {
    // A port was specified, so don't need to port scan.
    $available_streams[] = $url_bits;
  }
  foreach ( $available_streams as &$stream ) {
    # check for existence in db.
    $stream['url'] = unparse_url($stream, array('path'=>'/','query'=>'action=stream'));
    $monitors = ZM\Monitor::find(array('Path'=>$stream['url']));
    if ( count($monitors) ) {
      ZM\Info('Found monitors matching ' . $stream['url'] );
      $stream['Monitor'] = $monitors[0];
      if ( isset( $stream['Width'] ) and ( $stream['Monitor']->Width() != $stream['Width'] ) ) {
        $stream['Warning'] .= 'Monitor width ('.$stream['Monitor']->Width().') and stream width ('.$stream['Width'].") do not match!\n";
      }
      if ( isset( $stream['Height'] ) and ( $stream['Monitor']->Height() != $stream['Height'] ) ) {
        $stream['Warning'] .= 'Monitor height ('.$stream['Monitor']->Height().') and stream width ('.$stream['Height'].") do not match!\n";
      }
    } else {
      $stream['Monitor'] = clone $defaultMonitor;
      if ( isset($stream['Width']) ) {
        $stream['Monitor']->Width($stream['Width']);
        $stream['Monitor']->Height($stream['Height']);
      }
      if ( isset($stream['Name']) ) {
        $stream['Monitor']->Name($stream['Name']);
      }
    } // Monitor found or not
  } // end foreach Stream

  #$macCommandString = 'arp ' . $url_bits['host'] . " | awk 'BEGIN{ i=1; } { i++; if(i==3) print $3 }'";
  #$mac = exec($macCommandString);
  #Info("Mac $mac");
  return $available_streams;
} // end function probe

if (canEdit('Monitors')) {
  switch ($_REQUEST['action']) {
  case 'probe' :
  {
    $available_streams = array();
    $url = isset($_REQUEST['url']) ? $_REQUEST['url'] : '';
    $url_bits = null;
    if ( preg_match('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', $url) ) {
      $url_bits = array('host'=>$url);
    } else {
      $url_bits = parse_url($url);
    }

    if (!$url_bits) {
      ajaxError('The given URL was too malformed to parse.');
      return;
    }

    $available_streams = probe($url_bits);
    #ZM\Debug("available_streams".print_r($available_streams, true));

    ajaxResponse(array('Streams'=>$available_streams));
    return;
  } // end case url_probe
  case 'import':
  {
    if ( !isset($_FILES['import_file']) ) {
      ajaxError('No file was uploaded.');
      return;
    }
    $file = $_FILES['import_file'];

    if ( $file['error'] > 0 ) {
      ajaxError('File upload failed with error code '.$file['error']);
      return;
    }
    if ( !is_uploaded_file($file['tmp_name']) ) {
      ajaxError('Uploaded file does not exist');
      return;
    }

    if ( ($handle = fopen($file['tmp_name'], 'r')) === FALSE ) {
      ajaxError('Unable to open uploaded file');
      return;
    }

    $available_streams = array();
    $row = 0;
    while ( ($data = fgetcsv($handle, 1000, ',')) !== FALSE ) {
      $row ++;
      if ( count($data) < 2 ) {
        ZM\Info("Skipping line $row, expected at least Name,URL columns");
        continue;
      }
      $name = trim($data[0]);
      $url = trim($data[1]);
      $group = isset($data[2]) ? trim($data[2]) : '';

      // Skip an optional header row (Name,URL,Group)
      if ( $row == 1 and !strcasecmp($name, 'Name') and !strcasecmp($url, 'URL') ) {
        ZM\Debug('Skipping header row');
        continue;
      }
      if ( $url === '' ) {
        ZM\Info("Skipping line $row, no URL");
        continue;
      }
      ZM\Info("Importing line $row: $name $url $group");

      $url_bits = parse_url($url);
      $host = ($url_bits and isset($url_bits['host'])) ? $url_bits['host'] : '';

      # If a monitor already exists for this url, offer to edit it instead of adding a duplicate.
      $monitor = null;
      $existing = ZM\Monitor::find(array('Path'=>$url));
      if ( count($existing) ) $monitor = $existing[0];

      $available_streams[] = array(
        'mac'         => '',
        'description' => $name.' - '.$url,
        'url'         => $url,
        'IP'          => $host,
        'Group'       => $group,
        'camera'      => array(
          'Name'         => $name,
          'ip'           => $host,
          'Manufacturer' => '',
          'Model'        => '',
          'monitor'      => array(
            'Type' => 'Ffmpeg',
            'Path' => $url,
            'Name' => $name,
          ),
        ),
        'Monitor'     => $monitor,
      );
    } // end while rows
    fclose($handle);

    ajaxResponse(array('Streams'=>$available_streams));
    break;
  } // end case import
  default:
  ZM\Warning('unknown action '.$_REQUEST['action']);
  } // end switch action
} else {
  ZM\Warning('Cannot edit monitors');
}
ajaxError('Unrecognised action '.validHtmlStr($_REQUEST['action']).' or insufficient permissions for user '.validHtmlStr($user->Username()));
?>
