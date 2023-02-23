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

  public function __construct($filter = null, $term = null, $index=0) {
    $this->filter = $filter;
    $validConjunctionTypes = getFilterQueryConjunctionTypes();

    $this->index = $index;
    $this->attr = $term['attr'];
    $this->attr = preg_replace('/[^A-Za-z0-9\.]/', '', $term['attr'], -1, $count);
    if ($count) Error("Invalid characters removed from filter attr ${term['attr']}, possible hacking attempt.");
    $this->op = $term['op'];
    $this->val = $term['val'];
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
      case 'AlarmedZoneId':
        $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND ZoneId='.$value.' AND Score > 0)';
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
        if ( strstr($this->op, 'LIKE') and ! strstr($this->val, '%' ) ) {
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
        if ( $value_upper != 'NULL' )
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
        if ( $value_upper != 'NULL' )
          $value = 'extract(hour_second from \''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
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
      $values[] = $value;
    } // end foreach value
    return $values;
  } # end function sql_values

  public function sql_operator() {
    switch ($this->attr) {
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

  /* Some terms don't have related SQL */
  public function sql() {

    $sql = '';
    if ( isset($this->cnj) ) {
      $sql .= ' '.$this->cnj;
    }
    if ( isset($this->obr) ) {
      $sql .= ' '.str_repeat('(', $this->obr);
    }
    $sql .= ' ';

    switch ( $this->attr ) {
    case 'AlarmedZoneId':
      $sql .= '/* AlarmedZoneId */ ';
      break;
    case 'ExistsInFileSystem':
    case 'DiskPercent':
      $sql .= 'TRUE /*'.$this->attr.'*/';
      break;
    case 'MonitorName':
      $sql .= 'M.Name';
      break;
    case 'ServerId':
    case 'MonitorServerId':
      $sql .= 'M.ServerId';
      break;
    case 'StorageServerId':
      $sql .= 'S.ServerId';
      break;
    case 'FilterServerId':
      $sql .= ZM_SERVER_ID;
      break;
      # Unspecified start or end, so assume start, this is to support legacy filters
    case 'DateTime':
      $sql .= 'E.StartDateTime';
      break;
    case 'Date':
      $sql .= 'to_days(E.StartDateTime)';
      break;
    case 'Time':
      $sql .= 'extract(hour_second FROM E.StartDateTime)';
      break;
    case 'Weekday':
      $sql .= 'weekday(E.StartDateTime)';
      break;
      # Starting Time
    case 'StartDateTime':
      $sql .= 'E.StartDateTime';
      break;
    case 'FrameId':
      $sql .= 'Id';
      break;
    case 'Type':
    case 'TimeStamp':
    case 'Delta':
    case 'Score':
      $sql .= $this->attr;
      break;
    case 'FramesEventId':
      $sql .= 'F.EventId';
      break;
    case 'StartDate':
      $sql .= 'to_days(E.StartDateTime)';
      break;
    case 'StartTime':
      $sql .= 'extract(hour_second FROM E.StartDateTime)';
      break;
    case 'StartWeekday':
      $sql .= 'weekday(E.StartDateTime)';
      break;
      # Ending Time
    case 'EndDateTime':
      $sql .= 'E.EndDateTime';
      break;
    case 'EndDate':
      $sql .= 'to_days(E.EndDateTime)';
      break;
    case 'EndTime':
      $sql .= 'extract(hour_second FROM E.EndDateTime)';
      break;
    case 'EndWeekday':
      $sql .= 'weekday(E.EndDateTime)';
      break;
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
    case 'Notes':
    case 'StateId':
    case 'Archived':
      $sql .= $this->tablename.'.'.$this->attr;
      break;
    default :
      $sql .= $this->tablename.'.'.$this->attr;
    }
    $sql .= $this->sql_operator();
    $values = $this->sql_values();
    if ((count($values) > 1) or ($this->op == 'IN') or ($this->op == 'NOT IN') or ($this->op == '=[]') or ($this->op == '![]')) {
      $sql .= '('.join(',', $values).')';
    } else {
      $sql .= $values[0];
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
    if ( $this->cnj ) 
      $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][cnj]').'='.$this->cnj;
    if ( $this->obr )
      $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][obr]').'='.$this->obr;

    $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][attr]').'='.urlencode($this->attr);
    $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][op]').'='.urlencode($this->op);
    $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][val]').'='.urlencode($this->val);
    if ( $this->cbr )
      $query .= $querySep.urlencode($objectname.'[Query][terms]['.$this->index.'][cbr]').'='.$this->cbr;
    return $query;
  } # end public function querystring

  public function hidden_fields() {
    $html ='';
    if ( $this->cnj )
      $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][cnj]" value="'.$this->cnj.'"/>'.PHP_EOL;

    if ( $this->obr )
      $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][obr]" value="'.$this->obr.'"/>'.PHP_EOL;

    # attr should have been already validation, so shouldn't need htmlspecialchars
    $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][attr]" value="'.htmlspecialchars($this->attr).'"/>'.PHP_EOL;
    $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][op]" value="'.htmlspecialchars($this->op).'"/>'.PHP_EOL;
    $html .= '<input type="hidden" name="filter[Query][terms]['.$this->index.'][val]" value="'.htmlspecialchars($this->val).'"/>'.PHP_EOL;
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
      'EventId',
      'ExistsInFileSystem',
      'Emailed',
      'DiskSpace',
      'DiskPercent',
      'DiskBlocks',
      'MonitorName',
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
      'Archived'
    );
    return in_array($attr, $attrs);
  }
} # end class FilterTerm

?>
