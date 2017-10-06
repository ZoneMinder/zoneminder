<?php

class Group {

public $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'ParentId'        =>  null,
    'MonitorIds'      =>  '',
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
    dbQuery( 'DELETE FROM Groups WHERE Id = ?', array($this->{'Id'}) );
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
      if ( $group = dbFetchOne( 'SELECT MonitorIds FROM Groups WHERE Id=?', NULL, array($group_id) ) ) {
        $groupIds = array();
        if ( $group['MonitorIds'] )
          $groupIds = explode( ',', $group['MonitorIds'] );

        foreach ( dbFetchAll( 'SELECT MonitorIds FROM Groups WHERE ParentId = ?', NULL, array($group_id) ) as $group )
          if ( $group['MonitorIds'] )
            $groupIds = array_merge( $groupIds,  explode( ',', $group['MonitorIds'] ) );
      }
      $groupSql = " find_in_set( Id, '".implode( ',', $groupIds )."' )";
    }
    return $groupSql;
  } # end public static function get_group_sql( $group_id )
} # end class Group
?>
