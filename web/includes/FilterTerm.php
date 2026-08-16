<?php
namespace ZM;
$validConjunctionTypes = null;

function getFilterQueryConjunctionTypes() {
  if ( !isset($validConjunctionTypes ) ) {
    $validConjunctionTypes = array(
      'and' => translate('ConjAnd'),
      'or'  => translate('ConjOr')
    );
  }
  return $validConjunctionTypes;
}

class FilterTerm {
  public $filter;
  public $index;
  public $attr;
  public $op;
  public $val;
  public $values;
  public $cnj;
  public $obr;
  public $cbr;
  public $cookie;
  public $placeholder;
  public $collate;
  public $multiple;
  public $chosen;
  public $tablename;

  public function __construct($filter = null, $term = null, $index=0) {
    $this->cnj = '';
    $this->filter = $filter;
    $validConjunctionTypes = getFilterQueryConjunctionTypes();

    $this->index = $index;
    if ($term) {
      $this->attr = isset($term['attr']) ? $term['attr'] : '';
      $this->attr = preg_replace('/[^A-Za-z0-9\.]/', '', $this->attr, -1, $count);
      if ($count) Error("Invalid characters removed from filter attr {$term['attr']}, possible hacking attempt.");
      $this->op = isset($term['op']) ? $term['op'] : '=';
      $valid_ops = array('=', '!=', '>=', '<=', '>', '<', 'LIKE', 'NOT LIKE', '=~', '!~',
        '=[]', '![]', 'IN', 'NOT IN', 'EXISTS', 'IS', 'IS NOT');
      if (!in_array($this->op, $valid_ops)) {
        Warning('Invalid operator in filter term: ' . $this->op);
        $this->op = '=';
      }
      $this->val = isset($term['val']) ? $term['val'] : '';
      if (is_array($this->val)) $this->val = implode(',', $this->val);
      if ( isset($term['cnj']) ) {
        if ( array_key_exists($term['cnj'], $validConjunctionTypes) ) {
          $this->cnj = $term['cnj'];
        } else {
          Warning('Invalid cnj ' . $term['cnj'].' in '.print_r($term, true));
        }
      }
      if ( isset($term['tablename']) ) {
        # tablename is concatenated raw into SQL by sql_attr(), so it must be
        # restricted to the known table aliases used in the filter queries.
        # Anything else is rejected to prevent SQL injection.
        $valid_tablenames = array('E', 'M', 'S', 'F', 'T', 'ET', 'Snapshots');
        if ( in_array($term['tablename'], $valid_tablenames, true) ) {
          $this->tablename = $term['tablename'];
        } else {
          Error("Invalid tablename in filter term: {$term['tablename']}, possible hacking attempt. Using 'E'.");
          $this->tablename = 'E';
        }
      } else {
        $this->tablename = 'E';
      }

      if ( isset($term['obr']) ) {
        if ( (string)(int)$term['obr'] == $term['obr'] ) {
          $this->obr = $term['obr'];
        } else {
          Warning('Invalid obr ' . $term['obr'] . ' in ' . print_r($term, true));
        }
      }
      if ( isset($term['cbr']) ) {
        if ( (string)(int)$term['cbr'] == $term['cbr'] ) {
          $this->cbr = $term['cbr'];
        } else {
          Warning('Invalid cbr ' . $term['cbr'] . ' in ' . print_r($term, true));
        }
      }
      $this->cookie = isset($term['cookie']) ? $term['cookie'] : '';
      $this->placeholder = isset($term['placeholder']) ? $term['placeholder'] : null;
      $this->collate = isset($term['collate']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $term['collate']) : '';
      $this->multiple = isset($term['multiple']) ? $term['multiple'] : '';
      $this->chosen = isset($term['chosen']) ? $term['chosen'] : '';

    } else {
      Warning("No term in FilterTerm constructor".print_r(debug_backtrace(), true));
    }
  } # end function __construct

  private function compare($left, $op, $right) {
    $right = floatval($right);
    switch ($op) {
    case '=':  return $left == $right;
    case '!=': return $left != $right;
    case '>':  return $left > $right;
    case '>=': return $left >= $right;
    case '<':  return $left < $right;
    case '<=': return $left <= $right;
    default:
      Warning("Invalid operator '$op' in compare");
      return false;
    }
  }

