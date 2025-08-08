<?php
namespace ZM;
require_once('Object.php');
require_once('FilterTerm.php');
require_once('Monitor.php');

class Filter extends ZM_Object {
  protected static $table = 'Filters';
  protected static $attrTypes = null;
  protected static $opTypes = null;
  protected static $tags_opTypes = null;
  protected static $is_isnot_opTypes = null;
  protected static $archiveTypes = null;
  protected static $booleanValues = null;

  protected $Query;
  protected $Terms;
  protected $Query_json;


  protected $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'UserId'          =>  0,
    'ExecuteInterval' =>  60,
    'AutoExecute'     =>  0,
    'AutoExecuteCmd'  =>  '',
    'AutoEmail'       =>  0,
		'EmailTo'					=>	'',
		'EmailSubject'		=>	'',
		'EmailBody'				=>	'',
		'EmailFormat'			=>	'Individual',
    'EmailServer'     =>  '',
    'AutoDelete'      =>  0,
    'AutoArchive'     =>  0,
    'AutoUnarchive'   =>  0,
    'AutoVideo'       =>  0,
    'AutoUpload'      =>  0,
    'AutoMessage'     =>  0,
    'AutoMove'        =>  0,
    'AutoMoveTo'      =>  0,
    'AutoCopy'        =>  0,
    'AutoCopyTo'      =>  0,
    'UpdateDiskSpace' =>  0,
    'Background'      =>  0,
    'Concurrent'      =>  0,
    'Query_json'      =>  '',
    'LockRows'        =>  0,
  );

  protected $_querystring;
  protected $_sql;
  protected $_hidden_fields;
  public $_pre_sql_conditions;
  public $_post_sql_conditions;
  protected $_Terms;
  public $availableTags = array();

  public function sql() {
    // Debug('$_Terms: '. $_Terms);
    // Debug('$_sql: ' . $_sql);
    // Debug('$this->_sql: ' . $this->_sql);
    if (!isset($this->_sql)) {
      $this->_sql = '';
      foreach ( $this->FilterTerms() as $term ) {
        // Error($term->valid());
        if ($term->valid()) {
          // Debug('$this->_sql: ' . $this->_sql);
          // Error($this-cnj);
          if (!$this->_sql) {
            if ($term->cnj) unset($term->cnj);
          } else {
            if (!$term->cnj) $term->cnj = 'and';
          }
          $this->_sql .= $term->sql();
        } else {
          // Debug('Term is not valid '.$term->to_string());
        }
        // Debug('$term->_sql: ' . $term->_sql);
      } # end foreach term
    }
    // Debug('$this->_sql: ' . $this->_sql);
    return $this->_sql;
  }

  public function querystring($objectname='filter', $separator='&amp;') {
    if ( (! isset($this->_querystring)) or ( $separator != '&amp;' ) or ($objectname != 'filter') ) {
      $this->_querystring = '';
      foreach ( $this->FilterTerms() as $term ) {
        $this->_querystring .= $term->querystring($objectname, $separator);
      } # end foreach term
      $this->_querystring .= $separator.urlencode($objectname.'[Query][sort_asc]').'='.$this->sort_asc();
      $this->_querystring .= $separator.urlencode($objectname.'[Query][sort_field]').'='.$this->sort_field();
      if ($this->skip_locked())
        $this->_querystring .= $separator.urlencode($objectname.'[Query][skip_locked]').'='.$this->skip_locked();
      if ($this->limit())
        $this->_querystring .= $separator.urlencode($objectname.'[Query][limit]').'='.$this->limit();
      if ( $this->Id() ) {
        $this->_querystring .= $separator.$objectname.urlencode('[Id]').'='.$this->Id();
      }
    }
    return $this->_querystring;
  }

  public function hidden_fields() {
    if ( ! isset($this->_hidden_fields) ) {
      $this->_hidden_fields = '';
      foreach ( $this->FilterTerms() as $term ) {
        $this->_hidden_fields .= $term->hidden_fields();
      } # end foreach term
    }
    return $this->_hidden_fields;
  }

  public function pre_sql_conditions() {
    if ( ! isset($this->_pre_sql_conditions) ) {
      $this->_pre_sql_conditions = array();
      foreach ( $this->FilterTerms() as $term ) {
        if ( $term->is_pre_sql() )
          $this->_pre_sql_conditions[] = $term;
      } # end foreach term
    }
    return $this->_pre_sql_conditions;
  }

  public function post_sql_conditions() {

    if ( ! isset($this->_post_sql_conditions) ) {
      $this->_post_sql_conditions = array();
      foreach ( $this->FilterTerms() as $term ) {
        if ( $term->is_post_sql() )
          $this->_post_sql_conditions[] = $term;
      } # end foreach term
    }
    return $this->_post_sql_conditions;
  }

  public function FilterTerms() {
    if (!isset($this->Terms)) {
      $this->Terms = array();
      $_terms = $this->terms();
      if ($_terms) {
        for ($i=0; $i < count($_terms); $i++) {
          if (isset($_terms[$i])) {
            $term = new FilterTerm($this, $_terms[$i], $i);
            $this->Terms[] = $term;
          }
        } # end foreach term
      }
    }
    return $this->Terms;
  }

  public static function parse($new_filter, $querySep='&amp;') {
    $filter = new Filter();
    $filter->Query($new_filter['Query']);
    return $filter;
  }

  # If no storage areas are specified in the terms, then return all
  public function get_StorageAreas() {
    $storage_ids = array();
    foreach ( $this->Terms as $term ) {
      if ( $term->attr == 'StorageId' ) {
        # TODO handle other operators like !=
        $storage_ids[] = $term->val;
      }
    }
    if ( count($storage_ids) ) {
      return Storage::find(array('Id'=>$storage_ids));
    } else {
      return Storage::find();
    }
  } # end function get_StorageAreas

  public function Query_json() {
    if ( func_num_args( ) ) {
      $this->{'Query_json'} = func_get_arg(0);
      $this->{'Query'} = empty($this->{'Query_json'}) ? [] : jsonDecode($this->{'Query_json'});
    }
    return $this->{'Query_json'};
  }

  public function Query() {
    if ( func_num_args( ) ) {
      $this->{'Query'} = func_get_arg(0);
      $this->{'Query_json'} = empty($this->{'Query'}) ? '{}' : jsonEncode($this->{'Query'});
      # We have altered the query so need to reset all the calculated results.
      unset($this->_querystring);
      unset($this->_sql);
      unset($this->_hidden_fields);
      unset($this->_pre_sql_conditions);
      unset($this->_post_sql_conditions);
      unset($this->_Terms);
    }
    if ( !property_exists($this, 'Query') ) {
      if ( property_exists($this, 'Query_json') and $this->{'Query_json'} ) {
        $this->{'Query'} = jsonDecode($this->{'Query_json'});
      } else {
        $this->{'Query'} = array();
      }
    } else {
      if ( !is_array($this->{'Query'}) ) {
        # Handle existence of both Query_json and Query in the row
        $this->{'Query'} = empty($this->{'Query_json'}) ? [] : jsonDecode($this->{'Query_json'});
      }
    }

    # Disable this. We will do this on SQL generation
    #if ($this->{'Query'} and isset($this->{'Query'}['terms']) and count($this->{'Query'}['terms'])) {
      ## Unset cnj on first term, so that there's no leading AND
      #unset($this->{'Query'}['terms'][0]['cnj']);
    #}
    return $this->{'Query'};
  }

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(self::class, $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(self::class, $parameters, $options);
  }

  public function terms() {
    if ( func_num_args() ) {
      $Query = $this->Query();
      $Query['terms'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['terms'] ) ) {
      return $this->Query()['terms'];
    }
    return array();
  }

  // The following three fields are actually stored in the Query
  public function sort_field( ) {
    if (func_num_args()) {
      $Query = $this->Query();
      $Query['sort_field'] = func_get_arg(0);
      $this->Query($Query);
    }
    if (isset($this->Query()['sort_field'])) {
      return $this->{'Query'}['sort_field'];
    }
    return ZM_WEB_EVENT_SORT_FIELD;
    #return $this->defaults{'sort_field'};
  }

  public function sort_asc( ) {
    if (func_num_args()) {
      $Query = $this->Query();
      $Query['sort_asc'] = func_get_arg(0);
      $this->Query($Query);
    }
    if (isset($this->Query()['sort_asc'])) {
      return $this->{'Query'}['sort_asc'];
    }
    return ZM_WEB_EVENT_SORT_ORDER == 'asc' ? 1 : 0;
    #return $this->defaults{'sort_asc'};
  }

  public function skip_locked() {
    if (func_num_args()) {
      $Query = $this->Query();
      $Query['skip_locked'] = func_get_arg(0);
      $this->Query($Query);
    }
    if (isset($this->Query()['skip_locked']))
      return $this->{'Query'}['skip_locked'];
    return false;
  }

  public function limit( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['limit'] = func_get_arg(0);
      $this->Query($Query);
    }
    // Error($this->Query()['limit']);
    if ( isset( $this->Query()['limit'] ) )
      return $this->{'Query'}['limit'];
    return 0;
    #return $this->defaults{'limit'};
  }

  public function control($command, $server_id=null) {
    $Servers = $server_id ? [Server::find_one(array('Id'=>$server_id))] : Server::find(array('Status'=>'Running'));
    if ( !count($Servers) ) {
      if ( !$server_id ) {
        # This will be the non-multi-server case
        $Servers = array(new Server());
      } else {
        Warning("Server not found for id $server_id");
      }
    }
    foreach ( $Servers as $Server ) {

      if ( (!defined('ZM_SERVER_ID')) or (!$Server->Id()) or (ZM_SERVER_ID==$Server->Id()) ) {
        # Local
        Debug("Controlling filter locally $command for server ".$Server->Id());
        daemonControl($command, 'zmfilter.pl', '--filter_id='.$this->{'Id'}.' --daemon');
      } else {
        # Remote case

        $url = $Server->UrlToIndex() . '?view=filter';
        if ( ZM_OPT_USE_AUTH ) {
          if ( ZM_AUTH_RELAY == 'hashed' ) {
            $url .= '&auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
          } else if ( ZM_AUTH_RELAY == 'plain' ) {
            $url .= '&user='.$_SESSION['username'];
            $url .= '&pass='.$_SESSION['password'];
          } else if ( ZM_AUTH_RELAY == 'none' ) {
            $url .= '&user='.$_SESSION['username'];
          }
        }
        $data = array();
        if ( defined('ZM_ENABLE_CSRF_MAGIC') ) {
          require_once('includes/csrf/csrf-magic.php');
          $data['__csrf_magic'] = csrf_get_tokens();
        }
        $data['action']='control';
        $data['object'] = 'filter';
        $data['command'] = $command;
        $data['Id'] = $this->Id();
        $data['ServerId'] = $Server->Id();

        // use key 'http' even if you send the request to https://...
        $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
          ),
          'ssl'=>array(
            'verify_peer'=>false,
            'verify_peer_name'=>false,
          )
        );
        $context  = stream_context_create($options);
        try {
          $result = file_get_contents($url, false, $context);
          if ( $result === FALSE ) { /* Handle error */
            Error("Error restarting zmfilter.pl using $url");
          }
        } catch ( Exception $e ) {
          Error("Except $e thrown trying to restart zmfilter");
        }
      } # end if local or remote
    } # end foreach erver
  } # end function control

  public function execute() {
    $command = ZM_PATH_BIN.'/zmfilter.pl --filter_id='.escapeshellarg($this->Id());
    $result = exec($command, $output, $status);
    Debug("$command status:$status output:".implode("\n", $output));
    return $status;
  }

  public function test_pre_sql_conditions() {
    if (count($this->pre_sql_conditions())) {
      foreach ($this->pre_sql_conditions() as $term) {
        if (!$term->test()) return false;
      }
    } # end if pre_sql_conditions
    return true;
  }

  public function test_post_sql_conditions($event) {
    if (count($this->post_sql_conditions())) {
      foreach ($this->post_sql_conditions() as $term) {
        if (!$term->test($event)) return false;
      }
    } # end if pre_sql_conditions
    return true;
  }

  function tree() {
    $terms = $this->terms();

    if ( count($terms) <= 0 ) {
      return false;
    }

    $StorageArea = NULL;

    $postfixExpr = array();
    $postfixStack = array();

    $priorities = array(
      '<' => 1,
      '<=' => 1,
      '>' => 1,
      '>=' => 1,
      '=' => 2,
      '!=' => 2,
      '=~' => 2,
      '!~' => 2,
      '=[]' => 2,
      'IN' => 2,
      '![]' => 2,
      'and' => 3,
      'or' => 4,
      'IS' => 2,
      'IS NOT' => 2,
    );

    for ( $i = 0; $i < count($terms); $i++ ) {
      $term = $terms[$i];
      if (!$term) {
        continue;
      } else if (!(new FilterTerm($this, $term))->valid()) {
        Debug("Term " .$term['attr'] . ' is not valid');
        continue;
      }
      if ( $i>0 and !empty($term['cnj']) ) {
        while ( true ) {
          if ( !count($postfixStack) ) {
            $postfixStack[] = array('type'=>'cnj', 'value'=>$term['cnj'], 'sqlValue'=>$term['cnj']);
            break;
          } else if ( $postfixStack[count($postfixStack)-1]['type'] == 'obr' ) {
            $postfixStack[] = array('type'=>'cnj', 'value'=>$term['cnj'], 'sqlValue'=>$term['cnj']);
            break;
          } else if ( $priorities[$term['cnj']] < $priorities[$postfixStack[count($postfixStack)-1]['value']] ) {
            $postfixStack[] = array('type'=>'cnj', 'value'=>$term['cnj'], 'sqlValue'=>$term['cnj']);
            break;
          } else {
            $postfixExpr[] = array_pop($postfixStack);
          }
        }
      } # end if ! empty cnj

      if ( !empty($term['obr']) ) {
        for ( $j = 0; $j < $term['obr']; $j++ ) {
          $postfixStack[] = array('type'=>'obr', 'value'=>$term['obr']);
        }
      }
      if ( !empty($term['attr']) ) {
        // Error($term['attr']);
        $dtAttr = false;
        switch ( $term['attr']) {
        case 'Tags':
          $sqlValue = 'T.Name';
          break;
        case 'Group':
          $sqlValue = 'M.Id';
        case 'Monitor':
          $sqlValue = 'M.Id';
          break;
        case 'MonitorName':
          $sqlValue = 'M.'.preg_replace( '/^Monitor/', '', $term['attr']);
          break;
        case 'ServerId':
          $sqlValue .= 'M.ServerId';
          break;
        case 'StorageServerId':
          $sqlValue .= 'S.ServerId';
          break;
        case 'FilterServerId':
          $sqlValue .= ZM_SERVER_ID;
          break;
        case 'DateTime':
        case 'StartDateTime':
          $sqlValue = 'E.StartDateTime';
          $dtAttr = true;
          break;
        case 'Date':
        case 'StartDate':
          $sqlValue = 'to_days(E.StartDateTime)';
          $dtAttr = true;
          break;
        case 'Time':
        case 'StartTime':
          $sqlValue = 'extract(hour_second from E.StartDateTime)';
          break;
        case 'Weekday':
        case 'StartWeekday':
          $sqlValue = 'weekday(E.StartDateTime)';
          break;
        case 'EndDateTime':
          $sqlValue = 'E.EndDateTime';
          $dtAttr = true;
          break;
        case 'EndDate':
          $sqlValue = 'to_days(E.EndDateTime)';
          $dtAttr = true;
          break;
        case 'EndTime':
          $sqlValue = 'extract(hour_second from E.EndDateTime)';
          break;
        case 'EndWeekday':
          $sqlValue = 'weekday(E.EndDateTime)';
          break;
        case 'Id':
        case 'Name':
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
          $sqlValue = 'E.'.$term['attr'];
          break;
        case 'DiskPercent':
          // Need to specify a storage area, so need to look through other terms looking for a storage area, else we default to ZM_EVENTS_PATH
          if ( ! $StorageArea ) {
            for ( $j = 0; $j < count($terms); $j++ ) {
              if ( isset($terms[$j]['attr']) and $terms[$j]['attr'] == 'StorageId' and isset($terms[$j]['val']) ) {
                $StorageArea = new Storage($terms[$j]['val']);
                break;
              }
            } // end foreach remaining term
            if ( ! $StorageArea ) $StorageArea = new Storage();
          } // end no StorageArea found yet
          $sqlValue = getDiskPercent($StorageArea);
          break;
        case 'DiskBlocks':
          // Need to specify a storage area, so need to look through other terms looking for a storage area, else we default to ZM_EVENTS_PATH
          if ( ! $StorageArea ) {
            for ( $j = 0; $j < count($terms); $j++ ) {
              if ( isset($terms[$j]['attr']) and $terms[$j]['attr'] == 'StorageId' and isset($terms[$j]['val']) ) {
                $StorageArea = new Storage($terms[$j]['val']);
                break;
              }
            } // end foreach remaining term
            if ( ! $StorageArea ) $StorageArea = new Storage();
          } // end no StorageArea found yet
          $sqlValue = getDiskBlocks($StorageArea);
          break;
        default :
          $sqlValue = $term['attr'];
          break;
        }
        if ( $dtAttr ) {
          $postfixExpr[] = array('type'=>'attr', 'value'=>$term['attr'], 'sqlValue'=>$sqlValue, 'dtAttr'=>true);
        } else {
          $postfixExpr[] = array('type'=>'attr', 'value'=>$term['attr'], 'sqlValue'=>$sqlValue);
        }
      } # end if attr

      $sqlValue = '';
      if ( isset($term['op']) ) {
        if ( empty($term['op']) ) {
          $term['op'] = '=';
        }
        switch ( $term['op']) {
        case '=' :
        case '!=' :
        case '>=' :
        case '>' :
        case '<' :
        case '<=' :
        case 'LIKE' :
        case 'NOT LIKE':
          $sqlValue = $term['op'];
          break;
        case '=~' :
          $sqlValue = 'regexp';
          break;
        case '!~' :
          $sqlValue = 'not regexp';
          break;
        case '=[]' :
        case 'IN' :
          $sqlValue = 'in (';
          break;
        case '![]' :
          $sqlValue = 'not in (';
          break;
        case 'IS' :
        case 'IS NOT' :
          if ( $term['val'] == 'Odd' )  {
            $sqlValue = ' % 2 = 1';
          } else if ( $term['val'] == 'Even' )  {
            $sqlValue = ' % 2 = 0';
          } else {
            $sqlValue = ' '.$term['op'];
          }
          break;
        default :
          Error('Unknown operator in filter '.$term['op']);
        }

        while ( true ) {
          if ( !count($postfixStack) ) {
            $postfixStack[] = array('type'=>'op', 'value'=>$term['op'], 'sqlValue'=>$sqlValue);
            break;
          } else if ( $postfixStack[count($postfixStack)-1]['type'] == 'obr' ) {
            $postfixStack[] = array('type'=>'op', 'value'=>$term['op'], 'sqlValue'=>$sqlValue);
            break;
          } else if ( $priorities[$term['op']] < $priorities[$postfixStack[count($postfixStack)-1]['value']] ) {
            $postfixStack[] = array('type'=>'op', 'value'=>$term['op'], 'sqlValue'=>$sqlValue );
            break;
          } else {
            $postfixExpr[] = array_pop($postfixStack);
          }
        } // end while
      } // end if operator

      if ( isset($term['val']) ) {
        $valueList = array();
        foreach ( preg_split('/["\'\s]*?,["\'\s]*?/', preg_replace('/^["\']+?(.+)["\']+?$/', '$1', $term['val'])) as $value ) {
          $value_upper = strtoupper($value);
          switch ( $term['attr'] ) {
          case 'Group':
            $value = Group::get_group_sql($value);
            break;
          case 'Tags':
            // Error($term['attr']);
          case 'MonitorName':
          case 'Name':
          case 'Cause':
          case 'Notes':
            if ( $term['op'] == 'LIKE' || $term['op'] == 'NOT LIKE' ) {
              $value = '%'.$value.'%';
            }
            $value = dbEscape($value);
            break;
          case 'MonitorServerId':
          case 'FilterServerId':
          case 'StorageServerId':
          case 'ServerId':
            if ( $value_upper == 'ZM_SERVER_ID' ) {
              $value = ZM_SERVER_ID;
            } else if ( $value_upper == 'NULL' ) {

            } else {
              $value = dbEscape($value);
            }
            break;
          case 'StorageId':
            $StorageArea = new Storage($value);
            if ( $value != 'NULL' )
              $value = dbEscape($value);
            break;
          case 'DateTime':
          case 'EndDateTime':
          case 'StartDateTime':
            if ($value) {
              if ( $value_upper != 'NULL' )
                $value = "'".date(STRF_FMT_DATETIME_DB, strtotime($value))."'";
            }
            break;
          case 'Date':
          case 'EndDate':
          case 'StartDate':
            if ($value) {
              $value = 'to_days(\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
            }
            break;
          case 'Time':
          case 'EndTime':
          case 'StartTime':
            if ($value) {
              $value = 'extract(hour_second from \''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
            }
            break;
          default :
            if ( $value_upper != 'NULL' )
              $value = dbEscape($value);
          } // end switch attribute
          $valueList[] = $value;
        } // end foreach value
        $postfixExpr[] = array('type'=>'val', 'value'=>$term['val'], 'sqlValue'=>join(',', $valueList));
      } // end if has val

      if ( !empty($term['cbr']) ) {
        for ( $j = 0; $j < $term['cbr']; $j++ ) {
          while ( count($postfixStack) ) {
            $element = array_pop($postfixStack);
            if ( $element['type'] == 'obr' ) {
              $postfixExpr[count($postfixExpr)-1]['bracket'] = true;
              break;
            }
            $postfixExpr[] = $element;
          }
        }
      } #end if cbr
    } # end foreach term

    while ( count($postfixStack) ) {
      $postfixExpr[] = array_pop($postfixStack);
    }

    $exprStack = array();
    foreach ( $postfixExpr as $element ) {
      if ( $element['type'] == 'attr' || $element['type'] == 'val' ) {
        $node = array('data'=>$element, 'count'=>0);
        $exprStack[] = $node;
      } elseif ( $element['type'] == 'op' || $element['type'] == 'cnj' ) {
        $right = array_pop($exprStack);
        $left = array_pop($exprStack);
        $node = array('data'=>$element, 'count'=>2+($left?$left['count']:0)+($right?$right['count']:0), 'right'=>$right, 'left'=>$left);
        $exprStack[] = $node;
      } else {
        Fatal('Unexpected element type \''.$element['type'].'\', value \''.$element['value'].'\'');
      }
    }
    if ( count($exprStack) != 1 ) {
      Error('Expression stack has '.count($exprStack).' elements');
    }
    return array_pop($exprStack);
  } # end function tree

  function addTerm($term=false, $position=null) {
    if ( !FilterTerm::is_valid_attr($term['attr']) ) {
      Error('Unsupported filter attribute ' . $term['attr']);
      //return $this;
    }
    #if (!(new FilterTerm($this, $term))->valid()) {
      #Warning("Invalid term for ".$term['attr']. ' not adding to filter');
      #return $this;
    #}

    $terms = $this->terms();

    if ( (!isset($position)) or ($position > count($terms)) )
      $position = count($terms);
    else if ( $position < 0 )
      $position = 0;

    array_splice($terms, $position, 0, array($term ? $term : array()));
    $this->terms($terms);

    return $this;
  } # end function addTerm

  function addTerms($terms, $options=null) {
    foreach ( $terms as $term ) {
      $this->addTerm($term);
    }
    return $this;
  }

  function Events() {
    $events = array();
    if (!$this->test_pre_sql_conditions()) {
      Debug('Pre conditions failed, not doing sql');
      return $events;
    }

    $where = $this->sql() ? ' WHERE ('.$this->sql().')' : '';
    // $where = ' WHERE ( T.Name = "Bird" )';
    $sort = $this->sort_field() ? $this->sort_field() .' '.($this->sort_asc() ? 'ASC' : 'DESC') : '';

    $col_str = '
    E.*, 
    M.Name 
     AS Monitor';

    $sql = '
    SELECT 
      ' .$col_str. ' 
    FROM `Events` 
      AS E 
    INNER JOIN Monitors 
      AS M 
      ON E.MonitorId = M.Id
    LEFT JOIN Events_Tags 
      AS ET 
      ON E.Id = ET.EventId 
    LEFT JOIN Tags 
      AS T 
      ON T.Id = ET.TagId 
    '.$where.($sort?' ORDER BY '.$sort:'');
    
    if ($this->limit() and !count($this->pre_sql_conditions()) and !count($this->post_sql_conditions())) {
      $sql .= ' LIMIT '.$this->limit();
    }

    Debug('Calling the following sql query: ' .$sql);
    $query = dbQuery($sql);
    if (!$query) return $events;

    while ($row = dbFetchNext($query)) {
      $event = new Event($row);
      $event->remove_from_cache();
      if (!$this->test_post_sql_conditions($event)) {
        continue;
      }
      $events[] = $event;
      if ($this->limit() and (count($events) > $this->limit())) {
        break;
      }
    } # end foreach row
    return $events;
  } # end Events()

  public static function attrTypes() {
    if (!self::$attrTypes) {
      self::$attrTypes = array(
        'AlarmFrames' => translate('AttrAlarmFrames'),
        'AlarmedZoneId' =>  translate('AttrAlarmedZone'),
        'Archived'    => translate('AttrArchiveStatus'),
        'AvgScore'    => translate('AttrAvgScore'),
        'Cause'       => translate('AttrCause'),
        'DiskBlocks'  => translate('AttrDiskBlocks'),
        'DiskPercent' => translate('AttrDiskPercent'),
        #'StorageDiskSpace'   => translate('AttrStorageDiskSpace'),
        'DiskSpace'   => translate('AttrEventDiskSpace'),
        'DateTime'    => translate('Date Time'),
        'EndDateTime'    => translate('AttrEndDateTime'),
        'EndDate'        => translate('AttrEndDate'),
        'EndTime'        => translate('AttrEndTime'),
        'EndWeekday'     => translate('AttrEndWeekday'),
        'ExistsInFileSystem'  => translate('ExistsInFileSystem'),
        'FilterServerId'     => translate('AttrFilterServer'),
        'Frames'      => translate('AttrFrames'),
        'Group'       => translate('Group'),
        'Id'          => translate('AttrId'),
        'Length'      => translate('AttrDuration'),
        'MaxScore'    => translate('AttrMaxScore'),
        'Monitor'   => translate('Monitor'),
        'MonitorId'   => translate('AttrMonitorId'),
        'MonitorName' => translate('AttrMonitorName'),
        'MonitorServerId'    => translate('AttrMonitorServer'),
        'Name'        => translate('AttrName'),
        'Notes'       => translate('AttrNotes'),
        'SecondaryStorageId'   => translate('AttrSecondaryStorageArea'),
        'ServerId'           => translate('AttrMonitorServer'),
        'StartDateTime'    => translate('AttrStartDateTime'),
        'StartDate'        => translate('AttrStartDate'),
        'StartTime'        => translate('AttrStartTime'),
        'StartWeekday'     => translate('AttrStartWeekday'),
        'StateId'            => translate('AttrStateId'),
        'StorageId'           => translate('AttrStorageArea'),
        'StorageServerId'    => translate('AttrStorageServer'),
        'SystemLoad'  => translate('AttrSystemLoad'),
        'Tags'         => translate('Tags'),
        'TotScore'    => translate('AttrTotalScore'),
      );
    }
    return self::$attrTypes;
  }

  public static function booleanValues() {
    if (!self::$booleanValues) {
      self::$booleanValues = array(
        'false' => translate('False'),
        'true' => translate('True')
      );
    }
    return self::$booleanValues;
  }

  public static function is_isnot_opTypes() {
    if (!self::$is_isnot_opTypes) {
      self::$is_isnot_opTypes = array(
        'IS'  => translate('OpIs'),
        'IS NOT'  => translate('OpIsNot'),
      );
    }
    return self::$is_isnot_opTypes;
  }

  public static function tags_opTypes() {
    if (!self::$tags_opTypes) {
      self::$tags_opTypes = array(
        '=' => translate('OpEq'),
        '!=' => translate('OpNe'),
      );
    }
    return self::$tags_opTypes;
  }

  public static function opTypes() {
    if (!self::$opTypes) {
      self::$opTypes = array(
        '='   => translate('OpEq'),
        '!='  => translate('OpNe'),
        '>='  => translate('OpGtEq'),
        '>'   => translate('OpGt'),
        '<'   => translate('OpLt'),
        '<='  => translate('OpLtEq'),
        '=~'  => translate('OpMatches'),
        '!~'  => translate('OpNotMatches'),
        '=[]' => translate('OpIn'),
        '![]' => translate('OpNotIn'),
        'IS'  => translate('OpIs'),
        'IS NOT'  => translate('OpIsNot'),
        'LIKE' => translate('OpLike'),
        'NOT LIKE' => translate('OpNotLike'),
      );
    }
    return self::$opTypes;
  }

  public static function archiveTypes() {
    if (!self::$archiveTypes) {
      self::$archiveTypes = array(
        '' => translate('All'),
        '0' => translate('ArchUnarchived'),
        '1' => translate('ArchArchived')
      );
    }
    return self::$archiveTypes;
  }

  public function widget() {
    $html = '<table id="fieldsTable" class="filterTable"><tbody>';
    $opTypes = $this->opTypes();
    $archiveTypes = $this->archiveTypes();

    $terms = $this->terms();
    $obracketTypes = array();
    $cbracketTypes = array();
    if ( count($terms) ) {
      for ( $i = 0; $i <= count($terms)-2; $i++ ) {
        $obracketTypes[$i] = str_repeat('(', $i);
        $cbracketTypes[$i] = str_repeat(')', $i);
      }
    }

    $is_isnot_opTypes = $this->is_isnot_opTypes();
    $booleanValues = $this->booleanValues();

    $conjunctionTypes = getFilterQueryConjunctionTypes();
    $storageareas = null;
    $weekdays = array();
    for ( $i = 0; $i < 7; $i++ ) {
      $weekdays[$i] = date('D', mktime(12, 0, 0, 1, $i+1, 2001));
    }
    $states = array();
    foreach ( dbFetchAll('SELECT `Id`, `Name` FROM `States` ORDER BY lower(`Name`) ASC') as $state_row ) {
      $states[$state_row['Id']] = validHtmlStr($state_row['Name']);
    }
    $servers = array();
    $servers['ZM_SERVER_ID'] = 'Current Server';
    $servers['NULL'] = 'No Server';
    global $Servers;
    foreach ( $Servers as $server ) {
      $servers[$server->Id()] = validHtmlStr($server->Name());
    }
    $monitors = array();
    $monitor_names = array();
    foreach ( dbFetchAll('SELECT `Id`, `Name` FROM `Monitors` WHERE Deleted=false ORDER BY lower(`Name`) ASC') as $monitor ) {
      if ( visibleMonitor($monitor['Id']) ) {
        $monitors[$monitor['Id']] = new Monitor($monitor);
        $monitor_names[] = validHtmlStr($monitor['Name']);
      }
    }
    $zones = array();
    foreach ( dbFetchAll('SELECT Id, Name, MonitorId FROM Zones WHERE EXISTS (SELECT * FROM Monitors WHERE Monitors.Id=MonitorId AND Monitors.Deleted=false) ORDER BY lower(`Name`) ASC') as $zone ) {
      if ( visibleMonitor($zone['MonitorId']) ) {
        if ( isset($monitors[$zone['MonitorId']]) ) {
          $zone['Name'] = validHtmlStr($monitors[$zone['MonitorId']]->Name().': '.$zone['Name']);
          $zones[$zone['Id']] = new Zone($zone);
        }
      }
    }
    $availableTags = array(''=>translate('No Tag'));
    foreach ( dbFetchAll('SELECT Id, Name FROM Tags ORDER BY lower(`Name`) ASC') AS $tag ) {
      $availableTags[$tag['Id']] = validHtmlStr($tag['Name']);
    }

    for ($i=0; $i < count($terms); $i++) {
      $term = $terms[$i];
      if ( ! isset( $term['op'] ) )
        $term['op'] = '=';
      if ( ! isset( $term['attr'] ) )
        $term['attr'] = 'Id';
      if ( ! isset( $term['val'] ) )
        $term['val'] = '';
      if ( ! isset( $term['cnj'] ) )
        $term['cnj'] = 'and';
      if ( ! isset( $term['cbr'] ) )
        $term['cbr'] = '';
      if ( ! isset( $term['obr'] ) )
        $term['obr'] = '';
      $html .= '<tr>'.PHP_EOL;
      $html .= ($i == 0) ?  '<td>&nbsp;</td>' : '<td>'.htmlSelect("filter[Query][terms][$i][cnj]", $conjunctionTypes, $term['cnj'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
      $html .= '<td>'. ( count($terms) > 2 ? htmlSelect("filter[Query][terms][$i][obr]", $obracketTypes, $term['obr'], ['class'=>'chosen', 'data-placeholder'=>' ']) : '&nbsp;').'</td>'.PHP_EOL;
      $html .= '<td>'.htmlSelect("filter[Query][terms][$i][attr]", $this->attrTypes(), $term['attr'], array('data-on-change-this'=>'checkValue', 'class'=>'chosen chosen-full-width')).'</td>'.PHP_EOL;
      if ( isset($term['attr']) ) {
        if ( $term['attr'] == 'Archived' ) {
          $html .= '<td>'.translate('OpEq').'<input type="hidden" name="filter[Query][terms]['.$i.'][op]" value="="/></td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $archiveTypes, $term['val'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        
        
        
        } else if ( $term['attr'] == 'Tags') {
          // Error($term['attr']);
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $options = ['class'=>'chosen chosen-full-width', 'multiple'=>'multiple'];
          $selected = explode(',', $term['val']);
          if (count($selected) == 1 and !$selected[0]) {
            $selected = null;
          }
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $availableTags, $selected, $options).'</td>'.PHP_EOL;
          // ZM\Debug('$availableTags: '.$availableTags);
          // ZM\Debug('$selected: '.$selected);



        } else if ( $term['attr'] == 'DateTime' || $term['attr'] == 'StartDateTime' || $term['attr'] == 'EndDateTime') {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td><input type="text" name="filter[Query][terms]['.$i.'][val]" id="filter[Query][terms]['.$i.'][val]" value="'.(isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'').'"/></td>'.PHP_EOL;
        } else if ( $term['attr'] == 'Date' || $term['attr'] == 'StartDate' || $term['attr'] == 'EndDate' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td><input type="text" name="filter[Query][terms]['.$i.'][val]" id="filter[Query][terms]['.$i.'][val]" value="'.(isset($term['val'])?validHtmlStr($term['val']):'').'"/></td>'.PHP_EOL;
        } else if ( $term['attr'] == 'StartTime' || $term['attr'] == 'EndTime' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td><input type="text" name="filter[Query][terms]['.$i.'][val]" id="filter[Query][terms]['.$i.'][val]" value="'.(isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'' ).'"/></td>'.PHP_EOL;
        } else if ( $term['attr'] == 'ExistsInFileSystem' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $is_isnot_opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $booleanValues, $term['val'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        } else if ( $term['attr'] == 'Group') {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", Group::get_dropdown_options(), $term['val'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        } else if ( $term['attr'] == 'StateId' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $states, $term['val'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        } else if ( strpos($term['attr'], 'Weekday') !== false ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $weekdays, $term['val'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        } else if ( $term['attr'] == 'Monitor' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $selected = explode(',', $term['val']);
          if (count($selected) == 1 and !$selected[0]) {
            $selected = null;
          }
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $monitors, $selected, ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        } else if ( $term['attr'] == 'MonitorName' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", array_combine($monitor_names,$monitor_names), $term['val'],
          ['class'=>'chosen chosen-full-width', 'multiple'=>'multiple']).'</td>'.PHP_EOL;
        } else if ( $term['attr'] == 'ServerId' || $term['attr'] == 'MonitorServerId' || $term['attr'] == 'StorageServerId' || $term['attr'] == 'FilterServerId' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $servers, $term['val'],
          ['class'=>'chosen chosen-full-width', 'multiple'=>'multiple']).'</td>'.PHP_EOL;
        } else if ( ($term['attr'] == 'StorageId') || ($term['attr'] == 'SecondaryStorageId') ) {
          if (!$storageareas) {
            $storageareas = array('' => array('Name'=>'NULL Unspecified'), '0' => array('Name'=>'Zero')) + ZM_Object::Objects_Indexed_By_Id('ZM\Storage');
          }

          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $storageareas, $term['val'],
          ['class'=>'chosen chosen-full-width', 'multiple'=>'multiple']).'</td>'.PHP_EOL;
        } elseif ( $term['attr'] == 'AlarmedZoneId' ) {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][val]", $zones, $term['val'],
          ['class'=>'chosen chosen-full-width', 'multiple'=>'multiple']).'</td>'.PHP_EOL;
        } else {
          $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
          $html .= '<td><input type="text" name="filter[Query][terms]['.$i.'][val]" value="'.validHtmlStr($term['val']).'"/></td>'.PHP_EOL;
        }
      } else { # no attr ?
        $html .= '<td>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op'], ['class'=>'chosen chosen-full-width']).'</td>'.PHP_EOL;
        $html .= '<td><input type="text" name="filter[Query][terms]['.$i.'][val]" value="'.(isset($term['val'])?validHtmlStr($term['val']):'' ).'"/></td>'.PHP_EOL;
      }
      $html .= '<td>'.( count($terms) > 2 ? htmlSelect("filter[Query][terms][$i][cbr]", $cbracketTypes, $term['cbr'], ['class'=>'chosen', 'data-placeholder'=>' ']) : '&nbsp;').'</td>'.PHP_EOL;
      $html .= '<td>
                <button type="button" data-on-click-this="addTerm">+</button>
                <button type="button" data-on-click-this="delTerm" '.(count($terms) == 1 ? 'disabled' : '' ).'>-</button>
              </td>
            </tr>
