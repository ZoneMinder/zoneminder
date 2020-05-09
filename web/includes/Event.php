<?php
namespace ZM;
require_once('Storage.php');
require_once('functions.php');
require_once('Object.php');

class Event extends ZM_Object {
  protected static $table = 'Events';

  protected $defaults = array(
    'Id' => null,
    'Name' => '',
    'MonitorId' => null,
    'StorageId' => null,
    'SecondaryStorageId' => null,
    'Cause' => '',
    'StartTime' => null,
    'EndTime' => null,
    'Width' => null,
    'Height' => null,
    'Length' => null,
    'Frames' => null,
    'AlarmFrames' => null,
    'DefaultVideo' => '',
    'SaveJPEGs' => 0,
    'TotScore' => 0,
    'AvgScore' => 0,
    'MaxScore' => 0,
    'Archived' => 0,
    'Videoed'  => 0,
    'Uploaded' => 0,
    'Emailed' => 0,
    'Messaged' => 0,
    'Executed' => 0,
    'Notes' => '',
    'StateId' => 0,
    'Orientation' => 0,
    'DiskSpace' => null,
    'Scheme' => 0,
    'Locked' => 0,
);
  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public static function clear_cache() {
    return ZM_Object::_clear_cache(get_class());
  }

  public function Storage( $new = null ) {
    if ( $new ) {
      $this->{'Storage'} = $new;
    }
    if ( ! ( property_exists($this, 'Storage') and $this->{'Storage'} ) ) {
      if ( isset($this->{'StorageId'}) and $this->{'StorageId'} )
        $this->{'Storage'} = Storage::find_one(array('Id'=>$this->{'StorageId'}));
      if ( ! ( property_exists($this, 'Storage') and $this->{'Storage'} ) )
        $this->{'Storage'} = new Storage(NULL);
    }
    return $this->{'Storage'};
  }

  public function SecondaryStorage( $new = null ) {
    if ( $new ) {
      $this->{'SecondaryStorage'} = $new;
    }
    if ( ! ( property_exists($this, 'SecondaryStorage') and $this->{'SecondaryStorage'} ) ) {
      if ( isset($this->{'SecondaryStorageId'}) and $this->{'SecondaryStorageId'} )
        $this->{'SecondaryStorage'} = Storage::find_one(array('Id'=>$this->{'SecondaryStorageId'}));
      if ( ! ( property_exists($this, 'SecondaryStorage') and $this->{'SecondaryStorage'} ) )
        $this->{'SecondaryStorage'} = new Storage(NULL);
    }
    return $this->{'SecondaryStorage'};
  }

  public function Monitor() {
    if ( isset($this->{'MonitorId'}) ) {
      $Monitor = Monitor::find_one(array('Id'=>$this->{'MonitorId'}));
      if ( $Monitor )
        return $Monitor;
    }
    return new Monitor();
  }

  public function Time() {
    if ( ! isset($this->{'Time'}) ) {
      $this->{'Time'} = strtotime($this->{'StartTime'});
    }
    return $this->{'Time'};
  }

  public function Path() {
    $Storage = $this->Storage();
    if ( $Storage->Path() and $this->Relative_Path() ) {
      return $Storage->Path().'/'.$this->Relative_Path();
    } else {
      Error('Event Path not complete. Storage: '.$Storage->Path().' relative: '.$this->Relative_Path());
      return '';
    }
  }

  public function Relative_Path() {
    $event_path = '';

    if ( $this->{'Scheme'} == 'Deep' ) {
      $event_path = $this->{'MonitorId'}.'/'.strftime('%y/%m/%d/%H/%M/%S', $this->Time());
    } else if ( $this->{'Scheme'} == 'Medium' ) {
      $event_path = $this->{'MonitorId'}.'/'.strftime('%Y-%m-%d', $this->Time()).'/'.$this->{'Id'};
    } else {
      $event_path = $this->{'MonitorId'}.'/'.$this->{'Id'};
    }

    return $event_path;
  } // end function Relative_Path()

  public function Link_Path() {
    if ( $this->{'Scheme'} == 'Deep' ) {
      return $this->{'MonitorId'}.'/'.strftime('%y/%m/%d/.', $this->Time()).$this->{'Id'};
    }
    Error('Calling Link_Path when not using deep storage');
    return '';
  }

