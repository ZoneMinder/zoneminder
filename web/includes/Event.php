<?php
require_once( 'database.php' );
require_once( 'Storage.php' );

class Event {
  public function __construct( $IdOrRow ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT *,unix_timestamp(StartTime) as Time FROM Events WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load Event record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        Error("Unknown argument passed to Event Constructor ($IdOrRow)");
        return;
      }
    } # end if isset($IdOrRow)

    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
    } else {
      Error("No row for Event " . $IdOrRow );
    }
  } // end function __construct
  public function Storage() {
    return new Storage( isset($this->{'StorageId'}) ? $this->{'StorageId'} : NULL );
  }
  public function __call( $fn, array $args){
    if(isset($this->{$fn})){
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
    $event_path = "";

    if ( ZM_USE_DEEP_STORAGE )
    {
      $event_path = 
        $this->{'MonitorId'}
      .'/'.strftime( "%y/%m/%d/%H/%M/%S",
          $this->Time()
          )
        ;
    }
    else
    {
      $event_path = 
        $this->{'MonitorId'}
      .'/'.$this->{'Id'}
      ;
    }

    return( $event_path );

  }

  public function LinkPath() {
    if ( ZM_USE_DEEP_STORAGE ) {
      return $this->{'MonitorId'} .'/'.strftime( "%y/%m/%d/.", $this->Time()).$this->{'Id'};
    }
    Error("Calling Link_Path when not using deep storage");
    return '';
  }

  public function delete() {
    dbQuery( 'DELETE FROM Events WHERE Id = ?', array($this->{'Id'}) );
    if ( !ZM_OPT_FAST_DELETE ) {
      dbQuery( 'DELETE FROM Stats WHERE EventId = ?', array($this->{'Id'}) );
      dbQuery( 'DELETE FROM Frames WHERE EventId = ?', array($this->{'Id'}) );
      if ( ZM_USE_DEEP_STORAGE ) {

# Assumption: All events haev a start time
        $start_date = date_parse( $this->{'StartTime'} );
        $start_date['year'] = $start_date['year'] % 100;

        $Storage = $this->Storage();
# So this is  because ZM creates a link under teh day pointing to the time that the event happened. 
        $eventlink_path = $Storage->Path().'/'.$this->Link_Path();

        if ( $id_files = glob( $eventlink_path ) ) {
# I know we are using arrays here, but really there can only ever be 1 in the array
          $eventPath = preg_replace( '/\.'.$event['Id'].'$/', readlink($id_files[0]), $id_files[0] );
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

public function getStreamSrc( $args, $querySep='&amp;' ) {
    return ZM_BASE_URL.'/index.php?view=view_video&eid='.$this->{'Id'};

    $streamSrc = ZM_BASE_URL.ZM_PATH_ZMS;

    $args[] = "source=event&event=".$this->{'Id'};

    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_AUTH_RELAY == "hashed" ) {
        $args[] = "auth=".generateAuthHash( ZM_AUTH_HASH_IPS );
      } elseif ( ZM_AUTH_RELAY == "plain" ) {
        $args[] = "user=".$_SESSION['username'];
        $args[] = "pass=".$_SESSION['password'];
      } elseif ( ZM_AUTH_RELAY == "none" ) {
        $args[] = "user=".$_SESSION['username'];
      }
    }
    if ( !in_array( "mode=single", $args ) && !empty($GLOBALS['connkey']) ) {
      $args[] = "connkey=".$GLOBALS['connkey'];
    }
    if ( ZM_RAND_STREAM ) {
      $args[] = "rand=".time();
    }

    if ( count($args) ) {
      $streamSrc .= "?".join( $querySep, $args );
    }

    return( $streamSrc );
  } // end function getStreamSrc
  function DiskSpace() {
    return folder_size( $this->Path() );
  }
} # end class
?>
