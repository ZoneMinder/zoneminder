<?php

class Event {
  public function __construct( $IdOrRow ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT *,unix_timestamp(StartTime) as Time FROM Events WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error('Unable to load Event record for Id=' . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Error("Unknown argument passed to Event Constructor from $file:$line)");
        Error("Unknown argument passed to Event Constructor ($IdOrRow)");
        return;
      }
    } # end if isset($IdOrRow)

    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
    } else {
      Error('No row for Event ' . $IdOrRow );
    }
  } // end function __construct

  public function Storage() {
    return new Storage( isset($this->{'StorageId'}) ? $this->{'StorageId'} : NULL );
  }

  public function Monitor() {
    return new Monitor( isset($this->{'MonitorId'}) ? $this->{'MonitorId'} : NULL );
  }

  public function __call( $fn, array $args){
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};
#array_unshift($args, $this);
#call_user_func_array( $this->{$fn}, $args);
    }
  }

  public function Time() {
    if ( ! isset( $this->{'Time'} ) ) {
      $this->{'Time'} = strtotime($this->{'StartTime'});
    }
    return $this->{'Time'};
  }

  public function Path() {
    $Storage = $this->Storage();
    return $Storage->Path().'/'.$this->Relative_Path();
  }

  public function Relative_Path() {
    $event_path = '';

    if ( ZM_USE_DEEP_STORAGE ) {
      $event_path = $this->{'MonitorId'} .'/'.strftime( '%y/%m/%d/%H/%M/%S', $this->Time()) ;
    } else {
      $event_path = $this->{'MonitorId'} .'/'.$this->{'Id'};
    }

    return( $event_path );
  } // end function Relative_Path()

  public function Link_Path() {
    if ( ZM_USE_DEEP_STORAGE ) {
      return $this->{'MonitorId'} .'/'.strftime( '%y/%m/%d/.', $this->Time()).$this->{'Id'};
    }
    Error('Calling Link_Path when not using deep storage');
    return '';
  }

  public function delete() {
    # This wouldn't work with foreign keys
    dbQuery( 'DELETE FROM Events WHERE Id = ?', array($this->{'Id'}) );
    if ( !ZM_OPT_FAST_DELETE ) {
      dbQuery( 'DELETE FROM Stats WHERE EventId = ?', array($this->{'Id'}) );
      dbQuery( 'DELETE FROM Frames WHERE EventId = ?', array($this->{'Id'}) );
      if ( ZM_USE_DEEP_STORAGE ) {

# Assumption: All events have a start time
        $start_date = date_parse( $this->{'StartTime'} );
        if ( ! $start_date ) {
          Error('Unable to parse start time for event ' . $this->{'Id'} . ' not deleting files.' );
          return;
        }
        $start_date['year'] = $start_date['year'] % 100;

# So this is because ZM creates a link under the day pointing to the time that the event happened. 
        $link_path = $this->Link_Path();
        if ( ! $link_path ) {
          Error('Unable to determine link path for event ' . $this->{'Id'} . ' not deleting files.' );
          return;
        }
        
        $Storage = $this->Storage();
        $eventlink_path = $Storage->Path().'/'.$link_path;

        if ( $id_files = glob( $eventlink_path ) ) {
          if ( ! $eventPath = readlink($id_files[0]) ) {
            Error("Unable to read link at $id_files[0]");
            return;
          }
# I know we are using arrays here, but really there can only ever be 1 in the array
          $eventPath = preg_replace( '/\.'.$this->{'Id'}.'$/', $eventPath, $id_files[0] );
          deletePath( $eventPath );
          deletePath( $id_files[0] );
          $pathParts = explode(  '/', $eventPath );
          for ( $i = count($pathParts)-1; $i >= 2; $i-- ) {
            $deletePath = join( '/', array_slice( $pathParts, 0, $i ) );
            if ( !glob( $deletePath."/*" ) ) {
              deletePath( $deletePath );
            }
          }
        } else {
          Warning( "Found no event files under $eventlink_path" );
        } # end if found files
      } else {
        $eventPath = $this->Path();
        deletePath( $eventPath );
      } # USE_DEEP_STORAGE OR NOT
    } # ! ZM_OPT_FAST_DELETE
  } # end Event->delete

  public function getStreamSrc( $args=array(), $querySep='&amp;' ) {
    if ( $this->{'DefaultVideo'} ) {
      return ( ZM_BASE_PATH != '/' ? ZM_BASE_PATH : '' ).'/index.php?view=view_video&eid='.$this->{'Id'};
    }

    $streamSrc = ZM_BASE_URL.ZM_PATH_ZMS;

    $args['source'] = 'event';
    $args['event'] = $this->{'Id'};

    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_AUTH_RELAY == 'hashed' ) {
        $args['auth'] = generateAuthHash( ZM_AUTH_HASH_IPS );
      } elseif ( ZM_AUTH_RELAY == 'plain' ) {
        $args['user'] = $_SESSION['username'];
        $args['pass'] = $_SESSION['password'];
      } elseif ( ZM_AUTH_RELAY == "none" ) {
        $args['user'] = $_SESSION['username'];
      }
    }
    if ( ( (!isset($args['mode'])) or ( $args['mode'] != 'single' ) ) && !empty($GLOBALS['connkey']) ) {
      $args['connkey'] = $GLOBALS['connkey'];
    }
    if ( ZM_RAND_STREAM ) {
      $args['rand'] = time();
    }

    $streamSrc .= '?'.http_build_query( $args,'', $querySep );

    return( $streamSrc );
  } // end function getStreamSrc

  function DiskSpace() {
    return folder_size( $this->Path() );
  }

  function createListThumbnail( $overwrite=false ) {
  # Load the frame with the highest score to use as a thumbnail
    if ( !($frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventId=? AND Score=? ORDER BY FrameId LIMIT 1', NULL, array( $this->{'Id'}, $this->{'MaxScore'} ) )) ) {
      Error("Unable to find a Frame matching max score " . $this->{'MaxScore'} . ' for event ' . $this->{'Id'} );
      // FIXME: What if somehow the db frame was lost or score was changed?  Should probably try another search for any frame.
      return( false );
    }

    $frameId = $frame['FrameId'];

    if ( ZM_WEB_LIST_THUMB_WIDTH ) {
      $thumbWidth = ZM_WEB_LIST_THUMB_WIDTH;
      $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_WIDTH)/$this->{'Width'};
      $thumbHeight = reScale( $this->{'Height'}, $scale );
    } elseif ( ZM_WEB_LIST_THUMB_HEIGHT ) {
      $thumbHeight = ZM_WEB_LIST_THUMB_HEIGHT;
      $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_HEIGHT)/$this->{'Height'};
      $thumbWidth = reScale( $this->{'Width'}, $scale );
    } else {
      Fatal( "No thumbnail width or height specified, please check in Options->Web" );
    }

    $imageData = $this->getImageSrc( $frame, $scale, false, $overwrite );
    if ( ! $imageData ) {
      return ( false );
    }
    $thumbData = $frame;
    $thumbData['Path'] = $imageData['thumbPath'];
    $thumbData['Width'] = (int)$thumbWidth;
    $thumbData['Height'] = (int)$thumbHeight;

    return( $thumbData );
  } // end function createListThumbnail

  // frame is an array representing the db row for a frame.
  function getImageSrc( $frame, $scale=SCALE_BASE, $captureOnly=false, $overwrite=false ) {
    $Storage = new Storage(  isset($this->{'StorageId'}) ? $this->{'StorageId'} : NULL  );
    $Event = $this;
    $eventPath = $Event->Path();

    if ( $frame and ! is_array($frame) ) {
      # Must be an Id
      Debug("Assuming that $frame is an Id");
      $frame = array( 'FrameId'=>$frame, 'Type'=>'' );
    }

    if ( ( ! $frame ) and file_exists( $eventPath.'/snapshot.jpg' ) ) {
      # No frame specified, so look for a snapshot to use
      $captImage = 'snapshot.jpg';
      Debug("Frame not specified, using snapshot");
    } else {
      $captImage = sprintf( '%0'.ZM_EVENT_IMAGE_DIGITS.'d-capture.jpg', $frame['FrameId'] );
      if ( ! file_exists( $eventPath.'/'.$captImage ) ) {
        # Generate the frame JPG
        if ( $Event->DefaultVideo() ) {
          $videoPath = $eventPath.'/'.$Event->DefaultVideo();

          if ( ! file_exists( $videoPath ) ) {
            Error("Event claims to have a video file, but it does not seem to exist at $videoPath" );
            return '';
          } 
            
          #$command ='ffmpeg -v 0 -i '.$videoPath.' -vf "select=gte(n\\,'.$frame['FrameId'].'),setpts=PTS-STARTPTS" '.$eventPath.'/'.$captImage;
          $command ='ffmpeg -ss '. $frame['Delta'] .' -i '.$videoPath.' -frames:v 1 '.$eventPath.'/'.$captImage;
          Logger::Debug( "Running $command" );
          $output = array();
          $retval = 0;
          exec( $command, $output, $retval );
          Logger::Debug("Retval: $retval, output: " . implode("\n", $output));
        } else {
          Error("Can't create frame images from video becuase there is no video file for this event (".$Event->DefaultVideo() );
        }
      }
    }

    $captPath = $eventPath.'/'.$captImage;
    if ( ! file_exists( $captPath ) ) {
      Error( "Capture file does not exist at $captPath" );
      return '';
    }
    $thumbCaptPath = ZM_DIR_IMAGES.'/'.$this->{'Id'}.'-'.$captImage;
    
    //echo "CI:$captImage, CP:$captPath, TCP:$thumbCaptPath<br>";

    $analImage = sprintf( '%0'.ZM_EVENT_IMAGE_DIGITS.'d-analyse.jpg', $frame['FrameId'] );
    $analPath = $eventPath.'/'.$analImage;

    $thumbAnalPath = ZM_DIR_IMAGES.'/'.$this->{'Id'}.'-'.$analImage;
    //echo "AI:$analImage, AP:$analPath, TAP:$thumbAnalPath<br>";

    $alarmFrame = $frame['Type']=='Alarm';

    $hasAnalImage = $alarmFrame && file_exists( $analPath ) && filesize( $analPath );
    $isAnalImage = $hasAnalImage && !$captureOnly;

    if ( !ZM_WEB_SCALE_THUMBS || $scale >= SCALE_BASE || !function_exists( 'imagecreatefromjpeg' ) ) {
      $imagePath = $thumbPath = $isAnalImage?$analPath:$captPath;
      $imageFile = $imagePath;
      $thumbFile = $thumbPath;
    } else {
      if ( version_compare( phpversion(), '4.3.10', '>=') )
        $fraction = sprintf( '%.3F', $scale/SCALE_BASE );
      else
        $fraction = sprintf( '%.3f', $scale/SCALE_BASE );
      $scale = (int)round( $scale );

      $thumbCaptPath = preg_replace( '/\.jpg$/', "-$scale.jpg", $thumbCaptPath );
      $thumbAnalPath = preg_replace( '/\.jpg$/', "-$scale.jpg", $thumbAnalPath );

      if ( $isAnalImage ) {
        $imagePath = $analPath;
        $thumbPath = $thumbAnalPath;
      } else {
        $imagePath = $captPath;
        $thumbPath = $thumbCaptPath;
      }

      $thumbFile = $thumbPath;
      if ( $overwrite || ! file_exists( $thumbFile ) || ! filesize( $thumbFile ) ) {
        // Get new dimensions
        list( $imageWidth, $imageHeight ) = getimagesize( $imagePath );
        $thumbWidth = $imageWidth * $fraction;
        $thumbHeight = $imageHeight * $fraction;

        // Resample
        $thumbImage = imagecreatetruecolor( $thumbWidth, $thumbHeight );
        $image = imagecreatefromjpeg( $imagePath );
        imagecopyresampled( $thumbImage, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imageWidth, $imageHeight );

        if ( !imagejpeg( $thumbImage, $thumbPath ) )
          Error( "Can't create thumbnail '$thumbPath'" );
      }
    } # Create thumbnails

    $imageData = array(
        'eventPath' => $eventPath,
        'imagePath' => $imagePath,
        'thumbPath' => $thumbPath,
        'imageFile' => $imagePath,
        'thumbFile' => $thumbFile,
        'imageClass' => $alarmFrame?'alarm':'normal',
        'isAnalImage' => $isAnalImage,
        'hasAnalImage' => $hasAnalImage,
        );

    return( $imageData );
  }

} # end class

?>
