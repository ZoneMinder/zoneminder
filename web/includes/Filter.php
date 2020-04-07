<?php
namespace ZM;
require_once('Object.php');

class Filter extends ZM_Object {
  protected static $table = 'Filters';

  protected $defaults = array(
    'Id'              =>  null,
    'Name'            =>  '',
    'AutoExecute'     =>  0,
    'AutoExecuteCmd'  =>  0,
    'AutoEmail'       =>  0,
		'EmailTo'					=>	'',
		'EmailSubject'		=>	'',
		'EmailBody'				=>	'',
    'AutoDelete'      =>  0,
    'AutoArchive'     =>  0,
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
  );

  public function Query_json() {
    if ( func_num_args( ) ) {
      $this->{'Query_json'} = func_get_arg(0);;
      $this->{'Query'} = jsonDecode($this->{'Query_json'});
    }
    return $this->{'Query_json'};
  }

  public function Query() {
    if ( func_num_args( ) ) {
      $this->{'Query'} = func_get_arg(0);
      $this->{'Query_json'} = jsonEncode($this->{'Query'});
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

	public function parse( $querySep='&amp;') {

    $filter = $this->Query();
    Warning(print_r($filter, true));

    $query = '';
    $sql = '';
    $fields = '';

    $validQueryConjunctionTypes = getFilterQueryConjunctionTypes();
    $StorageArea = NULL;

    # It is not possible to pass an empty array in the url, so we have to deal with there not being a terms field.
    $terms = (is_array($filter['terms'])) ? $filter['terms'] : array();

    if ( count($terms) ) {
      for ( $i = 0; $i < count($terms); $i++ ) {

        $term = $terms[$i];

        if ( isset($term['cnj']) && array_key_exists($term['cnj'], $validQueryConjunctionTypes) ) {
          $query .= $querySep.urlencode("filter[Query][terms][$i][cnj]").'='.urlencode($term['cnj']);
          $sql .= ' '.$term['cnj'].' ';
          $fields .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][cnj]\" value=\"".htmlspecialchars($term['cnj'])."\"/>\n";
        }
        if ( isset($term['obr']) && (string)(int)$term['obr'] == $term['obr'] ) {
          $query .= $querySep.urlencode("filter[Query][terms][$i][obr]").'='.urlencode($term['obr']);
          $sql .= ' '.str_repeat('(', $term['obr']).' ';
          $fields .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][obr]\" value=\"".htmlspecialchars($term['obr'])."\"/>\n";
        }
        if ( isset($term['attr']) ) {
          $query .= $querySep.urlencode("filter[Query][terms][$i][attr]").'='.urlencode($term['attr']);
          $fields .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][attr]\" value=\"".htmlspecialchars($term['attr'])."\"/>\n";
          switch ( $term['attr'] ) {
          case 'AlarmedZoneId':
            $term['op'] = 'EXISTS';
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
            $sql .= 'E.'.$term['attr'];
            break;
          case 'DiskPercent':
            // Need to specify a storage area, so need to look through other terms looking for a storage area, else we default to ZM_EVENTS_PATH
            if ( ! $StorageArea ) {
              for ( $j = 0; $j < count($terms); $j++ ) {
                if (
                  isset($terms[$j]['attr'])
                  and
                  ($terms[$j]['attr'] == 'StorageId')
                  and
                  isset($terms[$j]['val'])
                ) {
                  $StorageArea = ZM\Storage::find_one(array('Id'=>$terms[$j]['val']));
                  break;
                }
              } // end foreach remaining term
              if ( ! $StorageArea ) $StorageArea = new ZM\Storage();
            } // end no StorageArea found yet

            $sql .= getDiskPercent($StorageArea->Path());
            break;
          case 'DiskBlocks':
            // Need to specify a storage area, so need to look through other terms looking for a storage area, else we default to ZM_EVENTS_PATH
            if ( ! $StorageArea ) {
              for ( $j = $i; $j < count($terms); $j++ ) {
                if (
                  isset($terms[$j]['attr'])
                  and
                  ($terms[$j]['attr'] == 'StorageId')
                  and
                  isset($terms[$j]['val'])
                ) {
                  $StorageArea = ZM\Storage::find_one(array('Id'=>$terms[$j]['val']));
                }
              } // end foreach remaining term
            } // end no StorageArea found yet
            $sql .= getDiskBlocks( $StorageArea );
            break;
          case 'SystemLoad':
            $sql .= getLoad();
            break;
          }
          $valueList = array();
          foreach ( preg_split('/["\'\s]*?,["\'\s]*?/', preg_replace('/^["\']+?(.+)["\']+?$/', '$1', $term['val'])) as $value ) {
            switch ( $term['attr'] ) {

            case 'AlarmedZoneId':
              $value = '(SELECT * FROM Stats WHERE EventId=E.Id AND ZoneId='.$value.')';
              break;
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
              if ( $value == 'ZM_SERVER_ID' ) {
                $value = ZM_SERVER_ID;
              } else if ( $value == 'NULL' ) {

              } else {
                $value = dbEscape($value);
              }
              break;
            case 'StorageId':
              $StorageArea = ZM\Storage::find_one(array('Id'=>$value));
              if ( $value != 'NULL' )
                $value = dbEscape($value);
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
              if ( $value != 'NULL' )
                $value = dbEscape($value);
              break;
            }
            $valueList[] = $value;
          } // end foreach value

          switch ( $term['op'] ) {
          case '=' :
          case '!=' :
          case '>=' :
          case '>' :
          case '<' :
          case '<=' :
          case 'LIKE' :
          case 'NOT LIKE':
            $sql .= ' '.$term['op'].' '. $value;
            break;
          case '=~' :
            $sql .= ' regexp '.$value;
            break;
          case '!~' :
            $sql .= ' not regexp '.$value;
            break;
          case '=[]' :
          case 'IN' :
            $sql .= ' IN ('.join(',', $valueList).')';
            break;
          case '![]' :
            $sql .= ' not in ('.join(',', $valueList).')';
            break;
          case 'EXISTS' :
            $sql .= ' EXISTS ' .$value;
            break;
          case 'IS' :
            if ( $value == 'Odd' )  {
              $sql .= ' % 2 = 1';
            } else if ( $value == 'Even' )  {
              $sql .= ' % 2 = 0';
            } else {
              $sql .= " IS $value";
            }
            break;
          case 'IS NOT' :
            $sql .= " IS NOT $value";
            break;
          default:
            ZM\Warning('Invalid operator in filter: ' . print_r($term['op'], true));
          } // end switch op

          $query .= $querySep.urlencode("filter[Query][terms][$i][op]").'='.urlencode($term['op']);
          $fields .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][op]\" value=\"".htmlspecialchars($term['op'])."\"/>\n";
          if ( isset($term['val']) ) {
            $query .= $querySep.urlencode("filter[Query][terms][$i][val]").'='.urlencode($term['val']);
            $fields .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][val]\" value=\"".htmlspecialchars($term['val'])."\"/>\n";
          }
        } // end if isset($term['attr'])
        if ( isset($term['cbr']) && (string)(int)$term['cbr'] == $term['cbr'] ) {
          $query .= $querySep.urlencode("filter[Query][terms][$i][cbr]").'='.urlencode($term['cbr']);
          $sql .= ' '.str_repeat(')', $term['cbr']);
          $fields .= "<input type=\"hidden\" name=\"filter[Query][terms][$i][cbr]\" value=\"".htmlspecialchars($term['cbr'])."\"/>\n";
        }
      } // end foreach term
      if ( $sql )
        $sql = ' AND ( '.$sql.' )';
    } else {
      $query = $querySep;
      #.urlencode('filter[Query][terms]=[]');
    } // end if terms
    $this->{'query'} = $query;
    $this->{'sql'} = $sql;
    $this->{'fields'} = $fields;

    #if ( 0 ) {
    #// ICON I feel like these should be here, but not yet
    #if ( isset($filter['Query']['sort_field']) ) {
    #$sql .= ' ORDER BY ' . $filter['Query']['sort_field'] . (
    #( $filter['Query']['sort_asc'] ? ' ASC' : ' DESC' ) );
    #}
    #if ( $filter['Query']['limit'] ) {
    #$sql .= ' LIMIT ' . validInt($filter['Query']['limit']);
    #}
    #}
  } // end function parse($querySep='&amp;')

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
    return ZM_WEB_EVENT_SORT_ORDER;
    #return $this->defaults{'sort_asc'};
  }

  public function fields() {
    if ( !property_exists($this, 'fields') ) {
      $this->parse();
    }
    return $this->{'fields'};
  }

  public function sql() {
    if ( !property_exists($this, 'sql') ) {
      $this->sql();
    }
    return $this->{'sql'};
  }

  public function query_string() {
    if ( !property_exists($this, 'query') ) {
      $this->query();
    }
    return $this->{'query'};
  }

  public function limit( ) {
    if ( func_num_args( ) ) {
      $Query = $this->Query();
      $Query['limit'] = func_get_arg(0);
      $this->Query($Query);
    }
    if ( isset( $this->Query()['limit'] ) )
      return $this->{'Query'}['limit'];
    return 100;
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
        Logger::Debug("Controlling filter locally $command for server ".$Server->Id());
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
        Logger::Debug("sending command to $url");
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
    Logger::Debug("$command status:$status output:".implode("\n", $output));
    return $status;
  }

} # end class Filter

?>
