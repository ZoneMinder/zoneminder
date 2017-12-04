<?php

class Group {

public $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'ParentId'        =>  null,
);

  public function __construct( $IdOrRow=NULL ) {
    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer( $IdOrRow ) or is_numeric( $IdOrRow ) ) {
        $row = dbFetchOne( 'SELECT * FROM Groups WHERE Id=?', NULL, array( $IdOrRow ) );
        if ( ! $row ) {
          Error('Unable to load Group record for Id=' . $IdOrRow );
        }
      } elseif ( is_array( $IdOrRow ) ) {
        $row = $IdOrRow;
      } else {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        Error("Unknown argument passed to Group Constructor from $file:$line)");
        Error("Unknown argument passed to Group Constructor ($IdOrRow)");
        return;
      }
    } # end if isset($IdOrRow)

    if ( $row ) {
      foreach ($row as $k => $v) {
        $this->{$k} = $v;
      }
    }
  } // end function __construct

  public function __call( $fn, array $args ) {
    if ( count( $args )  ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists( $fn, $this ) ) {
      return $this->{$fn};
    } else if ( array_key_exists( $fn, $this->defaults ) ) {
      $this->{$fn} = $this->defaults{$fn};
      return $this->{$fn};
    } else {

      $backTrace = debug_backtrace();
      $file = $backTrace[1]['file'];
      $line = $backTrace[1]['line'];
      Warning( "Unknown function call Group->$fn from $file:$line" );
    }
  }

  public static function find_all( $parameters = null ) {
    $filters = array();
    $sql = 'SELECT * FROM Groups ';
    $values = array();

    if ( $parameters ) {
      $fields = array();
      $sql .= 'WHERE ';
      foreach ( $parameters as $field => $value ) {
        if ( $value == null ) {
          $fields[] = $field.' IS NULL';
        } else if ( is_array( $value ) ) {
          $func = function(){return '?';};
          $fields[] = $field.' IN ('.implode(',', array_map( $func, $value ) ). ')';
          $values += $value;

        } else {
          $fields[] = $field.'=?';
          $values[] = $value;
        }
      }
      $sql .= implode(' AND ', $fields );
    }
    $sql .= ' ORDER BY Name';
    $result = dbQuery($sql, $values);
    $results = $result->fetchALL(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, 'Group');
    foreach ( $results as $row => $obj ) {
      $filters[] = $obj;
    }
    return $filters;
  }

  public function delete() {
    if ( array_key_exists( 'Id', $this ) ) {
      dbQuery( 'DELETE FROM Groups WHERE Id=?', array($this->{'Id'}) );
      dbQuery( 'DELETE FROM Groups_Monitors WHERE GroupId=?', array($this->{'Id'}) );
      if ( isset($_COOKIE['zmGroup']) ) {
        if ( $this->{'Id'} == $_COOKIE['zmGroup'] ) {
          unset( $_COOKIE['zmGroup'] );
          setcookie( 'zmGroup', '', time()-3600*24*2 );
        }
      }
    }
  } # end function delete()

  public function set( $data ) {
    foreach ($data as $k => $v) {
      if ( is_array( $v ) ) {
        $this->{$k} = $v;
      } else if ( is_string( $v ) ) {
        $this->{$k} = trim( $v );
      } else if ( is_integer( $v ) ) {
        $this->{$k} = $v;
      } else if ( is_bool( $v ) ) {
        $this->{$k} = $v;
      } else {
        Error( "Unknown type $k => $v of var " . gettype( $v ) );
        $this->{$k} = $v;
      }
    }
  }
  public function depth( $new = null ) {
    if ( isset($new) ) {
      $this->{'depth'} = $new;
    }
    if ( ! array_key_exists( 'depth', $this ) or ( $this->{'depth'} == null ) ) {
      $this->{'depth'} = 1;
      if ( $this->{'ParentId'} != null ) {
        $Parent = new Group( $this->{'ParentId'} );
        $this->{'depth'} += $Parent->depth();
      }
    }
    return $this->{'depth'};
  } // end public function depth

  public function MonitorIds( ) {
    if ( ! array_key_exists( 'MonitorIds', $this ) ) {
      $this->{'MonitorIds'} = dbFetchAll( 'SELECT MonitorId FROM Groups_Monitors WHERE GroupId=?', 'MonitorId', array($this->{'Id'}) );
    }
    return $this->{'MonitorIds'};
  }

  public static function get_group_dropdowns() {
    # This will end up with the group_id of the deepest selection
    $group_id = 0;
    $depth = 0;
    $groups = array();
    $parent_group_ids = null;
    while(1) {
      $Groups = Group::find_all( array('ParentId'=>$parent_group_ids) );
      if ( ! count( $Groups ) )
        break;

      $parent_group_ids = array();
      $selected_group_id = 0;
      if ( isset($_REQUEST['group'.$depth]) and $_REQUEST['group'.$depth] > 0 ) {
        $selected_group_id = $group_id = $_REQUEST['group'.$depth];
      } else if ( isset($_COOKIE['zmGroup'.$depth] ) and $_COOKIE['zmGroup'.$depth] > 0 ) {
        $selected_group_id = $group_id = $_COOKIE['zmGroup'.$depth];
      }

      foreach ( $Groups as $Group ) {
        if ( ! isset( $groups[$depth] ) ) {
          $groups[$depth] = array(0=>'All');
        }
        $groups[$depth][$Group->Id()] = $Group->Name();
        if ( $selected_group_id and ( $selected_group_id == $Group->Id() ) )
          $parent_group_ids[] = $Group->Id();
      }

      echo htmlSelect( 'group'.$depth, $groups[$depth], $selected_group_id, "changeGroup(this,$depth);" );
      if ( ! count($parent_group_ids) ) break;
      $depth += 1;
    }

    return $group_id;
  } # end public static function get_group_dropdowns()


  public static function get_group_sql( $group_id ) {
    $groupSql = '';
    if ( $group_id ) {
         $MonitorIds = dbFetchAll( 'SELECT MonitorId FROM Groups_Monitors WHERE GroupId=?', 'MonitorId', array($group_id) );

         $MonitorIds = array_merge( $MonitorIds, dbFetchAll( 'SELECT MonitorId FROM Groups_Monitors WHERE GroupId IN (SELECT Id FROM Groups WHERE ParentId = ?)', 'MonitorId', array($group_id) ) );
      $groupSql = " find_in_set( Id, '".implode( ',', $MonitorIds )."' )";
    }
    return $groupSql;
  } # end public static function get_group_sql( $group_id )

  public static function get_monitors_dropdown( $options = null ) {
  $monitor_id = 0;
  if ( isset( $_REQUEST['monitor_id'] ) ) {
    $monitor_id = $_REQUEST['monitor_id'];
  } else if ( isset($_COOKIE['zmMonitorId']) ) {
    $monitor_id = $_COOKIE['zmMonitorId'];
  }
	  $sql = 'SELECT * FROM Monitors';
	  if ( $options ) {
		  $sql .= ' WHERE '. implode(' AND ', array(
					  ( isset($options['groupSql']) ? $options['groupSql']:'')
					  ) ).' ORDER BY Sequence ASC';
	  }
	  $monitors_dropdown = array(''=>'All');

	foreach ( dbFetchAll( $sql ) as $monitor ) {
    if ( !visibleMonitor( $monitor['Id'] ) ) {
      continue;
    }
    $monitors_dropdown[$monitor['Id']] = $monitor['Name'];
  }

  echo htmlSelect( 'monitor_id', $monitors_dropdown, $monitor_id, array('onchange'=>'changeMonitor(this);') );
  return $monitor_id;
}

} # end class Group


?>
