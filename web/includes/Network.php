<?php
//
// ZoneMinder network address helpers
//
// Kept dependency-free so it can be included from anywhere - session.php pulls
// it in before the rest of the web bootstrap exists - and unit tested
// standalone. Policy decisions that happen to involve addresses do not belong
// here; the auth hash's use of getRemoteAddr() lives in auth.php.
//

// Return the effective client address.
//
// X-Forwarded-For is client-settable, so it is only honoured when the request
// arrives from one of ZM_AUTH_TRUSTED_PROXIES. The header is then walked from
// the right, skipping hops that are themselves trusted proxies, and the first
// untrusted hop is the client. Left-most values are never preferred: a proxy
// appends to whatever the client sent, so they are attacker-chosen. With no
// trusted proxies configured (the default) this is always REMOTE_ADDR.
//
// src/zm_utils.cpp ClientAddress() implements the same rule for zms; the two
// must agree or IP-bound auth hashes fail to validate.
//
// $trustedProxies defaults to the ZM_AUTH_TRUSTED_PROXIES config and is a
// parameter only so the tests can vary it.
//
// ponytail: exact address match only. Add CIDR ranges if proxies on dynamic
// addresses (e.g. container networks) need it.
function getRemoteAddr($trustedProxies=null) {
  if ($trustedProxies === null) {
    $trustedProxies = defined('ZM_AUTH_TRUSTED_PROXIES') ? ZM_AUTH_TRUSTED_PROXIES : '';
  }
  $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
  $trusted = preg_split('/[\s,]+/', $trustedProxies, -1, PREG_SPLIT_NO_EMPTY);
  if (empty($_SERVER['HTTP_X_FORWARDED_FOR']) or !in_array($remoteAddr, $trusted, true)) {
    return $remoteAddr;
  }
  $hops = array_values(array_filter(array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])), 'strlen'));
  for ($i = count($hops) - 1; $i >= 0; $i--) {
    if (!in_array($hops[$i], $trusted, true)) return $hops[$i];
  }
  // Every hop is a trusted proxy; the left-most is the origin.
  return count($hops) ? $hops[0] : $remoteAddr;
}
?>