';
    } # end foreach term
    // Error($html);
    return $html;
  }  # end function widget()

  public function simple_widget() {
    $html = '<div id="fieldsTable" class="filterTable">';
    $terms = $this->terms();
    $attrTypes = $this->attrTypes();
    $opTypes = $this->opTypes();
    $archiveTypes = $this->archiveTypes();
    $is_isnot_opTypes = $this->is_isnot_opTypes();
    $booleanValues = $this->booleanValues();
    $storageareas= null;
    $states = array();
    foreach ( dbFetchAll('SELECT `Id`, `Name` FROM `States` ORDER BY lower(`Name`) ASC') as $state_row ) {
      $states[$state_row['Id']] = validHtmlStr($state_row['Name']);
    }
    $servers = array();
    $servers['ZM_SERVER_ID'] = 'Current Server';
    $servers['NULL'] = 'No Server';
    global $Servers;
    foreach ( $Servers as $server ) {
      $servers[$server->Id()] = validHtmlStr($server->Name());
    }
    $weekdays = array();
    for ( $i = 0; $i < 7; $i++ ) {
      $weekdays[$i] = date('D', mktime(12, 0, 0, 1, $i+1, 2001));
    }
    $availableTags = array(''=>translate('No Tag'));
    foreach ( dbFetchAll('SELECT Id, Name FROM Tags ORDER BY lower(`Name`) ASC') AS $tag ) {
      $availableTags[$tag['Id']] = validHtmlStr($tag['Name']);
    }

    for ($i=0; $i < count($terms); $i++) {
      $term = $terms[$i];
      if ( ! isset( $term['op'] ) )
        $term['op'] = '=';
      if ( ! isset( $term['attr'] ) )
        $term['attr'] = 'Id';
      if ( ! isset( $term['val'] ) )
        $term['val'] = '';
      if ( ! isset( $term['cnj'] ) )
        $term['cnj'] = 'and';
      if ( ! isset( $term['cbr'] ) )
        $term['cbr'] = '';
      if ( ! isset( $term['obr'] ) )
        $term['obr'] = '';

      #$html .= ($i == 0) ?  '' : htmlSelect("filter[Query][terms][$i][cnj]", $conjunctionTypes, $term['cnj']).PHP_EOL;
      $html .= ($i == 0) ?  '' : html_input("filter[Query][terms][$i][cnj]", 'hidden', $term['cnj']).PHP_EOL;
      if ( isset($term['attr']) ) {
        $html .= '<span class="term '.$term['attr'].'"><span class="term-label-wrapper"><label>'.$attrTypes[$term['attr']].'</label>';
        $html .= html_input("filter[Query][terms][$i][attr]", 'hidden', $term['attr']);
        $html .= html_input("filter[Query][terms][$i][op]", 'hidden', $term['op']).PHP_EOL;
        $html .= '<span>'. $term['op'].'</span></span>'.PHP_EOL;
        #$html .= '<span>'.htmlSelect("filter[Query][terms][$i][op]", $opTypes, $term['op']).'</span>'.PHP_EOL;

        if ( $term['attr'] == 'Archived' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $archiveTypes, $term['val'],['id'=>'filterArchived', 'class'=>'chosen chosen-auto-width']).PHP_EOL;
          $html .= '</span>';
        } else if ( $term['attr'] == 'Tags' ) {
          $selected = explode(',', $term['val']);
          // echo '<pre>selected: '; print_r($selected); echo '</pre>';
          if (count($selected) == 1 and !$selected[0]) {
            $selected = null;
          }
          $options = ['id'=>'filterTags', 'class'=>'chosen chosen-auto-width', 'multiple'=>'multiple', 'data-placeholder'=>translate('All Tags')];
          if (isset($term['cookie'])) {
            $options['data-cookie'] = $term['cookie'];

            if (!$selected and isset($_COOKIE[$term['cookie']]) and $_COOKIE[$term['cookie']])
              $selected = explode(',', $_COOKIE[$term['cookie']]);
          }
          // These echo statements print these variables at the top of the view.
          // echo '<pre>availableTags: '; print_r($availableTags); echo '</pre>';
          // echo '<pre>selected: '; print_r($selected); echo '</pre>';
          // echo '<pre>options: '; print_r($options); echo '</pre>';

          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $availableTags, $selected, $options).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
          // $html .= '<span>'.htmlSelect("filter[Query][terms][$i][val]", array_combine($availableTags,$availableTags), $term['val'],
          // $options).'</span>'.PHP_EOL;
          // $html .= '<span>'.htmlSelect("filter[Query][terms][$i][val]", $availableTags, $term['val'], $options).'</span>'.PHP_EOL;

          // Debug doesn't work here.
          // Debug('$availableTags: '.$availableTags);
          // Debug('$selected: '.$selected);
          // Debug('$options: '.$options);



        } else if ( $term['attr'] == 'DateTime' || $term['attr'] == 'StartDateTime' || $term['attr'] == 'EndDateTime') {
          $html .= '<span class="term-value-wrapper">';
          $html .= '<input type="text" class="term-value datetimepicker" name="filter[Query][terms]['.$i.'][val]"';
          if (isset($term['id'])) {
            $html .= ' id="'.$term['id'].'"';
          } else {
            $html .= ' id="filter[Query][terms]['.$i.'][val]"';
          }
          if (isset($term['cookie'])) {
            if ((!$term['val']) and isset($_COOKIE[$term['cookie']])) $term['val'] = $_COOKIE[$term['cookie']];
            $html .= ' data-cookie="'.validHtmlStr($term['cookie']).'"';
          }
          $html .= ' value="'.(isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'').'"';

          if (!isset($term['placeholder'])) $term['placeholder'] = translate('Attr'.$term['attr']);
          $html .= ' placeholder="'.$term['placeholder'].'"/>'.PHP_EOL;
          $html .= '</span>';
        } else if ( $term['attr'] == 'Date' || $term['attr'] == 'StartDate' || $term['attr'] == 'EndDate' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= '<input type="text" class="term-value datepicker" name="filter[Query][terms]['.$i.'][val]" id="filter[Query][terms]['.$i.'][val]"';
          if (isset($term['cookie'])) {
            if (!$term['val'] and isset($_COOKIE[$term['cookie']])) $term['val'] = $_COOKIE[$term['cookie']];
            $html .= ' data-cookie="'.$term['cookie'].'"';
          }
          $html .= ' value="'.(isset($term['val'])?validHtmlStr($term['val']):'').'" placeholder="'.translate('Attr'.$term['attr']).'"';
          $html .= '/></span>'.PHP_EOL;
        } else if ( $term['attr'] == 'StartTime' || $term['attr'] == 'EndTime' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= '<input type="text" name="filter[Query][terms]['.$i.'][val]" id="filter[Query][terms]['.$i.'][val]" value="'.(isset($term['val'])?validHtmlStr(str_replace('T', ' ', $term['val'])):'' ).'"/>'.PHP_EOL;
          $html .= '</span>';
        } else if ( $term['attr'] == 'ExistsInFileSystem' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $booleanValues, $term['val'], ['class'=>'chosen chosen-auto-width']).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'Group') {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", Group::get_dropdown_options(), $term['val'],
            ['class'=>'term-value chosen chosen-auto-width',
            'multiple'=>'multiple', 
            'data-placeholder'=>translate('All Groups')]).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'StateId' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $states, $term['val'], ['class'=>'chosen chosen-auto-width']).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( strpos($term['attr'], 'Weekday') !== false ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $weekdays, $term['val'], ['class'=>'chosen chosen-auto-width']).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'Monitor' ) {
          $monitors = [];
          foreach (Monitor::find(['Deleted'=>false], ['order'=>'lower(Name)']) as $m) {
            if ($m->canView()) {
              $monitors[$m->Id()] = $m->Id().' '.validHtmlStr($m->Name());
            }
          }
          $selected = explode(',', $term['val']);
          // echo '<pre>Monitor selected: '; print_r($selected); echo '</pre>';
          if (count($selected) == 1 and !$selected[0]) {
            $selected = null;
          }
          $options = ['class'=>'term-value chosen chosen-auto-width', 'multiple'=>'multiple', 'data-placeholder'=>translate('All Monitors')];
          if (isset($term['cookie'])) {
            $options['data-cookie'] = $term['cookie'];

            if (!$selected and isset($_COOKIE[$term['cookie']]) and $_COOKIE[$term['cookie']])
              $selected = explode(',', $_COOKIE[$term['cookie']]);
          }
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $monitors, $selected, $options).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'MonitorName' ) {
          $monitor_names = [];
          foreach (Monitor::find(['Deleted'=>false], ['order'=>'lower(Name)']) as $m) {
            if ($m->canView()) {
              $monitor_names[$m->Name()] = validHtmlStr($m->Name());
            }
          }
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", array_combine($monitor_names,$monitor_names), $term['val'],
            ['class'=>'term-value chosen chosen-auto-width', 'multiple'=>'multiple', 'data-placeholder'=>translate('All Monitors')]).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'ServerId' || $term['attr'] == 'MonitorServerId' || $term['attr'] == 'StorageServerId' || $term['attr'] == 'FilterServerId' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $servers, $term['val'],
            ['class'=>'term-value chosen chosen-auto-width', 'multiple'=>'multiple']).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( ($term['attr'] == 'StorageId') || ($term['attr'] == 'SecondaryStorageId') ) {
          if (!$storageareas) {
            $storageareas = array('' => array('Name'=>'NULL Unspecified'), '0' => array('Name'=>'Zero')) + ZM_Object::Objects_Indexed_By_Id('ZM\Storage');
          }
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $storageareas, $term['val'],
            ['class'=>'term-value chosen chosen-auto-width', 'multiple'=>'multiple']).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'AlarmedZoneId' ) {
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $zones, $term['val'],
            ['class'=>'term-value chosen chosen-auto-width', 'multiple'=>'multiple']).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else if ( $term['attr'] == 'Notes' ) {
          $attrs = ['id'=>'filterNotes', 'class'=>'term-value chosen chosen-auto-width', 'multiple'=>'multiple', 'data-placeholder'=>translate('Event Type')];
          $selected = explode(',', $term['val']);
          if (count($selected) == 1 and !$selected[0]) {
            $selected = null;
          }
          if (isset($term['cookie'])) {
            $attrs['data-cookie'] = $term['cookie'];

            if (!$selected and isset($_COOKIE[$term['cookie']]) and $_COOKIE[$term['cookie']])
              $selected = explode(',', $_COOKIE[$term['cookie']]);
          }
          $options = [
            'Motion' => 'Motion',
            'ONVIF' => 'ONVIF',
            'Linked' => 'Linked',
            'detected' => 'Any Object',
            'aplr' => 'Any license plate',
            'person'=>'Person',
            'boat' => 'Boat',
            'bus'  => 'Bus',
            'car' => 'Car',
            'truck' => 'Truck',
            'vehicle' => 'Vehicle'];
          $html .= '<span class="term-value-wrapper">';
          $html .= htmlSelect("filter[Query][terms][$i][val]", $options, $selected, $attrs).PHP_EOL;
          $html .= '</span>';
          $html .= $this->addButtonForFilterSelect("filter[Query][terms][$i][val]");
        } else {
          #$html .= $term['attr'];
          $html .= '<span class="term-value-wrapper">';
          $html .= '<input  class="term-value"type="text" name="filter[Query][terms]['.$i.'][val]" value="'.validHtmlStr($term['val']).'"/>'.PHP_EOL;
          $html .= '</span>';
        }
      } else { # no attr ?
        $html .= '<span class="term-value-wrapper">';
        $html .= '<input class="term-value" type="text" name="filter[Query][terms]['.$i.'][val]" value="'.(isset($term['val'])?validHtmlStr($term['val']):'' ).'"/></span>'.PHP_EOL;
        $html .= '</span>';
      }

      $html .= '</span>';
    } # end foreach term
    $html .= '</div>';
    // Error($html);
    return $html;
  }  # end function simple_widget()

  public function has_term($attr, $op=null) {
    foreach ($this->terms() as $term) {
      if (($term['attr'] == $attr) and ((!$op) or ($term['op']==$op)) ) return $term;
    }
    return false;
  }
  // Given an array of attr, sort terms by attr
  public function sort_terms($sort) {
    $new_terms = [];
    $old_terms = $this->terms();
    foreach ($sort as $attr) {
      for ($i=0; $i < count($old_terms); $i++) {
        if ($old_terms[$i]['attr'] == $attr) {
          if (!isset($old_terms[$i]['cnj'])) {
            $old_terms[$i]['cnj'] = 'and';
          }
          $new_terms[] = $old_terms[$i];
          array_splice($old_terms, $i, 1);
          $i--;
        }
      }
    }
    if (count($old_terms)) {
      $new_terms = array_merge($new_terms, $old_terms);
    }
    $this->terms($new_terms);
  }

  public function remove_invalid_terms() {
    $new_terms = [];
    $old_terms = $this->terms();
    foreach ($old_terms as $term) {
      $Term = new FilterTerm($this, $term);
      if ($Term->valid())
        $new_terms[] = $term;
    }
    $this->terms($new_terms);
  }

  public function canDelete($u=null) {
    global $user;
    if (!$u) $u = $user;
    if ($u->canEdit('System') or ($this->UserId() == $u->Id())) return true;
    return false;
  }

  public function canEdit($u=null) {
    global $user;
    if (!$u) $u=$user;
    if ($u->canEdit('System')) return true;

    if ($this->UserId() == $u->Id()) {
      if ($u->canEdit('Events')) return true;
      if ($u->canView('Events')) {
        // If we can only view events, then we can't perform filters that involve changing the event.
        if (!(
          $this->AutoExecute() and
          $this->AutoDelete() and
          $this->AutoUnarchive() and
          $this->AutoMove() and
          $this->AutoCopy()
        )) {
          return true;
        }
      }
    }
    return false;
  }

  function addButtonForFilterSelect($nameSelect) {
    if (isset($_COOKIE['zmUseOldMenuView']) and $_COOKIE["zmUseOldMenuView"] === 'true') {
      $html = '';
    } else {
      $html = PHP_EOL . '
        <span class="btn-term-remove-all">
          <button type="button" name="deleteBtn" data-on-click-this="resetSelectElement" data-select-target="'.$nameSelect.'">
            <i class="material-icons">clear</i>
            <span class="text"></span>
          </button>
        </span>' . PHP_EOL;
    }
    return $html;
  }
} # end class Filter
?>
