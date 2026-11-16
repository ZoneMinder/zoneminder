<?php
//
// ZoneMinder network address helpers
//
// Kept dependency-free so it can be included from anywhere - session.php pulls
// it in before the rest of the web bootstrap exists - and unit tested
// standalone. Policy decisions that happen to involve addresses mostly live
// with their callers (the auth hash's use of getRemoteAddr() is in auth.php).
// corsAllowedOrigin() is the exception: the web UI's CORSHeaders() and the
// API's AppController share it, and functions.php cannot be loaded without
// the web bootstrap, so its tests could not reach it there.
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
// Return the request's Origin if it may make credentialed cross-origin calls,
// else ''. Allowed are pages on a server listed in the Servers table, matched
// by its Hostname or Name, or, on a single server using streaming ports
// (ZM_MIN_STREAMING_PORT), pages on this same host on another port. The
// Origin's host must equal one of those names exactly; any port is accepted.
//
// The caller sends Access-Control-Allow-Origin with this value and
// Access-Control-Allow-Credentials: true, so a match lets that page read API
// and web responses with the viewer's session. A prefix match would let
// https://zm.example.com.evil.net read as zm.example.com.
//
// $servers and $minStreamingPort default to the global $Servers and the
// ZM_MIN_STREAMING_PORT config and are parameters only so the tests can vary them.
function corsAllowedOrigin($servers=null, $minStreamingPort=null) {
  if ($servers === null) {
    global $Servers;
    $servers = isset($Servers) ? $Servers : array();
  }
  if ($minStreamingPort === null) {
    $minStreamingPort = defined('ZM_MIN_STREAMING_PORT') ? ZM_MIN_STREAMING_PORT : 0;
  }
  $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
  // scheme://host[:port] and nothing else, which is what browsers send.
  if (!preg_match('#^https?://[^/?\#@\\\\]+$#i', $origin)) return '';
  $host = parse_url($origin, PHP_URL_HOST);
  if (!$host) return '';

  $allowed = array();
  if (count($servers)) {
    foreach ($servers as $Server) {
      $allowed[] = $Server->Hostname();
      $allowed[] = $Server->Name();
    }
  } else if ($minStreamingPort and isset($_SERVER['HTTP_HOST'])) {
    $allowed[] = parse_url('http://'.$_SERVER['HTTP_HOST'], PHP_URL_HOST);
  }
  foreach ($allowed as $name) {
    if ($name and !strcasecmp($host, $name)) return $origin;
  }
  return '';
}
?>
