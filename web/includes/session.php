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

?>
