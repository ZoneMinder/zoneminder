<?php
namespace ZM;
require_once('database.php');
require_once('Object.php');
require_once('Event.php');

class Snapshot extends ZM_Object {
  protected static $table = 'Snapshots';
  protected $defaults = array(
    'Id' => null,
    'CreatedBy' => null,
    'CreatedOn' => 'NOW()',
    'Name' => '',
    'Description' => '',
  );

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Event() {
    return new Event( $this->{'EventId'} );
  }

  # Returns whether the snapshot and its event links were removed.
  public function delete() {
    if ( !property_exists($this, 'Id') ) return false;
    if ( !dbQuery('DELETE FROM `Snapshots_Events` WHERE `SnapshotId`=?', array($this->{'Id'})) ) return false;
    return (bool)dbQuery('DELETE FROM `Snapshots` WHERE `Id`=?', array($this->{'Id'}));
  }

  public function EventIds( ) {
    if ( ! property_exists($this, 'EventIds') ) {
      $this->{'EventIds'} = dbFetchAll('SELECT `EventId` FROM `Snapshots_Events` WHERE `SnapshotId`=?', 'EventId', array($this->{'Id'}));
    }
    return $this->{'EventIds'};
  }
  public function Events() {
    if ( ! property_exists($this, 'Events') ) {
      # Only the events of monitors the user can view, as everywhere else events are listed.
      $this->{'Events'} = array_values(array_filter(
        Event::find(array('Id'=>$this->EventIds())),
        function($event) { return $event->canView(); }
      ));
    }
    return $this->{'Events'};
  }

} # end class Snapshot

class Snapshot_Event extends ZM_Object {
  protected static $table = 'Snapshots_Events';
  protected $defaults = array(
    'Id' => null,
    'EventId' => 0,
    'SnapshotId' => 0,
  );
  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }
} # end class Snapshot_Event
?>
