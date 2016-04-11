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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

require_once('includes/Storage.php');
require_once('includes/Event.php');


$Storage = NULL;
$errorText = false;
if ( ! empty($_REQUEST['eid'] ) ) {
    $Event = new Event( $_REQUEST['eid'] );
    $Storage = $Event->Storage();
    $path = $Event->Relative_Path().'/'.$Event->DefaultVideo();
	Error("Path: $path");
} else {
    $errorText = "No video path";
}

if ( $errorText )
    Error( $errorText );
else{
# FIXME guess it from the video file
	header( 'Content-type: video/mp4' );
    if ( ! readfile( $Storage->Path().'/'.$path ) ) {
		Error("No bytes read from ". $Storage->Path() . '/'.$path );
	} else {
		Error("Success sending " . $Storage->Path().'/'.$path );
    }
}
?>
