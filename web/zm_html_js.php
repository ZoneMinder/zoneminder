<?php
//
// ZoneMinder base javascript file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

//
// This file should only contain JavaScript that needs preprocessing by php.
// Static JavaScript should go in zm_html.js
//

?>
<script type="text/javascript" src="mootools.v1.11.js"></script>
<script type="text/javascript" src="zm_html.js"></script>
<script type="text/javascript">
function eventWindow( eventId, eventFilter )
{
    var windowUrl = '<?= $PHP_SELF ?>?view=event&eid='+eventId;
    if ( eventFilter )
        windowUrl += eventFilter;
    var windowName = 'zmEvent';
    var windowOptions = 'resizable,status=no,width=<?= $monitor['Width']+$jws['event']['w'] ?>,height=<?= $monitor['Height']+$jws['event']['h'] ?>';
	var windowId = window.open( windowUrl, windowName, windowOptions );
}

function framesWindow( eventId, eventFilter )
{
    var windowUrl = '<?= $PHP_SELF ?>?view=frames&eid='+eventId;
    if ( eventFilter )
        windowUrl += eventFilter;
    var windowName = 'zmFrames';
    var windowOptions = 'resizable,status=no,width=<?= $monitor['Width']+$jws['frames']['w'] ?>,height=<?= $monitor['Height']+$jws['frames']['h'] ?>';
	var windowId = window.open( windowUrl, windowName, windowOptions );
}

function frameWindow( eventId, frameId, eventFilter )
{
    var windowUrl = '<?= $PHP_SELF ?>?view=frame&eid='+eventId+'&fid='+frameId;
    if ( eventFilter )
        windowUrl += eventFilter;
    var windowName = 'zmFrame';
    var windowOptions = 'resizable,status=no,width=<?= $monitor['Width']+$jws['image']['w'] ?>,height=<?= $monitor['Height']+$jws['image']['h'] ?>';
	var windowId = window.open( windowUrl, windowName, windowOptions );
}
</script>
