<?php

$group_cache = array();

class Group {

  public $defaults = array(
      'Id'              =>  null,
      'Name'            =>  '',
      'ParentId'        =>  null,
      );

  public function __construct( $IdOrRow=NULL ) {
    global $group_cache;

    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer($IdOrRow) or is_numeric($IdOrRow) ) {
        $row = dbFetchOne('SELECT * FROM Groups WHERE Id=?', NULL, array($IdOrRow));
        if ( ! $row ) {
          Error('Unable to load Group record for Id=' . $IdOrRow);
        }
      } elseif ( is_array($IdOrRow) ) {
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
      $group_cache[$row['Id']] = $this;
    }
  } // end function __construct

  public function __call($fn, array $args) {
    if ( count($args) ) {
      $this->{$fn} = $args[0];
    }
    if ( array_key_exists($fn, $this) ) {
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

  public static function find( $parameters = null, $options = null ) {
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
          $fields[] = $field.' IN ('.implode(',', array_map($func, $value)). ')';
          $values += $value;
        } else {
          $fields[] = $field.'=?';
          $values[] = $value;
        }
      }
      $sql .= implode(' AND ', $fields);
    } # end if parameters
    if ( $options ) {
      if ( isset($options['order']) ) {
        $sql .= ' ORDER BY ' . $options['order'];
      }
      if ( isset($options['limit']) ) {
        if ( is_integer($options['limit']) or ctype_digit($options['limit']) ) {
          $sql .= ' LIMIT ' . $options['limit'];
        } else {
          $backTrace = debug_backtrace();
          $file = $backTrace[1]['file'];
          $line = $backTrace[1]['line'];
          Error("Invalid value for limit(".$options['limit'].") passed to Group::find from $file:$line");
          return array();
        }
      }
    } # end if options

    $results = dbFetchAll($sql, NULL, $values);
    if ( $results ) {
      return array_map( function($row){ return new Group($row); }, $results );
    }
    return array();
  } # end find()

  public static function find_one($parameters = null, $options = null) {
    global $group_cache;
    if (
        ( count($parameters) == 1 ) and
        isset($parameters['Id']) and
        isset($group_cache[$parameters['Id']]) ) {
      return $group_cache[$parameters['Id']];
    }
    $results = Group::find($parameters, $options);
    if ( count($results) > 1 ) {
      Error("Group::find_one Returned more than 1");
      return $results[0];
    } else if ( count($results) ) {
      return $results[0];
    } else {
      return null;
    }
  }

  public function delete() {
    if ( array_key_exists('Id', $this) ) {
      dbQuery( 'DELETE FROM Groups_Monitors WHERE GroupId=?', array($this->{'Id'}) );
      dbQuery( 'DELETE FROM Groups WHERE Id=?', array($this->{'Id'}) );
      if ( isset($_COOKIE['zmGroup']) ) {
        if ( $this->{'Id'} == $_COOKIE['zmGroup'] ) {
          unset($_COOKIE['zmGroup']);
          setcookie('zmGroup', '', time()-3600*24*2);
        }
      }
    }
  } # end function delete()

  public function set( $data ) {
    foreach ($data as $k => $v) {
      if ( is_array($v) ) {
        $this->{$k} = $v;
      } else if ( is_string($v) ) {
        $this->{$k} = trim( $v );
      } else if ( is_integer($v) ) {
        $this->{$k} = $v;
      } else if ( is_bool($v) ) {
        $this->{$k} = $v;
      } else {
        Error("Unknown type $k => $v of var " . gettype($v));
        $this->{$k} = $v;
      }
    }
  }
  public function depth( $new = null ) {
    if ( isset($new) ) {
      $this->{'depth'} = $new;
    }
    if ( ! array_key_exists('depth', $this) or ($this->{'depth'} == null) ) {
      $this->{'depth'} = 1;
      if ( $this->{'ParentId'} != null ) {
        $Parent = Group::find_one(array('Id'=>$this->{'ParentId'}));
        $this->{'depth'} += $Parent->depth();
      }
    }
    return $this->{'depth'};
  } // end public function depth

  public function MonitorIds( ) {
    if ( ! array_key_exists('MonitorIds', $this) ) {
      $this->{'MonitorIds'} = dbFetchAll('SELECT MonitorId FROM Groups_Monitors WHERE GroupId=?', 'MonitorId', array($this->{'Id'}));
    }
    return $this->{'MonitorIds'};
  }

  public static function get_group_dropdown( ) {

    session_start();
    $selected_group_id = 0;
    if ( isset($_REQUEST['groups']) ) {
      $selected_group_id = $group_id = $_SESSION['groups'] = $_REQUEST['groups'];
    } else if ( isset( $_SESSION['groups'] ) ) {
      $selected_group_id = $group_id = $_SESSION['groups'];
    } else if ( isset($_REQUEST['filtering']) ) {
      unset($_SESSION['groups']);
    }
    session_write_close();

    return htmlSelect( 'Group[]', Group::get_dropdown_options(), isset($_SESSION['Group'])?$_SESSION['Group']:null, array(
          'onchange' => 'this.form.submit();',
          'class'=>'chosen',
          'multiple'=>'multiple',
          'data-placeholder'=>'All',
          ) );

  } # end public static function get_group_dropdown

  public static function get_dropdown_options() {
    $Groups = array();
    foreach ( Group::find( ) as $Group ) {
      $Groups[$Group->Id()] = $Group;
    }

# This  array is indexed by parent_id
    global $children;
    $children = array();

    foreach ( $Groups as $id=>$Group ) {
      if ( $Group->ParentId() != null ) {
        if ( ! isset( $children[$Group->ParentId()] ) )
          $children[$Group->ParentId()] = array();
        $children[$Group->ParentId()][] = $Group;
      }
    }

    function get_options($Group) {
      global $children;
      $options = array($Group->Id() => str_repeat('&nbsp;&nbsp;&nbsp;', $Group->depth()) . $Group->Name());
      if ( isset($children[$Group->Id()]) ) {
        foreach ( $children[$Group->Id()] as $child ) {
          $options += get_options($child);
        }
      }
      return $options;
    }
    $group_options = array();
    foreach ( $Groups as $id=>$Group ) {
      if ( ! $Group->ParentId() ) {
        $group_options += get_options($Group);
      }
    }
    return $group_options;
  }

  public static function get_group_sql($group_id) {
    $groupSql = '';
    if ( $group_id ) {
      if ( is_array($group_id) ) {
        $group_id_sql_part = ' IN ('.implode(',', array_map(function(){return '?';}, $group_id ) ).')';

        $MonitorIds = dbFetchAll('SELECT MonitorId FROM Groups_Monitors WHERE GroupId'.$group_id_sql_part, 'MonitorId', $group_id);

        $MonitorIds = array_merge($MonitorIds, dbFetchAll('SELECT MonitorId FROM Groups_Monitors WHERE GroupId IN (SELECT Id FROM Groups WHERE ParentId'.$group_id_sql_part.')', 'MonitorId', $group_id));
      } else { 
        $MonitorIds = dbFetchAll('SELECT MonitorId FROM Groups_Monitors WHERE GroupId=?', 'MonitorId', array($group_id));

        $MonitorIds = array_merge($MonitorIds, dbFetchAll('SELECT MonitorId FROM Groups_Monitors WHERE GroupId IN (SELECT Id FROM Groups WHERE ParentId = ?)', 'MonitorId', array($group_id)));
      }
      $groupSql = " find_in_set( M.Id, '".implode(',', $MonitorIds)."' )";
    }
    return $groupSql;
  } # end public static function get_group_sql( $group_id )

  public static function get_monitors_dropdown($options = null) {
  $monitor_id = 0;
  if ( isset($_REQUEST['monitor_id']) ) {
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

	foreach ( dbFetchAll($sql) as $monitor ) {
    if ( !visibleMonitor($monitor['Id']) ) {
      continue;
    }
    $monitors_dropdown[$monitor['Id']] = $monitor['Name'];
  }

  echo htmlSelect('monitor_id', $monitors_dropdown, $monitor_id, array('onchange'=>'changeMonitor(this);'));
  return $monitor_id;
}

public function Parent( ) {
  if ( $this->{'ParentId'} ) {
    return Group::find_one(array('Id'=>$this->{'ParentId'}));
  }
  return null;
}

public function Parents() {
  $Parents = array();
  $Parent = $this->Parent();
  while( $Parent ) {
    array_unshift($Parents, $Parent);
    $Parent = $Parent->Parent();
  }
  return $Parents;
}

} # end class Group
?>
