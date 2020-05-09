<?php

namespace ZM;
require_once( 'config.php' );

class Logger {
  private static $instance;

  const DEBUG = 1;
  const INFO = 0;
  const WARNING = -1;
  const ERROR = -2;
  const FATAL = -3;
  const PANIC = -4;
  const NOLOG = -5;   // Special artificial level to prevent logging

  private $initialised = false;

  private $id = 'web';
  private $idRoot = 'web';
  private $idArgs = '';
  private $useErrorLog = true;

  private $level = self::INFO;
  private $termLevel = self::NOLOG;
  private $databaseLevel = self::NOLOG;
  private $fileLevel = self::NOLOG;
  private $weblogLevel = self::NOLOG;
  private $syslogLevel = self::NOLOG;
  private $effectiveLevel = self::NOLOG;

  private $hasTerm = false;

  private $logPath = ZM_PATH_LOGS;
  private $logFile = '';
  private $logFd = NULL;

  public static $codes = array(
    self::DEBUG => 'DBG',
    self::INFO => 'INF',
    self::WARNING => 'WAR',
    self::ERROR => 'ERR',
    self::FATAL => 'FAT',
    self::PANIC => 'PNC',
    self::NOLOG => 'OFF',
  );
  private static $syslogPriorities = array(
    self::DEBUG   => LOG_DEBUG,
    self::INFO    => LOG_INFO,
    self::WARNING => LOG_WARNING,
    self::ERROR   => LOG_ERR,
    self::FATAL   => LOG_ERR,
    self::PANIC   => LOG_ERR,
  );
  private static $phpErrorLevels = array(
    self::DEBUG   => E_USER_NOTICE,
    self::INFO    => E_USER_NOTICE,
    self::WARNING => E_USER_WARNING,
    self::ERROR   => E_USER_WARNING,
    self::FATAL   => E_USER_ERROR,
    self::PANIC   => E_USER_ERROR,
  );

