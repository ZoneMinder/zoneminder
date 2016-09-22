<?php
//
// ZoneMinder web video view file, $Date: 2008-09-29 14:15:13 +0100 (Mon, 29 Sep 2008) $, $Revision: 2640 $
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

// Calling sequence:   ... /zm/index.php?view=video&event_id=123
//
//     event_id is the id of the event to view
//
//      Does not support scaling at this time.
//

if ( !canView( 'Events' ) ) {
  $view = "error";
  return;
}

require_once('includes/Event.php');

$errorText = false;
$path = '';

if ( ! empty($_REQUEST['eid'] ) ) {
  $Event = new Event( $_REQUEST['eid'] );
  $path = $Event->Path().'/'.$Event->DefaultVideo();
	Debug("Path: $path");
} else {
  $errorText = "No video path";
}

if ( $errorText ) {
  Error( $errorText );
  header ("HTTP/1.0 404 Not Found");
  die();
} 

$size = filesize($path);

$fh = @fopen($path,'rb');
if ( ! $fh ) {
  header ("HTTP/1.0 404 Not Found");
  die();
}

$begin = 0;
$end = $size;

if ( isset( $_SERVER['HTTP_RANGE'] ) ) {
  Debug("Using Range " . $_SERVER['HTTP_RANGE'] );
  if ( preg_match( '/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches) ) {
    $begin = intval( $matches[1] );
    if ( ! empty( $matches[2]) ) {
      $end = intval( $matches[2] );
    }
    Debug("Using Range $begin $end size: $size");
  }
} # end if HTTP_RANGE

if ( $begin > 0 || $end < $size ) {
  header('HTTP/1.0 206 Partial Content');
} else {
  header('HTTP/1.0 200 OK');
}

header('Content-type: video/mp4');
header('Accept-Ranges: bytes');
header('Content-Length:'.($end-$begin));
header("Content-Disposition: inline;");
header("Content-Range: bytes $begin-$end/$size");
header("Content-Transfer-Encoding: binary\n");
header('Connection: close');

// Apparently without these we get a few extra bytes of output at the end...
ob_clean();
flush();

$cur = $begin;
fseek( $fh, $begin, 0 );

while( ! feof( $fh ) && $cur < $end && ( connection_status() == 0 ) ) {
  #Error("Sending $cur");
  print fread( $fh, min( 1024*16, $end - $cur ) );
  $cur += 1024*16;
  usleep(100);
}
