<?php
//
// ZoneMinder web video view file
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

// Calling sequence:   ... /zm/index.php?view=video&event_id=123
//
//     event_id is the id of the event to view
//
//      Does not support scaling at this time.
//

if ( !canView('Events') ) {
  $view = 'error';
  return;
}

# in index.php we do ob_end_start but there can be no output before view_video and we often don't have enough ram to buffer the content.
ob_end_clean();
require_once('includes/Event.php');
require_once('includes/HttpRange.php');

$errorText = false;
$path = '';
$mode = (!empty($_REQUEST['mode'])) ? $_REQUEST['mode'] : '';

$Event = null;

$event_id = !empty($_REQUEST['eid']) ? $_REQUEST['eid']
  : (!empty($_REQUEST['event_id']) ? $_REQUEST['event_id'] : null);

if ($event_id !== null) {
  $Event = new ZM\Event($event_id);
  // Validate the event actually loaded — the constructor silently produces an
  // empty object for unknown ids. Without this check view_video previously
  // returned HTTP 200 with an empty body for nonexistent ids.
  if (!$Event->Id()) {
    header('HTTP/1.0 404 Not Found');
    ZM\Error('Event '.$event_id.' Not found');
    die();
  }
  // Per-event ACL: coarse canView('Events') isn't enough — the user may be
  // denied access to the monitor that owns this event (GHSA-vj5r-pc2v-gfwv).
  // 404 matches the missing-event response so the id isn't leaked.
  if (!$Event->canView()) {
    header('HTTP/1.0 404 Not Found');
    ZM\Warning('Event '.$event_id.' access denied');
    die();
  }
  if (!empty($_REQUEST['file'])) {
    $path = $Event->Path().'/'.basename($_REQUEST['file']);
  } else {
    $path = $Event->Path().'/'.$Event->DefaultVideo();
  }
} else {
  $errorText = 'No video path';
}

// If DefaultVideo is an m3u8 manifest and no explicit file was requested,
// find the actual mp4 video file in the event directory.
if ($Event && !$errorText) {
  $shouldUseMp4Fallback = !@is_file($path);
  if ($mode == "mp4") {
    $shouldUseMp4Fallback = ($Event->DefaultVideo() === 'index.m3u8' || !@is_file($path));
  }
  if ($shouldUseMp4Fallback) {
    $dir = $Event->Path();
    // Look for the final renamed mp4 first, then incomplete
    $candidates = glob($dir.'/'.$Event->Id().'-video.*.mp4');
    if (!$candidates) $candidates = glob($dir.'/incomplete.*.mp4');
    if ($candidates) {
      $path = $candidates[0];
    }
  }
}

if ( $errorText ) {
  ZM\Error($errorText);
  header('HTTP/1.0 404 Not Found');
  die();
} 

if ( ! ($fh = @fopen($path, 'rb') ) ) {
  ZM\Error('Can\'t open video at '.$path);
  header('HTTP/1.0 404 Not Found');
  die();
}
// Always derive the filename from the resolved $path: after the m3u8 fallback
// above, $path can point at an mp4 even when DefaultVideo is 'index.m3u8', so
// reporting DefaultVideo would advertise a manifest while serving mp4 bytes.
$filename = basename($path);

$size = filesize($path);
if ($size === false) {
  ZM\Error('Unable to determine the size of '.$path);
  header('HTTP/1.0 500 Internal Server Error');
  fclose($fh);
  die();
}
$begin = 0;
$end = $size - 1;
$partial = false;

if (isset($_SERVER['HTTP_RANGE'])) {
  ZM\Debug('Using Range '.$_SERVER['HTTP_RANGE']);
  $range = parseHttpRange($_SERVER['HTTP_RANGE'], $size);

  if ($range === null) {
    // Asked for bytes this file does not have. 416 with the real length lets
    // the client correct itself; a 206 naming a range that does not exist
    // leaves it waiting on a body that cannot arrive.
    ZM\Debug('Unsatisfiable Range '.$_SERVER['HTTP_RANGE'].' for size '.$size);
    header('HTTP/1.0 416 Range Not Satisfiable');
    header('Accept-Ranges: bytes');
    header('Content-Range: bytes */'.$size);
    header('Content-Length: 0');
    fclose($fh);
    exit();
  } else if ($range !== false) {
    list($begin, $end) = $range;
    $partial = true;
    ZM\Debug("Using Range $begin-$end of $size");
  }
} # end if HTTP_RANGE

// Taken from the range being served rather than from the one requested, so
// Content-Length always matches the bytes the loop below writes.
$length = $end - $begin + 1;
if ($length < 0) $length = 0;

$path_info = pathinfo($path ? $path : (($Event) ? $Event->DefaultVideo() : ''));
header('Content-type: video/'.$path_info['extension']);
header('Accept-Ranges: bytes');
header('Content-Length: '.$length);
# This is so that Save Image As give a useful filename
if ($Event) {
  header('Content-Disposition: inline; filename="' . $filename . '"');
} else {
  header('Content-Disposition: inline;');
}
if ($partial) {
  header('HTTP/1.0 206 Partial Content');
  header("Content-Range: bytes $begin-$end/$size");
  header("Content-Transfer-Encoding: binary\n");
  header('Connection: close');
} else {
  header('HTTP/1.0 200 OK');
}

// Apparently without these we get a few extra bytes of output at the end...
flush();
if ($begin) fseek($fh, $begin, 0);

while (($length > 0) && (!feof($fh)) && (connection_status() == 0)) {
  $data = fread($fh, min(1024*16, $length));
  // Short or failed reads used to still count against $length, so a file that
  // shrank or could not be read sent fewer bytes than Content-Length promised
  // and the client saw a truncated response rather than an error.
  if (($data === false) || ($data === '')) break;
  echo $data;
  $length -= strlen($data);
  flush();
}

fclose($fh);
exit();
