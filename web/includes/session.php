<?php

// Wrapper around setcookie that auto-sets samesite, and deals with older versions of php
function zm_setcookie($cookie, $value, $options=array()) {
  if (!isset($options['expires'])) {
    $options['expires'] = time()+3600*24*30*12*10; // 10 years?!
  }
  if (!isset($options['samesite'])) {
    $options['samesite'] = 'Strict';
  }

  if (version_compare(phpversion(), '7.3.0', '>=')) {
    setcookie($cookie, $value, $options);
  } else {
    setcookie($cookie, $value, $options['expires'], '/; samesite=strict');
  }
}

// ZM session start function support timestamp management
function zm_session_start() {

  if ( ini_get('session.name') != 'ZMSESSID' ) {
    // Make sure use_strict_mode is enabled.
    // use_strict_mode is mandatory for security reasons.
    ini_set('session.use_strict_mode', 1);

    $currentCookieParams = session_get_cookie_params(); 
    $currentCookieParams['lifetime'] = ZM_COOKIE_LIFETIME;
    $currentCookieParams['httponly'] = true;
    if ( version_compare(phpversion(), '7.3.0', '<') ) {
      session_set_cookie_params(
        $currentCookieParams['lifetime'],
        $currentCookieParams['path'],
        $currentCookieParams['domain'],
        $currentCookieParams['secure'],
        $currentCookieParams['httponly']
      );
    } else {
      # samesite was introduced in 7.3.0
      $currentCookieParams['samesite'] = 'Strict';
      session_set_cookie_params($currentCookieParams);
    }

    ini_set('session.name', 'ZMSESSID');
    ZM\Debug('Setting cookie parameters to '.print_r($currentCookieParams, true));
  }
  session_start();
  $_SESSION['remoteAddr'] = $_SERVER['REMOTE_ADDR']; // To help prevent session hijacking
  $now = time();
  // Do not allow to use expired session ID
  if ( !empty($_SESSION['last_time']) && ($_SESSION['last_time'] < ($now - 180)) ) {
    ZM\Info('Destroying session due to timeout.');
    session_destroy();
    session_start();
  } else if ( !empty($_SESSION['generated_at']) ) {
    if ( $_SESSION['generated_at']<($now-(ZM_COOKIE_LIFETIME/2)) ) {
      ZM\Debug('Regenerating session because generated_at ' . $_SESSION['generated_at'] . ' < ' . $now . '-'.ZM_COOKIE_LIFETIME.'/2 = '.($now-ZM_COOKIE_LIFETIME/2));
      zm_session_regenerate_id();
    }
  }
} // function zm_session_start()

// session regenerate id function
// Assumes that zm_session_start has been called previously
function zm_session_regenerate_id() {
  if ( session_status() != PHP_SESSION_ACTIVE ) {
    session_start();
  }

  // Set deleted timestamp. Session data must not be deleted immediately for reasons.
  $_SESSION['last_time'] = time();
  // Finish session
  session_write_close();

  session_start();
  session_regenerate_id();
  unset($_SESSION['last_time']);
  $_SESSION['generated_at'] = time();
} // function zm_session_regenerate_id()

function is_session_started() {
  if ( php_sapi_name() !== 'cli' ) {
    if ( version_compare(phpversion(), '5.4.0', '>=') ) {
      return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
    } else {
      return session_id() === '' ? FALSE : TRUE;
    }
  } else {
    Warning("php_sapi_name === 'cli'");
  }
  return FALSE;
} // function is_session_started()

function zm_session_clear() {
  session_start();
  $_SESSION = array();
  if ( ini_get('session.use_cookies') ) {
    $p = session_get_cookie_params();
    # Update the cookie to expire in the past.
    setcookie(session_name(), '', time() - 31536000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_unset();
  session_destroy();
  session_write_close();
  session_start();
} // function zm_session_clear()


class Session {
  private $db;
  public function __construct() {
    global $dbConn;
    $this->db = $dbConn;

    // Set handler to overide SESSION
    session_set_save_handler(
      array($this, '_open'),
      array($this, '_close'),
      array($this, '_read'),
      array($this, '_write'),
      array($this, '_destroy'),
      array($this, '_gc')
    );

    // Start the session
    //zm_session_start();
  }
  public function _open() {
    return $this->db ? true : false;
  }
  public function _close(){
    // The example code closed the db connection.. I don't think we care to.
    return true;
  }
  public function _read($id){
    $sth = $this->db->prepare('SELECT data FROM Sessions WHERE id = :id');
    $sth->bindParam(':id', $id, PDO::PARAM_STR, 32);

    if ( $sth->execute() and ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) ) {
      return $row['data'];
    }
    // Return an empty string
    return '';
  }
  public function _write($id, $data){
    // Create time stamp
    $access = time();

    $sth = $this->db->prepare('REPLACE INTO Sessions VALUES (:id, :access, :data)');

    $sth->bindParam(':id', $id, PDO::PARAM_STR, 32);
    $sth->bindParam(':access', $access, PDO::PARAM_INT);
    $sth->bindParam(':data', $data);

    return $sth->execute() ? true : false;
  }
  public function _destroy($id) {
    $sth = $this->db->prepare('DELETE FROM Sessions WHERE Id = :id');
    $sth->bindParam(':id', $id, PDO::PARAM_STR, 32);
    return $sth->execute() ? true : false;
  }
  public function _gc($max) {
    // Calculate what is to be deemed old
    $now = time();
    $old = $now - $max;
    ZM\Debug('doing session gc ' . $now . '-' . $max. '='.$old);
    $sth = $this->db->prepare('DELETE FROM Sessions WHERE access < :old');
    $sth->bindParam(':old', $old, PDO::PARAM_INT);
    return $sth->execute() ? true : false;
  }
} # end class Session

$session = new Session;
?>