  private function __construct() {
    $this->hasTerm = (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']));
    $this->logFile = $this->logPath.'/'.$this->id.'.log';
  }

  public function __destruct() {
    $this->terminate();
  }

  public function initialise( $options=array() ) {
    if ( !empty($options['id']) )
      $this->id = $options['id'];

    //if ( isset($options['useErrorLog']) )
    //$this->useErrorLog = $options['useErrorLog'];
    if ( isset($options['logPath']) ) {
      $this->logPath = $options['logPath'];
      $tempLogFile = $this->logPath.'/'.$this->id.'.log';
    }
    if ( isset($options['logFile']) )
      $tempLogFile = $options['logFile'];
    else
      $tempLogFile = $this->logPath.'/'.$this->id.'.log';
    if ( !is_null($logFile = $this->getTargettedEnv('LOG_FILE')) )
      $tempLogFile = $logFile;

    $tempLevel = self::INFO;
    $tempTermLevel = $this->termLevel;
    $tempDatabaseLevel = $this->databaseLevel;
    $tempFileLevel = $this->fileLevel;
    $tempSyslogLevel = $this->syslogLevel;
    $tempWeblogLevel = $this->weblogLevel;

    if ( isset($options['termLevel']) )
      $tempTermLevel = $options['termLevel'];
    if ( isset($options['databaseLevel']) )
      $tempDatabaseLevel = $options['databaseLevel'];
    else
      $tempDatabaseLevel = ZM_LOG_LEVEL_DATABASE;
    if ( isset($options['fileLevel']) )
      $tempFileLevel = $options['fileLevel'];
    else
      $tempFileLevel = ZM_LOG_LEVEL_FILE;
    if ( isset($options['weblogLevel']) )
      $tempWeblogLevel = $options['weblogLevel'];
    else
      $tempWeblogLevel = ZM_LOG_LEVEL_WEBLOG;
    if ( isset($options['syslogLevel']) )
      $tempSyslogLevel = $options['syslogLevel'];
    else
      $tempSyslogLevel = ZM_LOG_LEVEL_SYSLOG;

    if ( $value = getenv('LOG_PRINT') )
      $tempTermLevel = $value ? self::DEBUG : self::NOLOG;

    if ( !is_null($level = $this->getTargettedEnv('LOG_LEVEL')) )
      $tempLevel = $level;

    if ( !is_null($level = $this->getTargettedEnv('LOG_LEVEL_TERM')) )
      $tempTermLevel = $level;
    if ( !is_null($level = $this->getTargettedEnv('LOG_LEVEL_DATABASE')) )
      $tempDatabaseLevel = $level;
    if ( !is_null($level = $this->getTargettedEnv('LOG_LEVEL_FILE')) )
      $tempFileLevel = $level;
    if ( !is_null($level = $this->getTargettedEnv('LOG_LEVEL_SYSLOG')) )
      $tempSyslogLevel = $level;
    if ( !is_null($level = $this->getTargettedEnv('LOG_LEVEL_WEBLOG')) )
      $tempWeblogLevel = $level;

    if ( ZM_LOG_DEBUG ) {
      foreach ( explode( '|', ZM_LOG_DEBUG_TARGET ) as $target ) {
        if ( $target == $this->id || $target == '_'.$this->id || $target == $this->idRoot || $target == '_'.$this->idRoot || $target == '' ) {
          if ( ZM_LOG_DEBUG_LEVEL > self::NOLOG ) {
            $tempLevel = $this->limit( ZM_LOG_DEBUG_LEVEL );
            if ( ZM_LOG_DEBUG_FILE != '' ) {
              $tempLogFile = ZM_LOG_DEBUG_FILE;
              $tempFileLevel = $tempLevel;
            }
          }
        }
      } // end foreach target
    } // end if DEBUG

    $this->logFile( $tempLogFile );
    $this->termLevel( $tempTermLevel );
    $this->databaseLevel( $tempDatabaseLevel );
    $this->fileLevel( $tempFileLevel );
    $this->syslogLevel( $tempSyslogLevel );
    $this->weblogLevel( $tempWeblogLevel );

    $this->level( $tempLevel );

    $this->initialised = true;

    //Logger::Debug( "LogOpts: level=".self::$codes[$this->level]."/".self::$codes[$this->effectiveLevel].", screen=".self::$codes[$this->termLevel].", database=".self::$codes[$this->databaseLevel].", logfile=".self::$codes[$this->fileLevel]."->".$this->logFile.", weblog=".self::$codes[$this->weblogLevel].", syslog=".self::$codes[$this->syslogLevel] );
  }

  private function terminate() {
    if ( $this->initialised ) {
      if ( $this->fileLevel > self::NOLOG )
        $this->closeFile();
      if ( $this->syslogLevel > self::NOLOG )
        $this->closeSyslog();
    }
    $this->initialised = false;
  }

  private function limit( $level ) {
    if ( $level > self::DEBUG )
      return( self::DEBUG );
    if ( $level < self::NOLOG )
      return( self::NOLOG );
    return( $level );
  }

  private function getTargettedEnv( $name ) {
    $envName = $name.'_'.$this->id;
    $value = getenv( $envName );
    if ( $value === false && $this->id != $this->idRoot )
      $value = getenv( $name.'_'.$this->idRoot );
    if ( $value === false )
      $value = getenv( $name );
    return( $value !== false ? $value : NULL );
  }

  public static function fetch( $initialise=true ) {
    if ( !isset(self::$instance) ) {
      $class = __CLASS__;
      self::$instance = new $class;
      if ( $initialise )
        self::$instance->initialise( array( 'id'=>'web_php', 'syslogLevel'=>self::INFO, 'weblogLevel'=>self::INFO ) );
    }
    return self::$instance;
  }

  public static function Debug( $string ) {
    Logger::fetch()->logPrint( Logger::DEBUG, $string );
  }

  public function id( $id=NULL ) {
    if ( isset($id) && $this->id != $id ) {
      // Remove whitespace
      $id = preg_replace( '/\S/', '', $id );
      // Replace non-alphanum with underscore
      $id = preg_replace( '/[^a-zA-Z_]/', '_', $id );

      if ( $this->id != $id ) {
        $this->id = $this->idRoot = $id;
        if ( preg_match( '/^([^_]+)_(.+)$/', $id, $matches ) ) {
          $this->idRoot = $matches[1];
          $this->idArgs = $matches[2];
        }
      }
    }
    return $this->id;
  }

  public function level( $level ) {
    if ( isset($level) ) {
      $lastLevel = $this->level;
      $this->level = $this->limit($level);
      $this->effectiveLevel = self::NOLOG;
      if ( $this->termLevel > $this->effectiveLevel )
        $this->effectiveLevel = $this->termLevel;
      if ( $this->databaseLevel > $this->effectiveLevel )
        $this->effectiveLevel = $this->databaseLevel;
      if ( $this->fileLevel > $this->effectiveLevel )
        $this->effectiveLevel = $this->fileLevel;
      if ( $this->weblogLevel > $this->effectiveLevel )
        $this->effectiveLevel = $this->weblogLevel;
      if ( $this->syslogLevel > $this->effectiveLevel )
        $this->effectiveLevel = $this->syslogLevel;
      if ( $this->effectiveLevel > $this->level )
        $this->effectiveLevel = $this->level;
      if ( !$this->hasTerm ) {
        if ( $lastLevel < self::DEBUG && $this->level >= self::DEBUG ) {
          $this->savedErrorReporting = error_reporting( E_ALL );
          $this->savedDisplayErrors = ini_set( 'display_errors', true );
        } elseif ( $lastLevel >= self::DEBUG && $this->level < self::DEBUG ) {
          error_reporting( $this->savedErrorReporting );
          ini_set( 'display_errors', $this->savedDisplayErrors );
        }
      }
    }
    return( $this->level );
  }

  public function debugOn() {
    return( $this->effectiveLevel >= self::DEBUG );
  }

  public function termLevel( $termLevel ) {
    if ( isset($termLevel) ) {
      $termLevel = $this->limit($termLevel);
      if ( $this->termLevel != $termLevel )
        $this->termLevel = $termLevel;
    }
    return( $this->termLevel );
  }

  public function databaseLevel( $databaseLevel=NULL ) {
    if ( !is_null($databaseLevel) ) {
      $databaseLevel = $this->limit($databaseLevel);
      if ( $this->databaseLevel != $databaseLevel ) {
        $this->databaseLevel = $databaseLevel;
        if ( $this->databaseLevel > self::NOLOG ) {
          if ( (include_once 'database.php') === FALSE ) {
            $this->databaseLevel = self::NOLOG;
            Warning( 'Unable to write log entries to DB, database.php not found' );
          }
        }
      }
    }
    return $this->databaseLevel;
  }

  public function fileLevel( $fileLevel ) {
    if ( isset($fileLevel) ) {
      $fileLevel = $this->limit($fileLevel);
      if ( $this->fileLevel != $fileLevel ) {
        if ( $this->fileLevel > self::NOLOG )
          $this->closeFile();
        $this->fileLevel = $fileLevel;
        if ( $this->fileLevel > self::NOLOG )
          $this->openFile();
      }
    }
    return $this->fileLevel;
  }

  public function weblogLevel( $weblogLevel ) {
    if ( isset($weblogLevel) ) {
      $weblogLevel = $this->limit($weblogLevel);
      if ( $this->weblogLevel != $weblogLevel ) {
        if ( $weblogLevel > self::NOLOG && $this->weblogLevel <= self::NOLOG ) {
          $this->savedLogErrors = ini_set( 'log_errors', true );
        } elseif ( $weblogLevel <= self::NOLOG && $this->weblogLevel > self::NOLOG ) {
          ini_set( 'log_errors', $this->savedLogErrors );
        }
        $this->weblogLevel = $weblogLevel;
      }
    }
    return $this->weblogLevel;
  }

  public function syslogLevel( $syslogLevel ) {
    if ( isset($syslogLevel) ) {
      $syslogLevel = $this->limit($syslogLevel);
      if ( $this->syslogLevel != $syslogLevel ) {
        if ( $this->syslogLevel > self::NOLOG )
          $this->closeSyslog();
        $this->syslogLevel = $syslogLevel;
        if ( $this->syslogLevel > self::NOLOG )
          $this->openSyslog();
      }
    }
    return $this->syslogLevel;
  }

  private function openSyslog() {
    openlog($this->id, LOG_PID|LOG_NDELAY, LOG_LOCAL1);
  }

  private function closeSyslog() {
    closelog();
  }

  private function logFile($logFile) {
    if ( preg_match('/^(.+)\+$/', $logFile, $matches) ) {
      $this->logFile = $matches[1].'.'.getmypid();
    } else {
      $this->logFile = $logFile;
    }
  }

  private function openFile() {
    if ( !$this->useErrorLog ) {
      if ( $this->logFd = fopen($this->logFile, 'a+') ) {
        if ( strnatcmp(phpversion(), '5.2.0') >= 0 ) {
          $error = error_get_last();
          trigger_error("Can't open log file '$logFile': ".$error['message'].' @ '.$error['file'].'/'.$error['line'], E_USER_ERROR);
        }
        $this->fileLevel = self::NOLOG;
      }
    }
  }

  private function closeFile() {
    if ( $this->logFd )
      fclose($this->logFd);
  }

  public function logPrint( $level, $string, $file=NULL, $line=NULL ) {
    if ( $level > $this->effectiveLevel ) {
      return;
    }

    $string = preg_replace('/[\r\n]+$/', '', $string);
    $code = self::$codes[$level];

    $time = gettimeofday();
    $message = sprintf('%s.%06d %s[%d].%s [%s] [%s]',
      strftime('%x %H:%M:%S', $time['sec']), $time['usec'],
      $this->id, getmypid(), $code, $_SERVER['REMOTE_ADDR'], $string);

    if ( is_null($file) ) {
      if ( $this->useErrorLog || ($this->databaseLevel > self::NOLOG) ) {
        $backTrace = debug_backtrace();
        $file = $backTrace[1]['file'];
        $line = $backTrace[1]['line'];
        if ( $this->hasTerm )
          $rootPath = getcwd();
        else
          $rootPath = $_SERVER['DOCUMENT_ROOT'];
        $file = preg_replace('/^'.addcslashes($rootPath,'/').'\/?/', '', $file);
      }
    }

    if ( $this->useErrorLog ) {
      $message .= ' at '.$file.' line '.$line;
    } else {
      $message = $message;
    }

    if ( $level <= $this->termLevel ) {
      if ( $this->hasTerm )
        print($message."\n");
      else
        print(preg_replace("/\n/", '<br/>', htmlspecialchars($message)).'<br/>');
    }

    if ( $level <= $this->fileLevel ) {
      if ( $this->useErrorLog ) {
        if ( !error_log($message."\n", 3, $this->logFile) ) {
          if ( strnatcmp(phpversion(), '5.2.0') >= 0 ) {
            $error = error_get_last();
            trigger_error("Can't write to log file '".$this->logFile."': ".$error['message'].' @ '.$error['file'].'/'.$error['line'], E_USER_ERROR);
          }
        }
      } else if ( $this->logFd ) {
        fprintf($this->logFd, $message."\n");
      }
    }

    $message = $code.' ['.$string.']';
    if ( $level <= $this->syslogLevel )
      syslog( self::$syslogPriorities[$level], $message );

    if ( $level <= $this->databaseLevel ) {
      try {
        global $dbConn;
        $sql = 'INSERT INTO `Logs` ( `TimeKey`, `Component`, `ServerId`, `Pid`, `Level`, `Code`, `Message`, `File`, `Line` ) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ? )';
        $stmt = $dbConn->prepare($sql);
        $result = $stmt->execute(array(sprintf('%d.%06d', $time['sec'], $time['usec']), $this->id,
          (defined('ZM_SERVER_ID') ? ZM_SERVER_ID : null), getmypid(), $level, $code, $string, $file, $line));
      } catch(PDOException $ex) {
        $this->databaseLevel = self::NOLOG;
        Error("Can't write log entry '$sql': ". $ex->getMessage());
      }
    }
    // This has to be last as trigger_error can be fatal
    if ( $level <= $this->weblogLevel ) {
      if ( $this->useErrorLog ) {
        error_log($message, 0);
      } else {
        trigger_error($message, self::$phpErrorLevels[$level]);
      }
    }
  }
};

function logInit( $options=array() ) {
  $logger = Logger::fetch();
  $logger->initialise( $options );
  set_error_handler( 'ZM\ErrorHandler' );
}

function logToDatabase( $level=NULL ) {
  return( Logger::fetch()->databaseLevel( $level ) );
}

function Mark( $level=Logger::DEBUG, $tag='Mark' ) {
  Logger::fetch()->logPrint( $level, $tag );
}

function Dump( &$var, $label='VAR' ) {
  ob_start();
  print( $label.' => ' );
  print_r( $var );
  Logger::fetch()->logPrint( Logger::DEBUG, ob_get_clean() );
}

function Info( $string ) {
  Logger::fetch()->logPrint( Logger::INFO, $string );
}

function Warning( $string ) {
  Logger::fetch()->logPrint( Logger::WARNING, $string );
}

function Error( $string ) {
  Logger::fetch()->logPrint( Logger::ERROR, $string );
}

function Fatal( $string ) {
  Logger::fetch()->logPrint( Logger::FATAL, $string );
  if (Logger::fetch()->debugOn()) {
    echo(htmlentities($string));
  }
  exit(1);
}

function Panic( $string ) {
  if ( true ) {
    // Use builtin function
    ob_start();
    debug_print_backtrace();
    $backtrace = "\n".ob_get_clean();
  } else {
    // Roll your own
    $backtrace = '';
    $frames = debug_backtrace();
    for ( $i = 0; $i < count($frames); $i++ ) {
      $frame = $frames[$i];
      $backtrace .= sprintf( "\n#%d %s() at %s/%d", $i, $frame['function'], $frame['file'], $frame['line'] );
    }
  }
  Logger::fetch()->logPrint( Logger::PANIC, $string.$backtrace );
  if (Logger::fetch()->debugOn()) {
    echo $string;
  }
  exit(1);
}

function ErrorHandler( $error, $string, $file, $line ) {
  if ( ! (error_reporting() & $error) ) {
    // This error code is not included in error_reporting
    return false;
  }

  switch ( $error ) {
  case E_USER_ERROR:
    Logger::fetch()->logPrint( Logger::FATAL, $string, $file, $line );
    break;

  case E_USER_WARNING:
    Logger::fetch()->logPrint( Logger::ERROR, $string, $file, $line );
    break;

  case E_USER_NOTICE:
    Logger::fetch()->logPrint( Logger::WARNING, $string, $file, $line );
    break;

  default:
    Panic( "Unknown error type: [$error] $string" );
    break;
  }
  return true;
}

?>
