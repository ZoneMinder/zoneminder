<?php
namespace ZM;
require_once('database.php');
require_once('Event.php');
require_once('Object.php');

class Storage extends ZM_Object {
  protected static $table = 'Storage';
  protected $defaults = array(
    'Id'        => null,
    'Path'      => array('type'=>'text','filter_regexp'=>array('/[^\w\-\.\(\)\:\/ ]/','/\/$/'), 'default'=>''),
    'Name'      => '',
    'Type'      => 'local',
    'Url'       => '',
    'DiskSpace' => null,
    'Scheme'    => 'Medium',
    'ServerId'  => 0,
    'DoDelete'  => 1,
    'Enabled'   => 1,
  );
  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function Path($new=null) {
    if ( $new ) $this->{'Path'} = $new;
    if ( isset($this->{'Path'}) and ( $this->{'Path'} != '' ) ) {
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
  public function Name($new=null) {
    if ( $new )
      $this->{'Name'} = $new;
    if ( isset($this->{'Name'}) and ( $this->{'Name'} != '' ) ) {
      return $this->{'Name'};
    } else if ( ! isset($this->{'Id'}) ) {
      return 'Default';
    }
    return $this->{'Name'};
  }

  public function Events() {
    if ( $this->{'Id'} and ! isset($this->{'Events'}) ) {
      $this->{'Events'} = Event::find(array('StorageId'=>$this->{'Id'}));
    }
    if ( ! isset($this->{'Events'}) ) {
      $this->{'Events'} = array();
    }
    return $this->{'Events'};
  }

	public function EventCount() {
    if ( (! property_exists($this, 'EventCount')) or (!$this->{'EventCount'}) ) {
      $this->{'EventCount'} = dbFetchOne('SELECT COUNT(*) AS EventCount FROM Events WHERE StorageId=?', 'EventCount', array($this->Id()));
		}
		return $this->{'EventCount'};
	}

  public function disk_used_blocks() {
    $df = shell_exec('df '.escapeshellarg($this->Path()));
    $space = -1;
    if ( preg_match('/\s(\d+)\s+\d+\s+\d+%/ms', $df, $matches) )
      $space = $matches[1];
    return $space;
  }

  public function disk_usage_percent() {
    $path = $this->Path();
    if ( ! $path ) {
      Warning('Storage::disk_usage_percent: path is empty');
      return 0;
    } else if ( ! file_exists($path) ) {
      Warning("Storage::disk_usage_percent: path $path does not exist");
      return 0;
    }
      
    $total = $this->disk_total_space();
    if ( ! $total ) {
      Error('disk_total_space returned false for ' . $path);
      return 0;
    }
    $used = $this->disk_used_space();
    $usage = round(($used / $total) * 100);
    //Debug("Used $usage = round( ( $used / $total ) * 100 )");
    return $usage;
  }

  public function disk_total_space() {
    if ( !property_exists($this, 'disk_total_space') ) {
      $path = $this->Path();
      if ( file_exists($path) ) {
        $this->{'disk_total_space'} = disk_total_space($path);
      } else {
        Error("Path $path does not exist.");
        $this->{'disk_total_space'} = 0;
      }
    }
    return $this->{'disk_total_space'};
  }

  public function disk_used_space() {
    # This isn't a function like this in php, so we have to add up the space used in each event.
    if ( ( !property_exists($this, 'disk_used_space')) or !$this->{'disk_used_space'} ) {
      if ( $this->Type() == 's3fs' ) {
        $this->{'disk_used_space'} = $this->event_disk_space();
      } else { 
        $path = $this->Path();
        if ( file_exists($path) ) {
          $this->{'disk_used_space'} = disk_total_space($path) - disk_free_space($path);
        } else {
          Error("Path $path does not exist.");
          $this->{'disk_used_space'} = 0;
        }
      }
    }
    return $this->{'disk_used_space'};
  } // end function disk_used_space

  public function event_disk_space() {
    # This isn't a function like this in php, so we have to add up the space used in each event.
    if ( (! property_exists($this, 'DiskSpace')) or (!isset($this->{'DiskSpace'})) ) {
      $this->{'DiskSpace'} = dbFetchOne('SELECT SUM(DiskSpace) AS DiskSpace FROM Events WHERE StorageId=? AND DiskSpace IS NOT NULL', 'DiskSpace', array($this->Id()));
    }
    return $this->{'DiskSpace'};
  } // end function event_disk_space

  public function Server() {
    if ( ! property_exists($this, 'Server') ) {
      if ( property_exists($this, 'ServerId') ) {
        $this->{'Server'} = Server::find_one(array('Id'=>$this->{'ServerId'}));

        if ( !$this->{'Server'} ) {
          if ( $this->{'ServerId'} )
            Error('No Server record found for server id ' . $this->{'ServerId'});
          $this->{'Server'} = new Server();
        }
      } else {
        $this->{'Server'} = new Server();
      }
    }
    return $this->{'Server'};
  }

} // end class Storage
?>
