<?php
//
// ZoneMinder HLS manifest server
// Serves the pre-built m3u8 manifest for an event's byte-range HLS playback.
//

if (!canView('Events')) {
  $view = 'error';
  return;
}

ob_end_clean();
require_once('includes/Event.php');

if (empty($_REQUEST['eid'])) {
  header('HTTP/1.1 400 Bad Request');
  die('Missing eid parameter');
}

$Event = new ZM\Event($_REQUEST['eid']);
if (!$Event->Id()) {
  header('HTTP/1.1 404 Not Found');
  die('Event not found');
}

$m3u8_path = $Event->Path() . '/index.m3u8';

if (!file_exists($m3u8_path)) {
  header('HTTP/1.1 404 Not Found');
  die('HLS manifest not available for this event');
}

// Don't serve an m3u8 with no fragments — the event may have just started
$m3u8_content_check = file_get_contents($m3u8_path);
if (strpos($m3u8_content_check, '#EXTINF:') === false) {
  header('HTTP/1.1 404 Not Found');
  die('HLS manifest has no fragments yet');
}

// Build auth query string for segment URLs
$auth_query = '';
if (ZM_OPT_USE_AUTH) {
  if (ZM_AUTH_RELAY == 'hashed') {
    $auth_query = '&auth=' . generateAuthHash(ZM_AUTH_HASH_IPS);
  } else if (ZM_AUTH_RELAY == 'plain') {
    $auth_query = '&user=' . $_SESSION['username'] . '&pass=' . $_SESSION['password'];
  } else if (ZM_AUTH_RELAY == 'none') {
    $auth_query = '&user=' . $_SESSION['username'];
  }
}

// Read the m3u8 and inject auth tokens into segment URLs
$content = file_get_contents($m3u8_path);

// The m3u8 has relative URLs like "index.php?view=view_video&eid=123"
// We need to make them absolute with the server path and add auth
$Server = $Event->Server();
$base_url = $Server->PathToIndex();

// Replace bare URLs with full paths including auth
$content = preg_replace(
  '/^(index\.php\?.+)$/m',
  $base_url . '?$1' . $auth_query,
  $content
);

// Also fix the EXT-X-MAP URI
$content = preg_replace(
  '/URI="(index\.php\?[^"]+)"/m',
  'URI="' . $base_url . '?$1' . $auth_query . '"',
  $content
);

header('Content-Type: application/vnd.apple.mpegurl');
header('Cache-Control: no-cache');
echo $content;
exit;
