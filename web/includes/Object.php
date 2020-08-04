<?php
namespace ZM;
require_once('database.php');

$object_cache = array();

class ZM_Object {

  public function __construct($IdOrRow = NULL) {
    $class = get_class($this);

    $row = NULL;
    if ( $IdOrRow ) {
      if ( is_integer($IdOrRow) or ctype_digit($IdOrRow) ) {
        $table = $class::$table;
        $row = dbFetchOne("SELECT * FROM `$table` WHERE `Id`=?", NULL, array($IdOrRow));
        if ( !$row ) {
          Error("Unable to load $class record for Id=$IdOrRow");
        }
      } elseif ( is_array($IdOrRow) ) {
        $row = $IdOrRow;
      }

      if ( $row ) {
        global $object_cache;
        if ( ! isset($object_cache[$class]) ) {
          $object_cache[$class] = array();
        }
        $cache = &$object_cache[$class];

        foreach ($row as $k => $v) {
          $this->{$k} = $v;
        }
        $cache[$row['Id']] = $this;
      }
    } # end if isset($IdOrRow)
  } # end function __construct

  public function __call($fn, array $args){
    $type = (array_key_exists($fn, $this->defaults) && is_array($this->defaults[$fn])) ? $this->defaults[$fn]['type'] : 'scalar';
    if ( count($args) ) {
      if ( $type == 'set' and is_array($args[0]) ) {
        $this->{$fn} = implode(',', $args[0]);
      } else if ( array_key_exists($fn, $this->defaults) && is_array($this->defaults[$fn]) && isset($this->defaults[$fn]['filter_regexp']) ) {
        if ( is_array($this->defaults[$fn]['filter_regexp']) ) {
          foreach ( $this->defaults[$fn]['filter_regexp'] as $regexp ) {
            $this->{$fn} = preg_replace($regexp, '', $args[0]);
          }
        } else {
          $this->{$fn} = preg_replace($this->defaults[$fn]['filter_regexp'], '', $args[0]);
        }
      } else {
        $this->{$fn} = $args[0];
      }
    }

    if ( property_exists($this, $fn) ) {
      return $this->{$fn};
    } else {
      if ( array_key_exists($fn, $this->defaults) ) {
        if ( is_array($this->defaults[$fn]) ) {
          return $this->defaults[$fn]['default'];
        }
        return $this->defaults[$fn];
      } else {
        $backTrace = debug_backtrace();
        Warning("Unknown function call Object->$fn from ".print_r($backTrace,true));
      }
    }
  }

  public static function _find($class, $parameters = null, $options = null ) {
    $table = $class::$table;
    $filters = array();
    $sql = 'SELECT * FROM `'.$table.'` ';
    $values = array();

    if ( $parameters ) {
      $fields = array();
      $sql .= 'WHERE ';
      foreach ( $parameters as $field => $value ) {
        if ( $value == null ) {
          $fields[] = '`'.$field.'` IS NULL';
        } else if ( is_array($value) ) {
          $func = function(){return '?';};
          $fields[] = '`'.$field.'` IN ('.implode(',', array_map($func, $value)). ')';
          $values += $value;

        } else {
          $fields[] = '`'.$field.'`=?';
          $values[] = $value;
        }
      }
      $sql .= implode(' AND ', $fields );
    }
    if ( $options ) {
      if ( isset($options['order']) ) {
        $sql .= ' ORDER BY ' . $options['order'];
      }
      if ( isset($options['limit']) ) {
        if ( is_integer($options['limit']) or ctype_digit($options['limit']) ) {
          $sql .= ' LIMIT ' . $options['limit'];
        } else {
          $backTrace = debug_backtrace();
          Error('Invalid value for limit('.$options['limit'].') passed to '.get_class()."::find from ".print_r($backTrace,true));
          return array();
        }
      }
    }
    $rows = dbFetchAll($sql, NULL, $values);
    $results = array();
    if ( $rows ) {
      foreach ( $rows as $row ) {
        array_push($results , new $class($row));
      }
    }
    return $results;
  } # end public function _find()

