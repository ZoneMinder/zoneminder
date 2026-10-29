<?php
namespace ZM;

require_once('database.php');
require_once('Object.php');
require_once('CronTab.php');
require_once('Command.php');

// A command ZoneMinder puts in the web user's crontab for cron to run.
//
// The counterpart to a scheduled MonitorAction: that one runs a device
// command ZoneMinder already knows how to build, this one runs whatever the
// operator says. Both end up as lines in the same marked block, written by
// cronRebuild().
class CronJob extends ZM_Object {
  protected static $table = 'CronJobs';

  protected $defaults = array(
    'Id'       => null,
    'Name'     => '',
    'Enabled'  => 1,
    'Schedule' => '',
    'Command'  => '',
    'ServerId' => null,
  );

  public static function find($parameters = array(), $options = array()) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one($parameters = array(), $options = array()) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function Server() {
    if (!$this->{'ServerId'}) return null;
    require_once('Server.php');
    return Server::find_one(array('Id'=>$this->{'ServerId'}));
  }

  // Whether this job belongs in the crontab of the server running this code.
  // A job with no server named belongs on every server, which is what a single
  // server install wants and gets without having to say so.
  public function belongsOnThisServer() {
    if (!$this->{'ServerId'}) return true;
    if (!defined('ZM_SERVER_ID') or !ZM_SERVER_ID) return true;
    return ($this->{'ServerId'} == ZM_SERVER_ID);
  }

  // The crontab line, or null when this job is not one to write.
  //
  // Validated again here rather than trusted from the row: the database is
  // not the boundary, the crontab is, and a row could have been written by
  // something that did not check.
  public function cronLine() {
    if (!$this->{'Enabled'}) return null;
    if (!cronScheduleIsValid($this->{'Schedule'})) return null;
    if (!cronCommandIsValid($this->{'Command'})) return null;
    // Asked again on every rebuild, so narrowing ZM_ALLOWED_COMMANDS drops
    // the jobs it no longer covers out of the crontab rather than leaving
    // them running until somebody edits each one.
    if (!commandAllowedByConfig($this->{'Command'})) {
      Warning('Scheduled task '.$this->{'Id'}.' is not in ZM_ALLOWED_COMMANDS, leaving it out of the crontab');
      return null;
    }

    $line = trim($this->{'Schedule'}).' '.trim($this->{'Command'});
    // Named so somebody reading the crontab can find the row it came from.
    $line .= ' # ZoneMinder job '.intval($this->{'Id'});
    return $line;
  }
}
?>