  public function delete() {
    if ( ! $this->{'Id'} ) {
      Error('Event delete on event with empty Id');
      return;
    }
    if ( ZM_OPT_FAST_DELETE ) {
      dbQuery('DELETE FROM Events WHERE Id = ?', array($this->{'Id'}));
      return;
    }

    global $dbConn;
    $dbConn->beginTransaction();
    try {
      $this->lock();
      dbQuery('DELETE FROM Stats WHERE EventId = ?', array($this->{'Id'}));
      dbQuery('DELETE FROM Frames WHERE EventId = ?', array($this->{'Id'}));
      if ( $this->{'Scheme'} == 'Deep' ) {

        # Assumption: All events have a start time
        $start_date = date_parse($this->{'StartTime'});
        if ( ! $start_date ) {
          throw new Exception('Unable to parse start time for event ' . $this->{'Id'} . ' not deleting files.');
        }
        $start_date['year'] = $start_date['year'] % 100;

        # So this is because ZM creates a link under the day pointing to the time that the event happened. 
        $link_path = $this->Link_Path();
        if ( ! $link_path ) {
          throw new Exception('Unable to determine link path for event '.$this->{'Id'}.' not deleting files.');
        }

        $Storage = $this->Storage();
        $eventlink_path = $Storage->Path().'/'.$link_path;

        if ( $id_files = glob($eventlink_path) ) {
          if ( ! $eventPath = readlink($id_files[0]) ) {
            throw new Exception("Unable to read link at $id_files[0]");
          }
          # I know we are using arrays here, but really there can only ever be 1 in the array
          $eventPath = preg_replace('/\.'.$this->{'Id'}.'$/', $eventPath, $id_files[0]);
          deletePath($eventPath);
          deletePath($id_files[0]);
          $pathParts = explode('/', $eventPath);
          for ( $i = count($pathParts)-1; $i >= 2; $i-- ) {
            $deletePath = join('/', array_slice($pathParts, 0, $i));
            if ( !glob($deletePath.'/*') ) {
              deletePath($deletePath);
            }
          }
        } else {
          Warning("Found no event files under $eventlink_path");
        } # end if found files
      } else {
        $eventPath = $this->Path();
        if ( ! $eventPath ) {
          throw new Exception('No event Path in Event delete. Not deleting');
        }
        deletePath($eventPath);
        if ( $this->SecondaryStorageId() ) {
          $Storage = $this->SecondaryStorage();
          if ( $Storage->Id() ) {
            $eventPath = $Storage->Path().'/'.$this->Relative_Path();
            if ( ! $eventPath ) {
              Error('No event Path in Event delete. Not deleting');
            } else {
              deletePath($eventPath);
            }
          } # end if Storage
        } # end if has Secondary Storage
      } # USE_DEEP_STORAGE OR NOT
      dbQuery('DELETE FROM Events WHERE Id = ?', array($this->{'Id'}));
      $dbConn->commit();
    } catch (PDOException $e) {
      $dbConn->rollback();
    } catch (Exception $e) {
      Error($e->getMessage());
      $dbConn->rollback();
    }
  } # end Event->delete