  public static function _find_one($class, $parameters = array(), $options = array() ) {
    global $object_cache;
    if ( ! isset($object_cache[$class]) ) {
      $object_cache[$class] = array();
    }
    $cache = &$object_cache[$class];
    if ( 
        ( count($parameters) == 1 ) and
        isset($parameters['Id']) and
        isset($cache[$parameters['Id']]) ) {
      return $cache[$parameters['Id']];
    }
    $options['limit'] = 1;
    $results = ZM_Object::_find($class, $parameters, $options);
    if ( ! sizeof($results) ) {
      return;
    }
    return $results[0];
  }

  public static function _clear_cache($class) {
    global $object_cache;
    $object_cache[$class] = array();
  }

  public static function Objects_Indexed_By_Id($class) {
    $results = array();
    foreach ( ZM_Object::_find($class, null, array('order'=>'lower(Name)')) as $Object ) {
      $results[$Object->Id()] = $Object;
    }
    return $results;
  }

  public function to_json() {
    $json = array();
    foreach ($this->defaults as $key => $value) {
      if ( is_callable(array($this, $key), false) ) {
        $json[$key] = $this->$key();
      } else if ( property_exists($this, $key) ) {
        $json[$key] = $this->{$key};
      } else {
        $json[$key] = $this->defaults[$key];
      }
    }
    return json_encode($json);
  }

  public function set($data) {
    foreach ( $data as $field => $value ) {
      if ( method_exists($this, $field) and is_callable(array($this, $field), false) ) {
        $this->$field($value);
      } else {
        if ( is_array($value) ) {
# perhaps should turn into a comma-separated string
          $this->{$field} = implode(',', $value);
        } else if ( is_string($value) ) {
          if ( array_key_exists($field, $this->defaults) && is_array($this->defaults[$field]) && isset($this->defaults[$field]['filter_regexp']) ) {
            if ( is_array($this->defaults[$field]['filter_regexp']) ) {
              foreach ( $this->defaults[$field]['filter_regexp'] as $regexp ) {
                $this->{$field} = preg_replace($regexp, '', trim($value));
              }
            } else {
              $this->{$field} = preg_replace($this->defaults[$field]['filter_regexp'], '', trim($value));
            }
          } else {
            $this->{$field} = trim($value);
          }
        } else if ( is_integer($value) ) {
          $this->{$field} = $value;
        } else if ( is_bool($value) ) {
          $this->{$field} = $value;
        } else if ( is_null($value) ) {
          $this->{$field} = $value;
        } else {
          Error("Unknown type $field => $value of var " . gettype($value));
          $this->{$field} = $value;
        }
      } # end if method_exists
    } # end foreach $data as $field=>$value
  } # end function set($data)

