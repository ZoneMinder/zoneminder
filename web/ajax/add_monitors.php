<?php
function unparse_url($parsed_url, $substitutions = array() ) { 
  $fields = array('scheme','host','port','user','pass','path','query','fragment');

  foreach ( $fields as $field ) {
    if ( isset( $substitutions[$field] ) ) {
      $parsed_url[$field] = $substitutions[$field];
    }
  }
  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
  $pass     = ($user || $pass) ? "$pass@" : ''; 
  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
  $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
  return "$scheme$user$pass$host$port$path$query$fragment"; 
}

$defaultMonitor = new Monitor();
$defaultMonitor->set(array(
  'StorageId' =>  1,
  'ServerId'  =>  'auto',
  'Function'  =>  'Record',
  'Type'      =>  'Ffmpeg',
  'Enabled'   =>  '1',
  'Colour'    =>  '4', // 32bit
  'PreEventCount' =>  0,
) );

function probe( &$url_bits ) {
  global $defaultMonitor;
  $available_streams = array();
  if ( ! isset($url_bits['port']) ) {
    // No port given, do a port scan
    foreach ( range( 2000, 2007 ) as $port ) {
      $socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
      socket_set_option( $socket,
          SOL_SOCKET,  // socket level
          SO_SNDTIMEO, // timeout option
          array(
            "sec"=>0, // Timeout in seconds
            "usec"=>500  // I assume timeout in microseconds
            )
          );
      $new_stream = null;
Info("Testing connection to " . $url_bits['host'].':'.$port);
      if ( socket_connect( $socket, $url_bits['host'], $port ) ) {
        $new_stream = $url_bits; // make a copy
        $new_stream['port'] = $port;
      } else {
        socket_close($socket); 
        Info("No connection to ".$url_bits['host'] . " on port $port");
        continue;
      }
      if ( $new_stream ) {
        if ( ! isset($new_stream['scheme'] ) )
          $new_stream['scheme'] = 'http';
        $url = unparse_url($new_stream, array('path'=>'/', 'query'=>'action=snapshot'));
        list($width, $height, $type, $attr) = getimagesize( $url );
        Info("Got $width x $height from $url");
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
  } else {
    // A port was specified, so don't need to port scan.
    $available_streams[] = $url_bits;
  }
  foreach ( $available_streams as &$stream ) {
    # check for existence in db.
    $stream['url'] = unparse_url( $stream, array( 'path'=>'/','query'=>'action=stream' ) );
    $monitors = Monitor::find_all( array( 'Path'=>$stream['url'] ) );
    if ( count($monitors ) ) {
      $stream['Monitor'] = $monitors[0];
      if ( isset( $stream['Width'] ) and ( $stream['Monitor']->Width() != $stream['Width'] ) ) {
        $stream['Warning'] .= 'Monitor width ('.$stream['Monitor']->Width().') and stream width ('.$stream['Width'].") do not match!\n";
      }
      if ( isset( $stream['Height'] ) and ( $stream['Monitor']->Height() != $stream['Height'] ) ) {
        $stream['Warning'] .= 'Monitor height ('.$stream['Monitor']->Height().') and stream width ('.$stream['Height'].") do not match!\n";
      }
    } else {
      $stream['Monitor'] = $defaultMonitor;
      if ( isset($stream['Width']) ) {
        $stream['Monitor']->Width( $stream['Width'] );
        $stream['Monitor']->Height( $stream['Height'] );
      }
    } // Monitor found or not
  } // end foreach Stream

  #$macCommandString = 'arp ' . $url_bits['host'] . " | awk 'BEGIN{ i=1; } { i++; if(i==3) print $3 }'";
  #$mac = exec($macCommandString);
  #Info("Mac $mac");
  return $available_streams;
} // end function probe

if ( canEdit( 'Monitors' ) ) {
    switch ( $_REQUEST['action'] ) {
      case 'probe' :
        {
        $available_streams = array();
        $url_bits = null;
        if ( preg_match('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', $_REQUEST['url'] ) ) {
          $url_bits = array( 'host'=>$_REQUEST['url'] );
        } else {
          $url_bits = parse_url( $_REQUEST['url'] );
        }

if ( 0 ) {
        // Shortcut test
        $monitors = Monitor::find_all( array( 'Path'=>$_REQUEST['url'] ) );
        if ( count( $monitors ) ) {
          Info("Monitor found for " . $_REQUEST['url']);
          $url_bits['url'] = $_REQUEST['url'];
          $url_bits['Monitor'] = $monitors[0];
          $available_stream[] = $url_bits;
          ajaxResponse( array ( 'Streams'=>$available_streams) );
          return;
        } # end url already has a monitor
}

        if ( ! $url_bits ) {
          ajaxError("The given URL was too malformed to parse.");
          return;
        }

        $available_streams = probe( $url_bits );

        ajaxResponse( array('Streams'=>$available_streams) );
        return;
      } // end case url_probe
      case 'import':
      {

        $file = $_FILES['import_file'];

        if ($file["error"] > 0) {
          ajaxError($file["error"]);
          return;
        } else {
          $filename = $file["name"];

        $available_streams = array();
          $row = 1;
          if (($handle = fopen($file['tmp_name'], 'r')) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
              $name = $data[0];
              $url = $data[1];
              $group = $data[2];
              Info("Have the following line data $name $url $group");

              $url_bits = null;
              if ( preg_match('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', $url) ) {
                $url_bits = array( 'host'=>$url, 'scheme'=>'http' );
              } else {
                $url_bits = parse_url( $url );
              }
              if ( ! $url_bits ) {
                Info("Bad url, skipping line $name $url $group");
                continue;
              }

              $available_streams += probe( $url_bits );

              //$url_bits['url'] = unparse_url( $url_bits );
              //$url_bits['Monitor'] = $defaultMonitor;
              //$url_bits['Monitor']->Name( $name );
              //$url_bits['Monitor']->merge( $_POST['newMonitor'] );
              //$available_streams[] = $url_bits;
              
            } // end while rows
            fclose($handle);
            ajaxResponse( array('Streams'=>$available_streams) );
          } else {
            ajaxError("Uploaded file does not exist");
            return;
          }

        }
      } // end case import
      default:
      {
        Warning("unknown action " . $_REQUEST['action'] );
      } // end ddcase default
    }
} else {
  Warning("Cannot edit monitors" );
}

ajaxError( 'Unrecognised action or insufficient permissions' );

?>
