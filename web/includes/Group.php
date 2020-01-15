<?php
namespace ZM;

class Group extends ZM_Object {
  protected static $table = 'Groups';
  protected $defaults = array(
      'Id'              =>  null,
      'Name'            =>  '',
      'ParentId'        =>  null,
      );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function delete() {
    if ( property_exists($this, 'Id') ) {
      dbQuery('DELETE FROM Groups_Monitors WHERE GroupId=?', array($this->{'Id'}));
      dbQuery('UPDATE Groups SET ParentId=NULL WHERE ParentId=?', array($this->{'Id'}));
      dbQuery('DELETE FROM Groups WHERE Id=?', array($this->{'Id'}));
      if ( isset($_COOKIE['zmGroup']) ) {
        if ( $this->{'Id'} == $_COOKIE['zmGroup'] ) {
          unset($_COOKIE['zmGroup']);
          setcookie('zmGroup', '', time()-3600*24*2);
        }
      }
    }
  } # end function delete()

  public function depth( $new = null ) {
    if ( isset($new) ) {
      $this->{'depth'} = $new;
    }
    if ( !property_exists($this, 'depth') or ($this->{'depth'} === null) ) {
      $this->{'depth'} = 0;
      if ( $this->{'ParentId'} != null ) {
        $Parent = Group::find_one(array('Id'=>$this->{'ParentId'}));
        $this->{'depth'} += $Parent->depth()+1;
      }
    }
    return $this->{'depth'};
  } // end public function depth

  public function MonitorIds( ) {
    if ( ! property_exists($this, 'MonitorIds') ) {
      $this->{'MonitorIds'} = dbFetchAll('SELECT MonitorId FROM Groups_Monitors WHERE GroupId=?', 'MonitorId', array($this->{'Id'}));
    }
    return $this->{'MonitorIds'};
  }

  public static function get_group_dropdown( ) {

    $selected_group_id = 0;
    if ( isset($_REQUEST['groups']) ) {
      $selected_group_id = $group_id = $_SESSION['groups'] = $_REQUEST['groups'];
    } else if ( isset( $_SESSION['groups'] ) ) {
      $selected_group_id = $group_id = $_SESSION['groups'];
    } else if ( isset($_REQUEST['filtering']) ) {
      zm_session_start();
      unset($_SESSION['groups']);
      session_write_close();
    }

    return htmlSelect( 'GroupId[]', Group::get_dropdown_options(), isset($_SESSION['GroupId'])?$_SESSION['GroupId']:null, array(
          'data-on-change' => 'submitThisForm',
          'class'=>'chosen',
          'multiple'=>'multiple',
          'data-placeholder'=>'All',
          ) );

  } # end public static function get_group_dropdown

  public static function get_dropdown_options() {
    $Groups = array();
    foreach ( Group::find(array(), array('order'=>'lower(Name)')) as $Group ) {
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
    } # end foreach

    function get_options($Group) {
      global $children;
      $options = array($Group->Id() => str_repeat('&nbsp;&nbsp;&nbsp;', $Group->depth()) . $Group->Name());
      if ( isset($children[$Group->Id()]) ) {
        foreach ( $children[$Group->Id()] as $child ) {
          $options += get_options($child);
        }
      }
      return $options;
    } # end function get_options

    $group_options = array();
    foreach ( $Groups as $id=>$Group ) {
      if ( ! $Group->ParentId() ) {
        $group_options += get_options($Group);
      }
    }
    return $group_options;
  } # end function get_dropdown_options

  public static function get_group_sql($group_id) {
    $groupSql = '';
    if ( $group_id ) {
      if ( is_array($group_id) ) {
        $group_id_sql_part = ' IN ('.implode(',', array_map(function(){return '?';}, $group_id ) ).')';

        $MonitorIds = dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId`'.$group_id_sql_part, 'MonitorId', $group_id);

        $MonitorIds = array_merge($MonitorIds, dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId` IN (SELECT `Id` FROM `Groups` WHERE `ParentId`'.$group_id_sql_part.')', 'MonitorId', $group_id));
      } else { 
        $MonitorIds = dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId`=?', 'MonitorId', array($group_id));

        $MonitorIds = array_merge($MonitorIds, dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId` IN (SELECT `Id` FROM `Groups` WHERE `ParentId` = ?)', 'MonitorId', array($group_id)));
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
	  $sql = 'SELECT `Id`,`Name` FROM `Monitors`';
	  if ( $options ) {
		  $sql .= ' WHERE '. implode(' AND ', array(
					  ( isset($options['groupSql']) ? $options['groupSql']:'')
					  ) ).' ORDER BY `Sequence` ASC';
	  }
	  $monitors_dropdown = array(''=>'All');

	foreach ( dbFetchAll($sql) as $monitor ) {
    if ( !visibleMonitor($monitor['Id']) ) {
      continue;
    }
    $monitors_dropdown[$monitor['Id']] = $monitor['Name'];
  }

  echo htmlSelect('monitor_id', $monitors_dropdown, $monitor_id, array('data-on-change-this'=>'changeMonitor'));
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
