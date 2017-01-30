<?php
require_once( 'database.php' );
class Storage {
  public function __construct( $IdOrRow = NULL ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
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
    } else {
      $this->{'Name'} = '';
      $this->{'Path'} = '';
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
    if(isset($this->{$fn})){
      return $this->{$fn};
#array_unshift($args, $this);
#call_user_func_array( $this->{$fn}, $args);
    }
  }
  public static function find_all() {
    $storage_areas = array();
    $result = dbQuery( 'SELECT * FROM Storage ORDER BY Name');
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Storage' );
    foreach ( $results as $row => $obj ) {
      $storage_areas[] = $obj;
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
      
    $total = disk_total_space( $path );
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
}
?>