  /* types is an array of fields telling use that the input might be a checkbox so not present in the input, but therefore has a value
   */
  public function changes($new_values, $defaults=null) {
    $changes = array();

    if ( $defaults ) {
      foreach ( $defaults as $field => $type ) {
        if ( isset($new_values[$field]) ) {
          # Will have already been handled above
          continue;
        }

        if ( isset($this->defaults[$field]) ) {
          if ( is_array($this->defaults[$field]) ) {
            $new_values[$field] = $this->defaults[$field]['default'];
          } else {
            $new_values[$field] = $this->defaults[$field];
          }
        }
      } # end foreach default
    } # end if defaults

    foreach ( $new_values as $field => $value ) {

      if ( method_exists($this, $field) ) {

        if ( array_key_exists($field, $this->defaults) && is_array($this->defaults[$field]) && isset($this->defaults[$field]['filter_regexp']) ) {
          if ( is_array($this->defaults[$field]['filter_regexp']) ) {
            foreach ( $this->defaults[$field]['filter_regexp'] as $regexp ) {
              $value = preg_replace($regexp, '', trim($value));
            }
          } else {
            $value = preg_replace($this->defaults[$field]['filter_regexp'], '', trim($value));
          }
        }

        $old_value = $this->$field();
        if ( is_array($old_value) ) {
          $diff = array_recursive_diff($old_value, $value);
          if ( count($diff) ) {
            $changes[$field] = $value;
          }
        } else if ( $this->$field() != $value ) {
          $changes[$field] = $value;
        }
      } else if ( property_exists($this, $field) ) {
        $type = (array_key_exists($field, $this->defaults) && is_array($this->defaults[$field])) ? $this->defaults[$field]['type'] : 'scalar';
        if ( $type == 'set' ) {
          $old_value = is_array($this->$field) ? $this->$field : explode(',', $this->$field);
          $new_value = is_array($value) ? $value : explode(',', $value);

          $diff = array_recursive_diff($old_value, $new_value);
          if ( count($diff) ) {
            $changes[$field] = $new_value;
          }

          # Input might be a command separated string, or an array

        } else {
          if ( array_key_exists($field, $this->defaults) && is_array($this->defaults[$field]) && isset($this->defaults[$field]['filter_regexp']) ) {
            if ( is_array($this->defaults[$field]['filter_regexp']) ) {
              foreach ( $this->defaults[$field]['filter_regexp'] as $regexp ) {
                $value = preg_replace($regexp, '', trim($value));
              }
            } else {
              $value = preg_replace($this->defaults[$field]['filter_regexp'], '', trim($value));
            }
          }
          if ( $this->{$field} != $value ) {
            $changes[$field] = $value;
          }
        }
      } else if ( array_key_exists($field, $this->defaults) ) {
        if ( is_array($this->defaults[$field]) and isset($this->defaults[$field]['default']) ) {
          $default = $this->defaults[$field]['default'];
        } else {
          $default = $this->defaults[$field];
        }

        if ( $default != $value ) {
          $changes[$field] = $value;
        }
      }
    } # end foreach newvalue


    return $changes;
  } # end public function changes

  public function save($new_values = null) {
    $class = get_class($this);
    $table = $class::$table;

    if ( $new_values ) {
      $this->set($new_values);
    }

    # Set defaults.  Note that we only replace "" with null, not other values
    # because for example if we want to clear TimestampFormat, we clear it, but the default is a string value
    foreach ( $this->defaults as $field => $default ) {
      if ( (!property_exists($this, $field)) or ($this->{$field} === '') ) {
        if ( is_array($default) ) {
          $this->{$field} = $default['default'];
        } else if ( $default == null ) {
          $this->{$field} = $default;
        }
      }
    }

    $fields = array_filter(
      $this->defaults,
      function($v) {
        return !( 
          is_array($v)
          and
          isset($v['do_not_update'])
          and
          $v['do_not_update']
        );
      }
    );
    $fields = array_keys($fields);

    if ( $this->Id() ) {
      $sql = 'UPDATE `'.$table.'` SET '.implode(', ', array_map(function($field) {return '`'.$field.'`=?';}, $fields)).' WHERE Id=?';
      $values = array_map(function($field){ return $this->{$field};}, $fields);
      $values[] = $this->{'Id'};
      if ( dbQuery($sql, $values) )
        return true;
    } else {
      unset($fields['Id']);

      $sql = 'INSERT INTO `'.$table.
        '` ('.implode(', ', array_map(function($field) {return '`'.$field.'`';}, $fields)).
          ') VALUES ('.
          implode(', ', array_map(function($field){return '?';}, $fields)).')';

      $values = array_map(function($field){return $this->$field();}, $fields);
      if ( dbQuery($sql, $values) ) {
        $this->{'Id'} = dbInsertId();
        return true;
      }
    }
    return false;
  } // end function save

  public function delete() {
    $class = get_class($this);
    $table = $class::$table;
    dbQuery("DELETE FROM `$table` WHERE Id=?", array($this->{'Id'}));
    if ( isset($object_cache[$class]) and isset($object_cache[$class][$this->{'Id'}]) )
      unset($object_cache[$class][$this->{'Id'}]);
  }

  public function lock() {
    $class = get_class($this);
    $table = $class::$table;
    $row = dbFetchOne("SELECT * FROM `$table` WHERE `Id`=?", NULL, array($this->Id()));
    if ( !$row ) {
      Error("Unable to lock $class record for Id=".$this->Id());
    }
  }
} # end class Object
?>
