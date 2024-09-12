<?php
namespace ZM;
require_once('Object.php');
require_once('FilterTerm.php');

class Filter extends ZM_Object {
  protected static $table = 'Filters';

  protected $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'AutoExecute'     =>  0,
    'AutoExecuteCmd'  =>  '',
    'AutoEmail'       =>  0,
		'EmailTo'					=>	'',
		'EmailSubject'		=>	'',
		'EmailBody'				=>	'',
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
    'UserId'          =>  0,
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

  public function sql() {
    if ( ! isset($this->_sql) ) {
      $this->_sql = '';
      foreach ( $this->FilterTerms() as $term ) {
        #if ( ! ($term->is_pre_sql() or $term->is_post_sql()) ) {
          $this->_sql .= $term->sql();
        #} else {
          #$this->_sql .= '1';
        #}
      } # end foreach term
    }
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
    if ( ! isset($this->Terms) ) {
      $this->Terms = array();
      $_terms = $this->terms();
      for ( $i = 0; $i < count($_terms); $i++ ) {
        $term = new FilterTerm($this, $_terms[$i], $i);
        $this->Terms[] = $term;
      } # end foreach term
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
        $storage_ids[] = $term->value;
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
      $this->{'Query'} = jsonDecode($this->{'Query_json'});
    }
    return $this->{'Query_json'};
  }

  public function Query() {
    if ( func_num_args( ) ) {
      $this->{'Query'} = func_get_arg(0);
      $this->{'Query_json'} = jsonEncode($this->{'Query'});
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
        $this->{'Query'} = jsonDecode($this->{'Query_json'});
      }
    }
    return $this->{'Query'};
  }

  public static function find( $parameters = array(), $options = array() ) {
    return ZM_Object::_find(get_class(), $parameters, $options);
  }

  public static function find_one( $parameters = array(), $options = array() ) {
    return ZM_Object::_find_one(get_class(), $parameters, $options);
  }

  public function terms( ) {
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
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['sort_field'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['sort_field'] ) ) {
      return $this->{'Query'}['sort_field'];
    }
    return ZM_WEB_EVENT_SORT_FIELD;
    #return $this->defaults{'sort_field'};
  }

  public function sort_asc( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['sort_asc'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['sort_asc'] ) ) {
      return $this->{'Query'}['sort_asc'];
    }
    return ZM_WEB_EVENT_SORT_ORDER == 'asc' ? 1 : 0;
    #return $this->defaults{'sort_asc'};
  }

  public function limit( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['limit'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['limit'] ) )
      return $this->{'Query'}['limit'];
    return 0;
    #return $this->defaults{'limit'};
  }

  public function control($command, $server_id=null) {
    $Servers = $server_id ? Server::find(array('Id'=>$server_id)) : Server::find(array('Status'=>'Running'));
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

        $url = $Server->UrlToIndex();
        if ( ZM_OPT_USE_AUTH ) {
          if ( ZM_AUTH_RELAY == 'hashed' ) {
            $url .= '?auth='.generateAuthHash(ZM_AUTH_HASH_IPS);
          } else if ( ZM_AUTH_RELAY == 'plain' ) {
            $url = '?user='.$_SESSION['username'];
            $url = '?pass='.$_SESSION['password'];
          } else if ( ZM_AUTH_RELAY == 'none' ) {
            $url = '?user='.$_SESSION['username'];
          }
        }
        $url .= '&view=filter&object=filter&action=control&command='.$command.'&Id='.$this->Id().'&ServerId='.$Server->Id();
        Debug("sending command to $url");
        $data = array();
        if ( defined('ZM_ENABLE_CSRF_MAGIC') ) {
          require_once( 'includes/csrf/csrf-magic.php' );
          $data['__csrf_magic'] = csrf_get_tokens();
        }

        // use key 'http' even if you send the request to https://...
        $options = array(
          'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
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
      '![]' => 2,
      'and' => 3,
      'or' => 4,
      'IS' => 2,
      'IS NOT' => 2,
    );

    for ( $i = 0; $i < count($terms); $i++ ) {
      $term = $terms[$i];
      if ( !empty($term['cnj']) ) {
        while ( true ) {
          if ( !count($postfixStack) ) {
            $postfixStack[] = array('type'=>'cnj', 'value'=>$term['cnj'], 'sqlValue'=>$term['cnj']);
            break;
          } elseif ( $postfixStack[count($postfixStack)-1]['type'] == 'obr' ) {
            $postfixStack[] = array('type'=>'cnj', 'value'=>$term['cnj'], 'sqlValue'=>$term['cnj']);
            break;
          } elseif ( $priorities[$term['cnj']] < $priorities[$postfixStack[count($postfixStack)-1]['value']] ) {
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
        $dtAttr = false;
        switch ( $term['attr']) {
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
          ZM\Error('Unknown operator in filter '.$term['op']);
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
            if ( $value_upper != 'NULL' )
              $value = "'".date(STRF_FMT_DATETIME_DB, strtotime($value))."'";
            break;
          case 'Date':
          case 'EndDate':
          case 'StartDate':
            $value = 'to_days(\''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
            break;
          case 'Time':
          case 'EndTime':
          case 'StartTime':
            $value = 'extract(hour_second from \''.date(STRF_FMT_DATETIME_DB, strtotime($value)).'\')';
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
        $node = array('data'=>$element, 'count'=>2+$left['count']+$right['count'], 'right'=>$right, 'left'=>$left);
        $exprStack[] = $node;
      } else {
        ZM\Fatal('Unexpected element type \''.$element['type'].'\', value \''.$element['value'].'\'');
      }
    }
    if ( count($exprStack) != 1 ) {
      ZM\Fatal('Expression stack has '.count($exprStack).' elements');
    }
    return array_pop($exprStack);
  } # end function tree

  function addTerm($term=false, $position=null) {

    if ( !FilterTerm::is_valid_attr($term['attr']) ) {
      Error('Unsupported filter attribute ' . $term['attr']);
      //return $this;
    }

    $terms = $this->terms();

    if ( (!isset($position)) or ($position > count($terms)) )
      $position = count($terms);
    else if ( $position < 0 )
      $position = 0;

    if ( $term && ($position == 0) ) {
      # if only 1 term, don't need AND or OR
      unset($term['cnj']);
    }

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

} # end class Filter
?>
