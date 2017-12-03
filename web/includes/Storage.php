<?php
require_once( 'database.php' );

#$storage_cache = array();
class Storage {
  public function __construct( $IdOrRow = NULL ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {

	#if ( isset( $storage_cache[$IdOrRow] ) ) {
#Warning("using cached object for $dOrRow");
	  #return $storage_cache[$IdOrRow];
	#} else {
#Warning("Not using cached object for $dOrRow");
        $row = dbFetchOne( 'SELECT * FROM Storage WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error("Unable to load Storage record for Id=" . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      }
    }
    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
      #$storage_cache[$IdOrRow] = $this;
    } else {
      $this->{'Name'} = '';
      $this->{'Path'} = '';
      $this->{'Type'} = 'local';
    }
  }

  public function Path() {
    if ( isset( $this->{'Path'} ) and ( $this->{'Path'} != '' ) ) {
      return $this->{'Path'};
    } else if ( ! isset($this->{'Id'}) ) {
      $path = ZM_DIR_EVENTS;
      if ( $path[0] != '/' ) {
        $this->{'Path'} = ZM_PATH_WEB.'/'.ZM_DIR_EVENTS;
      } else {
        $this->{'Path'} = ZM_DIR_EVENTS;
      }
      return $this->{'Path'};
      
    }
    return $this->{'Name'};
  }
  public function Name() {
    if ( isset( $this->{'Name'} ) and ( $this->{'Name'} != '' ) ) {
      return $this->{'Name'};
    } else if ( ! isset($this->{'Id'}) ) {
      return 'Default';
    }
    return $this->{'Name'};
  }

  public function __call( $fn, array $args= NULL){
  if ( count( $args )  ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};
        
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Warning( "Unknown function call Storage->$fn from $file:$line" );
    }
  }
  public static function find_all() {
    $storage_areas = array();
    $result = dbQuery( 'SELECT * FROM Storage ORDER BY Name');
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Storage' );
    foreach ( $results as $row => $obj ) {
      $storage_areas[] = $obj;
      $storage_cache[$obj->Id()] = $obj;
    }
    return $storage_areas;
  }
  public function disk_usage_percent() {
    $path = $this->Path();
    if ( ! $path ) {
      Warning("Storage::disk_usage_percent: path is empty");
      return 0;
    } else if ( ! file_exists( $path ) ) {
      Warning("Storage::disk_usage_percent: path $path does not exist");
      return 0;
    }
      
    $total = $this->disk_total_space();
    if ( ! $total ) {
      Error("disk_total_space returned false for " . $path );
      return 0;
    }
    $free = disk_free_space( $path );
    if ( ! $free ) {
      Error("disk_free_space returned false for " . $path );
    }
    $usage = round(($total - $free) / $total * 100);
    return $usage;
  }
  public function disk_total_space() {
    if ( ! array_key_exists( 'disk_total_space', $this ) ) {
      $this->{'disk_total_space'} = disk_total_space( $this->Path() );
    }
    return $this->{'disk_total_space'};
  }
  public function disk_used_space() {
    # This isn't a function like this in php, so we have to add up the space used in each event.
    if ( ! array_key_exists( 'DiskSpace', $this ) ) {
      $used = 0;
      if ( $this->{'Type'} == 's3fs' ) {
        $used = dbFetchOne('SELECT SUM(DiskSpace) AS DiskSpace FROM Events WHERE StorageId=? AND DiskSpace IS NOT NULL', 'DiskSpace', array($this->Id()) );

        foreach ( Event::find_all( array( 'StorageId'=>$this->Id(), 'DiskSpace'=>null ) ) as $Event ) {
          $Event->Storage( $this ); // Prevent further db hit
          $used += $Event->DiskSpace();
        }
      } else { 
        $path = $this->Path();
        $used = disk_total_space( $path ) - disk_free_space( $path );;
      }
      $this->{'DiskSpace'} = $used;
    }
		
    return $this->{'DiskSpace'};
  }
}
?>
