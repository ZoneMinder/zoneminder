<?php
// ZM session start function support timestamp management
function zm_session_start() {
  // Make sure use_strict_mode is enabled.
  // use_strict_mode is mandatory for security reasons.
  ini_set('session.use_strict_mode', 1);

  $currentCookieParams = session_get_cookie_params(); 
  $currentCookieParams['lifetime'] = ZM_COOKIE_LIFETIME;

  Logger::Debug('Setting cookie parameters to lifetime('.$currentCookieParams['lifetime'].') path('.$currentCookieParams['path'].') domain ('.$currentCookieParams['domain'].') secure('.$currentCookieParams['secure'].') httpOnly(1)');
  session_set_cookie_params( 
    $currentCookieParams['lifetime'],
    $currentCookieParams['path'],
    $currentCookieParams['domain'],
    $currentCookieParams['secure'],
    true
  );

  ini_set('session.name', 'ZMSESSID');

  session_start();
  // Do not allow to use too old session ID
  if (!empty($_SESSION['last_time']) && $_SESSION['last_time'] < time() - 180) {
    session_destroy();
    session_start();
  }
}

// My session regenerate id function
function zm_session_regenerate_id() {
  // Call session_create_id() while session is active to 
  // make sure collision free.
  if ( session_status() != PHP_SESSION_ACTIVE ) {
    session_start();
  }
  // WARNING: Never use confidential strings for prefix!
  $newid = session_create_id();
  // Set deleted timestamp. Session data must not be deleted immediately for reasons.
  $_SESSION['last_time'] = time();
  // Finish session
  session_commit();
  // Make sure to accept user defined session ID
  // NOTE: You must enable use_strict_mode for normal operations.
  ini_set('session.use_strict_mode', 0);
  // Set new custome session ID
  session_id($newid);
  // Start with custome session ID
  session_start();
}

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
}

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
}
?>
