<?php
// ZM session start function support timestamp management
function zm_session_start() {
  // Make sure use_strict_mode is enabled.
  // use_strict_mode is mandatory for security reasons.
  ini_set('session.use_strict_mode', 1);

  $currentCookieParams = session_get_cookie_params(); 
  $currentCookieParams['lifetime'] = ZM_COOKIE_LIFETIME;

  session_set_cookie_params( 
    $currentCookieParams['lifetime'],
    $currentCookieParams['path'],
    $currentCookieParams['domain'],
    $currentCookieParams['secure'],
    true
  );

  ini_set('session.name', 'ZMSESSID');
  ZM\Logger::Debug('Setting cookie parameters to lifetime('.$currentCookieParams['lifetime'].') path('.$currentCookieParams['path'].') domain ('.$currentCookieParams['domain'].') secure('.$currentCookieParams['secure'].') httpOnly(1) name:'.session_name());

  session_start();
  $_SESSION['remoteAddr'] = $_SERVER['REMOTE_ADDR']; // To help prevent session hijacking
  $now = time();
  // Do not allow to use expired session ID
  if ( !empty($_SESSION['last_time']) && ($_SESSION['last_time'] < ($now - 180)) ) {
    ZM\Info('Destroying session due to timeout. ');
    session_destroy();
    session_start();
  } else if ( !empty($_SESSION['generated_at']) ) {
    if ( $_SESSION['generated_at']<($now-(ZM_COOKIE_LIFETIME/2)) ) {
      ZM\Logger::Debug("Regenerating session because generated_at " . $_SESSION['generated_at'] . ' < ' . $now . '-'.ZM_COOKIE_LIFETIME.'/2 = '.($now-ZM_COOKIE_LIFETIME/2));
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
?>