  # Returns an array of values.  AS term->value can be a list, we will break it apart, remove quotes etc
  public function sql_values() {
    $values = array();
    if ( !isset($this->val) ) {
      Warning('No value in term '.$this->attr);
      return $values;
    }

    $vals = is_array($this->val) ? $this->val : preg_split('/["\'\s]*?,["\'\s]*?/', preg_replace('/^["\']+?(.+)["\']+?$/', '$1', $this->val));
    foreach ( $vals as $value ) {
      $value_upper = strtoupper($value);
      switch ( $this->attr ) {
      case 'Group': 
        $group = new Group($value);
        $value = $group->MonitorIds();
        break;
      case 'AlarmedZoneId':
        $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND ZoneId='.intval($value).' AND Score > 0 LIMIT 1)';
        break;
      case 'ExistsInFileSystem':
        $value = '';
        break;
      case 'DiskPercent':
        $value = '';
        break;
      case 'Tags':
      case 'MonitorName':
      case 'Name':
      case 'Cause':
      case 'Notes':
        if ( ((!$this->op) or strstr($this->op, 'LIKE')) and ! strstr($this->val, '%' ) ) {
          $value = '%'.$value.'%';
        }
        $value = dbEscape($value);
        break;
      case 'MonitorServerId':
      case 'FilterServerId':
      case 'StorageServerId':
      case 'ServerId':
        if ( $value == 'ZM_SERVER_ID' ) {
          $value = defined('ZM_SERVER_ID') ? ZM_SERVER_ID : 0;
        } else if ( $value_upper == 'NULL' ) {

        } else {
          $value = dbEscape($value);
        }
        break;
      case 'StorageId':
        if ( $value_upper != 'NULL' ) {
          $value = dbEscape($value);
        }
        break;
      case 'CurrentDateTime':
      case 'DateTime':
      case 'StartDateTime':
      case 'EndDateTime':
        if ( $value_upper == 'CURDATE()' or $value_upper == 'NOW()' ) {

        } else if ( $value_upper != 'NULL' )
          $value = '\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\'';
        break;
      case 'CurrentDate':
      case 'Date':
      case 'StartDate':
      case 'EndDate':
        // Date/StartDate/EndDate emit raw quoted date strings here.  sql()
        // wraps them in a sargable range expression instead of the legacy
        // to_days(col) op to_days(val), which prevented index use on
        // StartDateTime / EndDateTime.  CurrentDate is a constant
        // (to_days(NOW())) on the left and keeps the legacy wrapping.
        if ( $value_upper == 'CURDATE()' or $value_upper == 'NOW()' ) {
          if ($this->attr === 'CurrentDate') {
            $value = 'to_days('.$value.')';
          }
          // For Date/StartDate/EndDate leave $value as CURDATE()/NOW() raw.
        } else if ( $value_upper != 'NULL' ) {
          if ($this->attr === 'CurrentDate') {
            $value = 'to_days(\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
          } else {
            $value = '\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\'';
          }
        }
        break;
      case 'CurrentTime':
      case 'Time':
      case 'StartTime':
      case 'EndTime':
        if ( $value_upper != 'NULL' ) {
          $value = 'extract(hour_second from \''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
        }
        break;
      default :
        if ( $value == 'Odd' ) {
          $value = 1;
        } else if ( $value == 'Even' ) {
          $value = 0;
        } else if ( $value_upper != 'NULL' )
          $value = dbEscape($value);
        break;
      }
      if (is_array($value)) {
        $values += $value;
      } else {
        $values[] = $value;
      }
    } // end foreach value
    return $values;
  } # end function sql_values

  public function sql_operator() {
    switch ($this->attr) {
    case 'Group':
      return ' IN ';
    case 'AlarmedZoneId':
      return ' EXISTS ';
    case 'ExistsInFileSystem':
    case 'DiskPercent':
      return '';
    }

    switch ( $this->op ) {
    case '=' :
    case '!=' :
    case '>=' :
    case '>' :
    case '<' :
    case '<=' :
    case 'LIKE' :
    case 'NOT LIKE':
      return ' '.$this->op.' ';
    case '=~' :
      return ' regexp ';
    case '!~' :
      return ' not regexp ';
    case '=[]' :
    case 'IN' :
      return ' IN ';
    case '![]' :
    case 'NOT IN' :
      return ' NOT IN ';
    case 'EXISTS' :
     return ' EXISTS ';
    case 'IS' :
      # Odd will be replaced with 1
      # Even will be replaced with 0
      if ( $this->val == 'Odd' or $this->val == 'Even' )  {
        return ' % 2 = ';
      } else if ( strtoupper($this->val) == 'NULL' ) {
        return ' IS ';
      }
      # SQL IS is only kept here for NULL; for any other value compare for equality
      return ' = ';
    case 'IS NOT' :
      if ( $this->val == 'Odd' or $this->val == 'Even' )  {
        # negate the modulo test so IS NOT Odd matches even (and vice versa)
        return ' % 2 != ';
      } else if ( strtoupper($this->val) == 'NULL' ) {
        return ' IS NOT ';
      }
      return ' != ';
    default:
      Warning('Invalid operator in filter: ' . print_r($this->op, true));
    } // end switch op
  } # end public function sql_operator

  public function sql_attr() {
    switch ( $this->attr ) {
    case 'AlarmedZoneId':
      return '/* AlarmedZoneId */ ';
    case 'ExistsInFileSystem':
    case 'DiskPercent':
      return 'TRUE /*'.$this->attr.'*/';
    case 'MonitorName':
      return 'M.Name';
    case 'Group':
    case 'Monitor':
      return 'E.MonitorId';
    case 'ServerId':
    case 'MonitorServerId':
      return 'M.ServerId';
    case 'StorageServerId':
      return 'S.ServerId';
    case 'FilterServerId':
      return '/* ZM_SERVER_ID:*/'.(defined('ZM_SERVER_ID') ? ZM_SERVER_ID : 0);
      # Unspecified start or end, so assume start, this is to support legacy filters
    case 'CurrentDateTime':
      return 'NOW()';
    case 'CurrentDate':
      return 'to_days(NOW())';
    case 'CurrentTime':
      return 'extract(hour_second FROM NOW())';
    case 'CurrentWeekday':
      return 'weekday(NOW()';
    case 'DateTime':
      // DateTime is an "event overlaps this instant/window" idiom, not a plain
      // StartDateTime comparison. A lower bound (>=/>) is satisfied by an event
      // still running at that time, so compare against EndDateTime (NULL end =
      // ongoing = never ends). An upper bound (<=/</=) is satisfied by an event
      // that had already started, so compare against StartDateTime. refs #4976
      if ($this->op == '>=' or $this->op == '>')
        return "COALESCE(E.EndDateTime, '9999-12-31 23:59:59')";
      return 'E.StartDateTime';
    case 'Date':
     return 'to_days(E.StartDateTime)';
    case 'Time':
     return 'extract(hour_second FROM E.StartDateTime)';
    case 'Weekday':
      return 'weekday(E.StartDateTime)';
      # Starting Time
    case 'StartDateTime':
      return 'E.StartDateTime';
    case 'FrameId':
      return 'Id';
    case 'Type':
    case 'TimeStamp':
    case 'Delta':
    case 'Score':
      return $this->attr;
    case 'FramesEventId':
      return 'F.EventId';
    case 'StartDate':
      return 'to_days(E.StartDateTime)';
    case 'StartTime':
      return 'extract(hour_second FROM E.StartDateTime)';
    case 'StartWeekday':
      return 'weekday(E.StartDateTime)';
      # Ending Time
    case 'EndDateTime':
      return 'E.EndDateTime';
    case 'EndDate':
      return 'to_days(E.EndDateTime)';
    case 'EndTime':
      return 'extract(hour_second FROM E.EndDateTime)';
    case 'EndWeekday':
      return 'weekday(E.EndDateTime)';
    case 'Notes':
    case 'Emailed':
    case 'Id':
    case 'Name':
    case 'DiskSpace':
    case 'MonitorId':
    case 'StorageId':
    case 'SecondaryStorageId':
    case 'Length':
    case 'Frames':
    case 'AlarmFrames':
    case 'TotScore':
    case 'AvgScore':
    case 'MaxScore':
    case 'Cause':
    case 'StateId':
    case 'Archived':
      return $this->tablename.'.'.$this->attr;
    case 'Tags':
      return 'T.Id';
    default :
      return $this->tablename.'.'.$this->attr;
    }
  }

  /* Some terms don't have related SQL */
  public function sql() {
    if (!$this->attr) {
      return '';
    }

    $sql = '';
    if ( isset($this->cnj) ) {
      $sql .= ' '.$this->cnj;
    }
    if ( isset($this->obr) ) {
      $sql .= ' '.str_repeat('(', $this->obr);
    }
    $sql .= ' ';

    // Date attrs: emit sargable range expression instead of
    // to_days(col) op to_days(val).
    $dateColumn = '';
    if ($this->attr === 'Date' || $this->attr === 'StartDate') {
      $dateColumn = 'E.StartDateTime';
    } else if ($this->attr === 'EndDate') {
      $dateColumn = 'E.EndDateTime';
    }
    if ($dateColumn) {
      $sql .= self::dateRangeSQL($dateColumn, $this->op, $this->sql_values());
      if ( isset($this->cbr) ) $sql .= ' '.str_repeat(')', $this->cbr);
      $sql .= PHP_EOL;
      return $sql;
    }

    $operator = $this->sql_operator();
    $values = $this->sql_values();
    if ((count($values) > 1) and !(($operator == ' IN ') or ($operator == ' NOT IN ') or ($operator == ' =[] ') or ($operator == ' ![] '))) {
      $subterms = [];
      foreach ($values as $value) {
        $subterm = $this->sql_attr();
        if ($this->collate) $subterm .= ' COLLATE '.$this->collate;
        $subterm .= $operator;
        $subterm .= $value;
        $subterms[] = $subterm;
      }
      $sql .= '('.implode(' OR ', $subterms).')';
    } elseif (($this->attr === 'Tags') && ($values[0] === "'0'")) {
      // "No Tag": = means no tags (NOT EXISTS), != means has tags (EXISTS)
      if ($this->op === '!=' || $this->op === 'IS NOT') {
        $sql .= 'EXISTS (SELECT NULL FROM Events_Tags AS ET WHERE ET.EventId = E.Id)';
      } else {
        $sql .= 'NOT EXISTS (SELECT NULL FROM Events_Tags AS ET WHERE ET.EventId = E.Id)';
      }
    } elseif (($this->attr === 'Tags') && ($values[0] === "'-1'")) {
      // "Any Tag": = means has tags (EXISTS), != means no tags (NOT EXISTS)
      if ($this->op === '!=' || $this->op === 'IS NOT') {
        $sql .= 'NOT EXISTS (SELECT NULL FROM Events_Tags AS ET WHERE ET.EventId = E.Id)';
      } else {
        $sql .= 'EXISTS (SELECT NULL FROM Events_Tags AS ET WHERE ET.EventId = E.Id)';
      }
    } else {
      $sql .= $this->sql_attr();
      if ($this->collate) $sql .= ' COLLATE '.$this->collate;
      if (($operator == ' IN ') or ($operator == ' NOT IN ') or ($operator == ' =[] ') or ($operator == ' ![] ')) {
        $sql .= $operator;
        $sql .= count($values) ? '('.join(',', $values).')' : '(SELECT NULL WHERE 1!=1)';
      } else {
        $sql .= $operator;
        $sql .= $values[0];
      }
    }
    if ( isset($this->cbr) ) {
      $sql .= ' '.str_repeat(')', $this->cbr);
    }
    $sql .= PHP_EOL;
    return $sql;
  } # end public function sql

  // Returns [$day_start_sql, $next_day_start_sql] for a date value.
  // $value is either a quoted 'YYYY-MM-DD HH:MM:SS', CURDATE(), or NOW().
  private static function dateBounds($value) {
    if ($value === 'CURDATE()' || $value === 'NOW()') {
      return array($value, "$value + INTERVAL 1 DAY");
    }
    $stripped = preg_replace("/^'(.+)'$/", '$1', $value);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $stripped, $m)) {
      Error("dateBounds: unable to parse '$value'");
      return array($value, $value);
    }
    $lo = sprintf("'%04d-%02d-%02d 00:00:00'", $m[1], $m[2], $m[3]);
    $hi_t = mktime(0, 0, 0, (int)$m[2], (int)$m[3] + 1, (int)$m[1]);
    $hi = '\''.date('Y-m-d 00:00:00', $hi_t).'\'';
    return array($lo, $hi);
  }

  // Builds a sargable WHERE-clause fragment for date-precision
  // comparisons against $column.  $values are raw SQL literals from
  // sql_values() (quoted date strings, CURDATE()/NOW(), or 'NULL').
  private static function dateRangeSQL($column, $op, $values) {
    if (count($values) === 1 && strtoupper($values[0]) === 'NULL') {
      if ($op === 'IS' || $op === '=')         return "$column IS NULL";
      if ($op === 'IS NOT' || $op === '!=')    return "$column IS NOT NULL";
    }

    if ($op === 'IN' || $op === '=[]') {
      $ors = array();
      foreach ($values as $v) {
        list($lo, $hi) = self::dateBounds($v);
        $ors[] = "($column >= $lo AND $column < $hi)";
      }
      return '('.implode(' OR ', $ors).')';
    }
    if ($op === 'NOT IN' || $op === '![]') {
      $ands = array();
      foreach ($values as $v) {
        list($lo, $hi) = self::dateBounds($v);
        $ands[] = "($column < $lo OR $column >= $hi)";
      }
      return '('.implode(' AND ', $ands).')';
    }

    list($lo, $hi) = self::dateBounds($values[0]);
    switch ($op) {
      case '=':       return "$column >= $lo AND $column < $hi";
      case '!=':      return "($column < $lo OR $column >= $hi)";
      case '>':       return "$column >= $hi";
      case '>=':      return "$column >= $lo";
      case '<':       return "$column < $lo";
      case '<=':      return "$column < $hi";
      case 'IS':      return "$column >= $lo AND $column < $hi";
      case 'IS NOT':  return "($column < $lo OR $column >= $hi)";
    }
    Warning("dateRangeSQL: unhandled op '$op', falling back to to_days");
    return "to_days($column) $op ".$values[0];
  }

  public function querystring($objectname='filter', $querySep='&amp;') {
    # We don't validate the term parameters here
    $query = '';
    if (isset($this->cnj) and $this->cnj)
      $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][cnj]').'='.$this->cnj;
    if ($this->obr)
      $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][obr]').'='.$this->obr;

    $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][attr]').'='.urlencode($this->attr);
    $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][op]').'='.urlencode($this->op);
    $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][val]').'='.urlencode($this->val);
    if ($this->cbr)
      $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][cbr]').'='.$this->cbr;
    return $query;
  } # end public function querystring

  public function hidden_fields() {
    $html ='';
    if ( isset($this->cnj) and $this->cnj )
      $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][cnj]" value="'.$this->cnj.'"/>'.PHP_EOL;

    if ( $this->obr )
      $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][obr]" value="'.$this->obr.'"/>'.PHP_EOL;

    # attr should have been already validated, so shouldn't need htmlspecialchars
    $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][attr]" value="'.htmlspecialchars($this->attr).'"/>'.PHP_EOL;
    $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][op]" value="'.htmlspecialchars($this->op).'"/>'.PHP_EOL;
    $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][val]" value="'.htmlspecialchars($this->val?$this->val:'').'"/>'.PHP_EOL;
    if ( $this->cbr )
      $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][cbr]" value="'.$this->cbr.'"/>'.PHP_EOL;

    return $html;
  } # end public function hiddens_fields

  public function test($event=null) {
    if ( !isset($event) ) {
      # Is a Pre Condition
      if ( $this->attr == 'DiskPercent' ) {
        $storage_areas = $this->filter->get_StorageAreas();
        # The logic on this when there are multiple storage areas breaks.  We will just use the first.
        foreach ( $storage_areas as $storage ) {
          Debug($storage->disk_usage_percent(). ' '.$this->op.'? '.$this->val);
          switch ($this->op) {
          case '=':
            return ($storage->disk_usage_percent() == $this->val);
          case '>':
            return ($storage->disk_usage_percent() > $this->val);
          case '<':
            return ($storage->disk_usage_percent() < $this->val);
          case '<=':
            return ($storage->disk_usage_percent() <= $this->val);
          case '>=':
            return ($storage->disk_usage_percent() >= $this->val);
          default:
            Warning('Invalid op '.$this->op .' for DiskPercent.');
          }
        } # end foreach Storage Area
      } else if ( $this->attr == 'SystemLoad' ) {
        $ret = $this->compare(getLoad(), $this->op, $this->val);
        Debug("SystemLoad compare: getLoad() {$this->op} {$this->val} = " . ($ret ? 'true' : 'false'));
        if ($ret) return true;
      } else {
        Error('testing unsupported pre term ' . $this->attr);
      }
    } else {
      # Is a Post Condition 
      if ( $this->attr == 'ExistsInFileSystem' ) {
        if ( 
          ($this->op == 'IS' and $this->val == 'true')
          or
          ($this->op == 'IS NOT' and $this->val == 'false')
        ) {
          return file_exists($event->Path());
        } else {
          return !file_exists($event->Path());
        }
      } else if ( $this->attr == 'DiskPercent' ) {
        $ret = $this->compare($event->Storage()->disk_usage_percent(), $this->op, $this->val);
        Debug("DiskPercent compare: " . ($ret ? 'true' : 'false'));
        if ($ret) return true;
      } else if ( $this->attr == 'DiskBlocks' ) {
        $ret = $this->compare($event->Storage()->disk_usage_blocks(), $this->op, $this->val);
        Debug("DiskBlocks compare: " . ($ret ? 'true' : 'false'));
        if ($ret) return true;
      } else if ( $this->attr == 'Tags' ) {
        // Debug('TODO: Complete this post_sql_condition for Tags  val: ' . $this->val . '  op: ' . $this->op . '  id: ' . $this->id);
        // Debug(print_r($this, true));
        // Debug(print_r($event, true));
        return true;
      } else {
        Error('testing unsupported post term ' . $this->attr);
      }
    }
    return false;
  }
  
  public function is_pre_sql() {
    if ( $this->attr == 'DiskPercent' )
        return true;
    if ( $this->attr == 'DiskBlocks' )
        return true;
    return false;
  }

  public function is_post_sql() {
    // Tags is filtered in SQL (see sql_attr/sql), so it is not post-sql.
    if ( $this->attr == 'ExistsInFileSystem' ) {
        return true;
    }
    return false;
  }

  public static function is_valid_attr($attr) {
    $attrs = array(
      'Score',
      'Delta',
      'TimeStamp',
      'Type',
      'FrameId',
      'Group',
      'EventId',
      'ExistsInFileSystem',
      'Emailed',
      'DiskSpace',
      'DiskPercent',
      'DiskBlocks',
      'MonitorName',
      'Monitor',
      'ServerId',
      'MonitorServerId',
      'StorageServerId',
      'FilterServerId',
      'CurrentDateTime',
      'CurrentDate',
      'CurrentTime',
      'CurrentWeekday',
      'DateTime',
      'Date',
      'Time',
      'Weekday',
      'StartDateTime',
      'FramesId',
      'FramesEventId',
      'StartDate',
      'StartTime',
      'StartWeekday',
      'EndDateTime',
      'EndDate',
      'EndTime',
      'EndWeekday',
      'Id',
      'Name',
      'MonitorId',
      'StorageId',
      'SecondaryStorageId',
      'Length',
      'Frames',
      'AlarmFrames',
      'TotScore',
      'AvgScore',
      'MaxScore',
      'Cause',
      'Notes',
      'StateId',
      'Archived',
      'Tags',
      # The following are for snapshots
      'CreatedOn', 
      'Description'
    );
    return in_array($attr, $attrs);
  }

  public function valid() {
    switch ($this->attr) {
    case 'AlarmFrames' :
      if (!(is_integer($this->val) or ctype_digit($this->val))) 
        return false;
      return true;
    case 'CurrentDate' :
    case 'CurrentTime' :
    case 'CurrentDateTime' :
    case 'CurrentWeekday' :
    case 'EndDate' :
    case 'StartDate' :
    case 'EndDateTime' :
    case 'StartDateTime' :
      if (!$this->val)
        return false;
      break;
    case 'Id' :
    case 'Archived' :
    case 'Tags' :
    case 'Monitor' :
    case 'MonitorId' :
    case 'ServerId' :
    case 'FilterServerId' :
    case 'Group' :
    case 'Notes' :
      if ($this->val === '')
        return false;
      else if ($this->val === '[]')
        return false;
      else if (is_array($this->val) and !count($this->val))
        return false;
      break;
    }
    return true;
  }
  public function to_string() {
    return 'Term: '.$this->attr .'op:' . $this->op . ' val:' . $this->val;
  }
} # end class FilterTerm

?>
