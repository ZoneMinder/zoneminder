<?php
//
// ZoneMinder network address helpers
//
// Kept dependency-free so it can be included from anywhere and unit tested
// standalone. corsAllowedOrigin() lives here because functions.php cannot be
// loaded without the web bootstrap, so its tests could not reach it there.
//

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
