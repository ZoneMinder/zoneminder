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
  public $index;
  public $attr;
  public $op;
  public $val;
  public $values;
  public $cnj;
  public $obr;
  public $cbr;


  public function __construct($term = NULL, $index=0) {
    $validConjunctionTypes = getFilterQueryConjunctionTypes();

    $this->index = $index;
    $this->attr = $term['attr'];
    $this->op = $term['op'];
    $this->val = $term['val'];
    if ( isset($term['cnj']) ) {
      if ( array_key_exists($term['cnj'], $validConjunctionTypes) ) {
      $this->cnj = $term['cnj'];
      } else {
        Warning('Invalid cnj ' . $term['cnj'] . ' in ' . print_r($term, true));
      }
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
        $this->obr = $term['cbr'];
      } else {
        Warning('Invalid cbr ' . $term['cbr'] . ' in ' . print_r($term, true));
      }
    }
  } # end function __construct

  # Returns an array of values.  AS term->value can be a list, we will break it apart, remove quotes etc
  public function sql_values() {
    $values = array();
    if ( !isset($this->val) ) {
      Logger::Warning("No value in term ");
      return $values;
    }

    foreach ( preg_split('/["\'\s]*?,["\'\s]*?/', preg_replace('/^["\']+?(.+)["\']+?$/', '$1', $this->val)) as $value ) {

      switch ( $this->attr ) {

      case 'AlarmedZoneId':
        $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND ZoneId='.$value.')';
        break;
      case 'ExistsInFileSystem':
        break;
      case 'MonitorName':
      case 'Name':
      case 'Cause':
      case 'Notes':
        if ( $this->op == 'LIKE' || $this->op == 'NOT LIKE' ) {
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
        } else if ( $value == 'NULL' ) {

        } else {
          $value = dbEscape($value);
        }
        break;
      case 'StorageId':
        if ( $value != 'NULL' ) {
          $value = dbEscape($value);
        }
        break;
      case 'DateTime':
      case 'StartDateTime':
      case 'EndDateTime':
        if ( $value != 'NULL' )
          $value = '\''.strftime(STRF_FMT_DATETIME_DB, strtotime($value)).'\'';
        break;
      case 'Date':
      case 'StartDate':
      case 'EndDate':
        if ( $value == 'CURDATE()' or $value == 'NOW()' ) {
          $value = 'to_days('.$value.')';
        } else if ( $value != 'NULL' ) {
          $value = 'to_days(\''.strftime(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
        }
        break;
      case 'Time':
      case 'StartTime':
      case 'EndTime':
        if ( $value != 'NULL' )
          $value = 'extract(hour_second from \''.strftime(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
        break;
      default :
        if ( $value == 'Odd' ) {
          $value = 1;
        } else if ( $value == 'Even' ) {
          $value = 0;
        } else if ( $value != 'NULL' )
          $value = dbEscape($value);
        break;
      }
      $values[] = $value;
    } // end foreach value
    return $values;
  } # end function sql_values

  public function sql_operator() {
    if ( $this->attr == 'AlarmZoneId' ) {
      return ' EXISTS ';
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
      return ' NOT IN ';
    case 'EXISTS' :
     return ' EXISTS ';
    case 'IS' :
      # Odd will be replaced with 1
      # Even will be replaced with 0
      if ( $this->value == 'Odd' or $this->value == 'Even' )  {
        return ' % 2 = ';
      } else {
        return ' IS ';
      }
    case 'IS NOT' :
      if ( $this->value == 'Odd' or $this->value == 'Even' )  {
        return ' % 2 = ';
      }
      return ' IS NOT ';
    default:
      ZM\Warning('Invalid operator in filter: ' . print_r($this->op, true));
    } // end switch op
  } # end public function sql_operator

  /* Some terms don't have related SQL */
  public function sql() {
    if ( $this->attr == 'ExistsInFileSystem' ) {
      return '';
    }

    $sql = '';
    if ( isset($this->cnj) ) {
      $sql .= ' '.$this->cnj.' ';
    }
    if ( isset($this->obr) ) {
      $sql .= ' '.str_repeat('(', $this->obr).' ';
    }

    switch ( $this->attr ) {
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
      $sql .= 'E.StartTime';
      break;
    case 'Date':
      $sql .= 'to_days(E.StartTime)';
      break;
    case 'Time':
      $sql .= 'extract(hour_second FROM E.StartTime)';
      break;
    case 'Weekday':
      $sql .= 'weekday(E.StartTime)';
      break;
      # Starting Time
    case 'StartDateTime':
      $sql .= 'E.StartTime';
      break;
    case 'FramesEventId':
      $sql .= 'F.EventId';
      break;
    case 'StartDate':
      $sql .= 'to_days(E.StartTime)';
      break;
    case 'StartTime':
      $sql .= 'extract(hour_second FROM E.StartTime)';
      break;
    case 'StartWeekday':
      $sql .= 'weekday(E.StartTime)';
      break;
      # Ending Time
    case 'EndDateTime':
      $sql .= 'E.EndTime';
      break;
    case 'EndDate':
      $sql .= 'to_days(E.EndTime)';
      break;
    case 'EndTime':
      $sql .= 'extract(hour_second FROM E.EndTime)';
      break;
    case 'EndWeekday':
      $sql .= 'weekday(E.EndTime)';
      break;
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
      $sql .= 'E.'.$this->attr;
    }
    $sql .= $this->sql_operator();
    $values = $this->sql_values();
    if ( count($values) > 1 ) {
      $sql .= '('.join(',', $values).')';
    } else {
      $sql .= $values[0];
    }

    if ( isset($this->cbr) ) {
      $sql .= ' '.str_repeat(')', $this->cbr).' ';
    }
    return $sql;
  } # end public function sql

  public function querystring($querySep='&amp;') {
    # We don't validate the term parameters here
    $query = '';
    if ( $this->cnj ) 
      $query .= $querySep.urlencode('filter[Query][terms]['.$this->index.'][cnj]').'='.$this->cnj;
    if ( $this->obr )
      $query .= $querySep.urlencode('filter[Query][terms]['.$this->index.'][obr]').'='.$this->obr;

    $query .= $querySep.urlencode('filter[Query][terms]['.$this->index.'][attr]').'='.urlencode($this->attr);
    $query .= $querySep.urlencode('filter[Query][terms]['.$this->index.'][op]').'='.urlencode($this->op);
    $query .= $querySep.urlencode('filter[Query][terms]['.$this->index.'][val]').'='.urlencode($this->val);
    if ( $this->cbr )
      $query .= $querySep.urlencode('filter[Query][terms]['.$this->index.'][cbr]').'='.$this->cbr;
    return $query;
  } # end public function querystring

  public function hidden_fields_string() {
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
  } # end public function hidden_field_string

  public function test($event) {
    Logger::Debug("Testing PostSQLcondtion");
    if ( $this->attr == 'ExistsInFileSystem' ) {
      Logger::Debug("file exists?! " . file_exists($event->Path()) );
      if ( 
        ($this->op == 'IS' and $this->val == 'True')
        or
        ($this->op == 'IS NOT' and $this->val == 'False')
      ) {
        return file_exists($event->Path());
      } else {
        return !file_exists($event->Path());
      }
    }
    Error("testing not supported term");
    return false;
  }
  
  public function is_post_sql() {
    if ( $this->attr == 'ExistsInFileSystem' ) {
        return true;
    }
    return false;
  }

} # end class FilterTerm

?>
