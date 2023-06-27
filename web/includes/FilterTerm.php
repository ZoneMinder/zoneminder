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

  public function __construct($filter = null, $term = null, $index=0) {
    $this->cnj = '';
    $this->filter = $filter;
    $validConjunctionTypes = getFilterQueryConjunctionTypes();

    $this->index = $index;
    if ($term) {
      $this->attr = isset($term['attr']) ? $term['attr'] : '';
      $this->attr = preg_replace('/[^A-Za-z0-9\.]/', '', $this->attr, -1, $count);
      if ($count) Error("Invalid characters removed from filter attr ${term['attr']}, possible hacking attempt.");
      $this->op = isset($term['op']) ? $term['op'] : '=';
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
        $this->tablename = $term['tablename'];
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
      $this->collate = isset($term['collate']) ? $term['collate'] : '';

    } else {
      Warning("No term in FilterTerm constructor");
      #Warning(print_r(debug_backtrace(), true));
    }
  } # end function __construct

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
        $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND ZoneId='.$value.' AND Score > 0 LIMIT 1)';
        break;
      case 'ExistsInFileSystem':
        $value = '';
        break;
      case 'DiskPercent':
        $value = '';
        break;
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
          $value = ZM_SERVER_ID;
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
      case 'DateTime':
      case 'StartDateTime':
      case 'EndDateTime':
        if ( $value_upper == 'CURDATE()' or $value_upper == 'NOW()' ) {

        } else if ( $value_upper != 'NULL' )
          $value = '\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\'';
        break;
      case 'Date':
      case 'StartDate':
      case 'EndDate':
        if ( $value_upper == 'CURDATE()' or $value_upper == 'NOW()' ) {
          $value = 'to_days('.$value.')';
        } else if ( $value_upper != 'NULL' ) {
          $value = 'to_days(\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
        }
        break;
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
      } else {
        return ' IS ';
      }
    case 'IS NOT' :
      if ( $this->val == 'Odd' or $this->val == 'Even' )  {
        return ' % 2 = ';
      }
      return ' IS NOT ';
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
      return ZM_SERVER_ID;
      # Unspecified start or end, so assume start, this is to support legacy filters
    case 'DateTime':
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
      Debug("Testing " . $this->attr);
      if ( $this->attr == 'DiskPercent' ) {
        # The logic on this is really ugly.  We are going to treat it as an OR
        foreach ( $this->filter->get_StorageAreas() as $storage ) {
          $string_to_eval = 'return $storage->disk_usage_percent() '.$this->op.' '.$this->val.';';
          try {
            $ret = eval($string_to_eval);
            Debug("Evalled $string_to_eval = $ret");
            if ( $ret )
              return true;
          } catch ( Throwable $t ) {
            Error('Failed evaluating '.$string_to_eval);
            return false;
          }
        } # end foreach Storage Area
      } else if ( $this->attr == 'SystemLoad' ) {
        $string_to_eval = 'return getLoad() '.$this->op.' '.$this->val.';';
        try {
          $ret = eval($string_to_eval);
          Debug("Evaled $string_to_eval = $ret");
          if ( $ret )
            return true;
        } catch ( Throwable $t ) {
          Error('Failed evaluating '.$string_to_eval);
          return false;
        }
      } else {
        Error('testing unsupported pre term ' . $this->attr);
      }
    } else {
      # Is a Post Condition 
      if ( $this->attr == 'ExistsInFileSystem' ) {
        if ( 
          ($this->op == 'IS' and $this->val == 'True')
          or
          ($this->op == 'IS NOT' and $this->val == 'False')
        ) {
          return file_exists($event->Path());
        } else {
          return !file_exists($event->Path());
        }
      } else if ( $this->attr == 'DiskPercent' ) {
        $string_to_eval = 'return $event->Storage()->disk_usage_percent() '.$this->op.' '.$this->val.';';
        try {
          $ret = eval($string_to_eval);
          Debug("Evalled $string_to_eval = $ret");
          if ( $ret )
            return true;
        } catch ( Throwable $t ) {
          Error('Failed evaluating '.$string_to_eval);
          return false;
        }
      } else if ( $this->attr == 'DiskBlocks' ) {
        $string_to_eval = 'return $event->Storage()->disk_usage_blocks() '.$this->op.' '.$this->val.';';
        try {
          $ret = eval($string_to_eval);
          Debug("Evalled $string_to_eval = $ret");
          if ( $ret )
            return true;
        } catch ( Throwable $t ) {
          Error('Failed evaluating '.$string_to_eval);
          return false;
        }
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
    case 'EndDate' :
    case 'StartDate' :
    case 'EndDateTime' :
    case 'StartDateTime' :
      if (!$this->val)
        return false;
      break;
    case 'Archived' :
    case 'Monitor' :
    case 'MonitorId' :
    case 'ServerId' :
    case 'FilterServerId' :
    case 'Group' :
    case 'Notes' :
      if ($this->val === '')
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