  public function getStreamSrc( $args=array(), $querySep='&' ) {

    $streamSrc = '';
    $Server = null;
    if ( $this->Storage()->ServerId() ) {
      # The Event may have been moved to Storage on another server,
      # So prefer viewing the Event from the Server that is actually
      # storing the video
      $Server = $this->Storage()->Server();
    } else if ( $this->Monitor()->ServerId() ) {
      # Assume that the server that recorded it has it
      $Server = $this->Monitor()->Server();
    } else {
      # A default Server will result in the use of ZM_DIR_EVENTS
      $Server = new Server();
    }

    # If we are in a multi-port setup, then use the multiport, else by
    # passing null Server->Url will use the Port set in the Server setting
    $streamSrc .= $Server->Url(
      ZM_MIN_STREAMING_PORT ?
      ZM_MIN_STREAMING_PORT+$this->{'MonitorId'} :
      null);

    if ( $this->{'DefaultVideo'} and $args['mode'] != 'jpeg' ) {
      $streamSrc .= $Server->PathToIndex();
      $args['eid'] = $this->{'Id'};
      $args['view'] = 'view_video';
    } else {
      $streamSrc .= $Server->PathToZMS();

      $args['source'] = 'event';
      $args['event'] = $this->{'Id'};
      if ( ( (!isset($args['mode'])) or ( $args['mode'] != 'single' ) ) && !empty($GLOBALS['connkey']) ) {
        $args['connkey'] = $GLOBALS['connkey'];
      }
      if ( ZM_RAND_STREAM ) {
        $args['rand'] = time();
      }
    }

    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_AUTH_RELAY == 'hashed' ) {
        $args['auth'] = generateAuthHash(ZM_AUTH_HASH_IPS);
      } else if ( ZM_AUTH_RELAY == 'plain' ) {
        $args['user'] = $_SESSION['username'];
        $args['pass'] = $_SESSION['password'];
      } else if ( ZM_AUTH_RELAY == 'none' ) {
        $args['user'] = $_SESSION['username'];
      }
    }

    $streamSrc .= '?'.http_build_query($args, '', $querySep);

    return $streamSrc;
  } // end function getStreamSrc

  function DiskSpace( $new='' ) {
    if ( is_null($new) or ( $new != '' ) ) {
      $this->{'DiskSpace'} = $new;
    }
    if ( (!property_exists($this, 'DiskSpace')) or (null === $this->{'DiskSpace'}) ) {
      $this->{'DiskSpace'} = folder_size($this->Path());
      dbQuery('UPDATE Events SET DiskSpace=? WHERE Id=?', array($this->{'DiskSpace'}, $this->{'Id'}));
    }
    return $this->{'DiskSpace'};
  }

  function createListThumbnail( $overwrite=false ) {
	# The idea here is that we don't really want to use the analysis jpeg as the thumbnail.  
	# The snapshot image will be generated during capturing
    if ( file_exists($this->Path().'/snapshot.jpg') ) {
      Logger::Debug("snapshot exists");
      $frame = null;
    } else {
      # Load the frame with the highest score to use as a thumbnail
      if ( !($frame = dbFetchOne( 'SELECT * FROM Frames WHERE EventId=? AND Score=? ORDER BY FrameId LIMIT 1', NULL, array( $this->{'Id'}, $this->{'MaxScore'} ) )) ) {
        Error("Unable to find a Frame matching max score " . $this->{'MaxScore'} . ' for event ' . $this->{'Id'} );
        // FIXME: What if somehow the db frame was lost or score was changed?  Should probably try another search for any frame.
        return false;
      }
    }

    $imageData = $this->getImageSrc($frame, $scale, false, $overwrite);
    if ( ! $imageData ) {
      return false;
    }
    $thumbData = $frame;
    $thumbData['Path'] = $imageData['thumbPath'];
    $thumbData['Width'] = $this->ThumbnailWidth();
    $thumbData['Height'] = $this->ThumbnailHeight();
    $thumbData['url'] = '?view=image&amp;eid='.$this->Id().'&amp;fid='.$imageData['FrameId'].'&amp;width='.$thumbData['Width'].'&amp;height='.$thumbData['Height'];

    return $thumbData;
  } // end function createListThumbnail

  function ThumbnailWidth( ) {
    if ( ! ( property_exists($this, 'ThumbnailWidth') ) ) {
      if ( ZM_WEB_LIST_THUMB_WIDTH ) {
        $this->{'ThumbnailWidth'} = ZM_WEB_LIST_THUMB_WIDTH;
        $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_WIDTH)/$this->{'Width'};
        $this->{'ThumbnailHeight'} = reScale( $this->{'Height'}, $scale );
      } elseif ( ZM_WEB_LIST_THUMB_HEIGHT ) {
        $this->{'ThumbnailHeight'} = ZM_WEB_LIST_THUMB_HEIGHT;
        $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_HEIGHT)/$this->{'Height'};
        $this->{'ThumbnailWidth'} = reScale( $this->{'Width'}, $scale );
      } else {
        Fatal( "No thumbnail width or height specified, please check in Options->Web" );
      }
    }
    return $this->{'ThumbnailWidth'};
  } // end function ThumbnailWidth

  function ThumbnailHeight( ) {
    if ( ! ( property_exists($this, 'ThumbnailHeight') ) ) {
      if ( ZM_WEB_LIST_THUMB_WIDTH ) {
        $this->{'ThumbnailWidth'} = ZM_WEB_LIST_THUMB_WIDTH;
        $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_WIDTH)/$this->{'Width'};
        $this->{'ThumbnailHeight'} = reScale( $this->{'Height'}, $scale );
      } elseif ( ZM_WEB_LIST_THUMB_HEIGHT ) {
        $this->{'ThumbnailHeight'} = ZM_WEB_LIST_THUMB_HEIGHT;
        $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_HEIGHT)/$this->{'Height'};
        $this->{'ThumbnailWidth'} = reScale( $this->{'Width'}, $scale );
      } else {
        Fatal( "No thumbnail width or height specified, please check in Options->Web" );
      }
    }
    return $this->{'ThumbnailHeight'};
  } // end function ThumbnailHeight

  function getThumbnailSrc( $args=array(), $querySep='&' ) {
    # The thumbnail is theoretically the image with the most motion.
# We always store at least 1 image when capturing

    $streamSrc = '';
    $Server = null;
    if ( $this->Storage()->ServerId() ) {
      $Server = $this->Storage()->Server();
    } else if ( $this->Monitor()->ServerId() ) {
      # Assume that the server that recorded it has it
      $Server = $this->Monitor()->Server();
    } else {
      $Server = new Server();
    }
    $streamSrc .= $Server->UrlToIndex(
      ZM_MIN_STREAMING_PORT ?
      ZM_MIN_STREAMING_PORT+$this->{'MonitorId'} :
      null);

    $args['eid'] = $this->{'Id'};
    $args['fid'] = 'snapshot';
    $args['view'] = 'image';
    $args['width'] = $this->ThumbnailWidth();
    $args['height'] = $this->ThumbnailHeight();

    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_AUTH_RELAY == 'hashed' ) {
        $args['auth'] = generateAuthHash(ZM_AUTH_HASH_IPS);
      } else if ( ZM_AUTH_RELAY == 'plain' ) {
        $args['user'] = $_SESSION['username'];
        $args['pass'] = $_SESSION['password'];
      } else if ( ZM_AUTH_RELAY == 'none' ) {
        $args['user'] = $_SESSION['username'];
      }
    }

    return $streamSrc.'?'.http_build_query($args,'', $querySep);
  } // end function getThumbnailSrc

  // frame is an array representing the db row for a frame.
  function getImageSrc($frame, $scale=SCALE_BASE, $captureOnly=false, $overwrite=false) {
    $Storage = $this->Storage();
    $Event = $this;
    $eventPath = $Event->Path();

    if ( $frame and ! is_array($frame) ) {
      # Must be an Id
      Logger::Debug("Assuming that $frame is an Id");
      $frame = array( 'FrameId'=>$frame, 'Type'=>'', 'Delta'=>0 );
    }

    if ( ( ! $frame ) and file_exists($eventPath.'/snapshot.jpg') ) {
      # No frame specified, so look for a snapshot to use
      $captImage = 'snapshot.jpg';
      Logger::Debug("Frame not specified, using snapshot");
      $frame = array('FrameId'=>'snapshot', 'Type'=>'','Delta'=>0);
    } else {
      $captImage = sprintf( '%0'.ZM_EVENT_IMAGE_DIGITS.'d-analyze.jpg', $frame['FrameId'] );
      if ( ! file_exists( $eventPath.'/'.$captImage ) ) {
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
            Error("Can't create frame images from video because there is no video file for event ".$Event->Id().' at ' .$Event->Path() );
          }
        } // end if capture file exists
      } // end if analyze file exists
    } // end if frame or snapshot

    $captPath = $eventPath.'/'.$captImage;
    if ( ! file_exists($captPath) ) {
      Error("Capture file does not exist at $captPath");
    }
    
    $analImage = sprintf('%0'.ZM_EVENT_IMAGE_DIGITS.'d-analyse.jpg', $frame['FrameId']);
    $analPath = $eventPath.'/'.$analImage;

    $alarmFrame = $frame['Type']=='Alarm';

    $hasAnalImage = $alarmFrame && file_exists($analPath) && filesize($analPath);
    $isAnalImage = $hasAnalImage && !$captureOnly;

    if ( !ZM_WEB_SCALE_THUMBS || ($scale >= SCALE_BASE) || !function_exists('imagecreatefromjpeg') ) {
      $imagePath = $thumbPath = $isAnalImage ? $analPath : $captPath;
      $imageFile = $imagePath;
      $thumbFile = $thumbPath;
    } else {
      if ( version_compare(phpversion(), '4.3.10', '>=') )
        $fraction = sprintf('%.3F', $scale/SCALE_BASE);
      else
        $fraction = sprintf('%.3f', $scale/SCALE_BASE);
      $scale = (int)round($scale);

      $thumbCaptPath = preg_replace( '/\.jpg$/', "-$scale.jpg", $captPath );
      $thumbAnalPath = preg_replace( '/\.jpg$/', "-$scale.jpg", $analPath );

      if ( $isAnalImage ) {
        $imagePath = $analPath;
        $thumbPath = $thumbAnalPath;
      } else {
        $imagePath = $captPath;
        $thumbPath = $thumbCaptPath;
      }

      $thumbFile = $thumbPath;
      if ( $overwrite || ! file_exists($thumbFile) || ! filesize($thumbFile) ) {
        // Get new dimensions
        list( $imageWidth, $imageHeight ) = getimagesize($imagePath);
        $thumbWidth = $imageWidth * $fraction;
        $thumbHeight = $imageHeight * $fraction;

        // Resample
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        $image = imagecreatefromjpeg($imagePath);
        imagecopyresampled($thumbImage, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imageWidth, $imageHeight);

        if ( !imagejpeg($thumbImage, $thumbPath) )
          Error("Can't create thumbnail '$thumbPath'");
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
        'FrameId'		=>	$frame['FrameId'],
        );

    return $imageData;
  }

  public function link_to($text=null) {
    if ( !$text )
      $text = $this->{'Id'};
    return '<a href="?view=event&amp;eid='. $this->{'Id'}.'">'.$text.'</a>';
  }

  public function file_exists() {
    if ( file_exists( $this->Path().'/'.$this->DefaultVideo() ) ) {
      return true;
    }
    if ( !defined('ZM_SERVER_ID') ) {
      return false;
    }
    $Storage= $this->Storage();
    $Server = $Storage->ServerId() ? $Storage->Server() : $this->Monitor()->Server();
    if ( $Server->Id() != ZM_SERVER_ID ) {

      $url = $Server->UrlToApi() . '/events/'.$this->{'Id'}.'.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '?auth='.generateAuthHash( ZM_AUTH_HASH_IPS );
        } else if ( ZM_AUTH_RELAY == 'plain' ) {
          $url .= '?user='.$_SESSION['username'];
          $url .= '?pass='.$_SESSION['password'];
        } else {
          Error('Multi-Server requires AUTH_RELAY be either HASH or PLAIN');
          return;
        }
      }
      Logger::Debug("sending command to $url");
      // use key 'http' even if you send the request to https://...
      $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'GET',
            'content' => ''
            )
          );
      $context  = stream_context_create($options);
      try {
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE) { /* Handle error */
          Error("Error restarting zmc using $url");
        }
        $event_data = json_decode($result,true);
        Logger::Debug(print_r($event_data['event']['Event'],1));
        return $event_data['event']['Event']['fileExists'];
      } catch ( Exception $e ) {
        Error("Except $e thrown trying to get event data");
      }
    } # end if not local
    return false;
  } # end public function file_exists()

  public function file_size() {
    if ( file_exists($this->Path().'/'.$this->DefaultVideo()) ) {
      return filesize($this->Path().'/'.$this->DefaultVideo());
    }
    if ( !defined('ZM_SERVER_ID') ) {
      return false;
    }
    $Storage= $this->Storage();
    $Server = $Storage->ServerId() ? $Storage->Server() : $this->Monitor()->Server();
    if ( $Server->Id() != ZM_SERVER_ID ) {

      $url = $Server->UrlToApi().'/events/'.$this->{'Id'}.'.json';
      if ( ZM_OPT_USE_AUTH ) {
        if ( ZM_AUTH_RELAY == 'hashed' ) {
          $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
        } elseif ( ZM_AUTH_RELAY == 'plain' ) {
          $url .= '?user='.$_SESSION['username'];
          $url .= '?pass='.$_SESSION['password'];
        } else {
          Error('Multi-Server requires AUTH_RELAY be either HASH or PLAIN');
          return;
        }
      }
      Logger::Debug("sending command to $url");
      // use key 'http' even if you send the request to https://...
      $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'GET',
            'content' => ''
            )
          );
      $context = stream_context_create($options);
      try {
        $result = file_get_contents($url, false, $context);
        if ( $result === FALSE ) { /* Handle error */
          Error("Error restarting zmc using $url");
        }
        $event_data = json_decode($result,true);
        Logger::Debug(print_r($event_data['event']['Event'], 1));
        return $event_data['event']['Event']['fileSize'];
      } catch ( Exception $e ) {
        Error("Except $e thrown trying to get event data");
      }
    } # end if not local
    return 0;
  } # end public function file_size()

  public function can_delete() {
    if ( $this->Archived() ) {
      return false;
    }
    if ( !$this->EndTime() ) {
      return false;
    }
    if ( !canEdit('Events') ) {
      return false;
    }

    return true;
  }

  public function cant_delete_reason() {
    if ( $this->Archived() ) {
      return 'You cannot delete an archived event. Unarchive it first.';
    } else if ( ! $this->EndTime() ) {
      return 'You cannot delete an event while it is being recorded. Wait for it to finish.';
    } else if ( ! canEdit('Events') ) {
      return 'You do not have rights to edit Events.';
    }
    return 'Unknown reason';
  }

} # end class

?>
