//
// ZoneMinder logger javascript file, $Date: 2011-05-27 22:24:17 +0100 (Fri, 27 May 2011) $, $Revision: 3374 $
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

if ( !window.console )
{
    window.console =
    {
        init:function() {},
        log:function() {},
        debug:function() {},
        info:function() {},
        warn:function() {},
        error:function() {}
    };
}
if ( !console.debug )//IE8 has console but doesn't have console.debug so lets alias it.
    console.debug = console.log;

var reportLogs = true;

var debugParms;
var debugReq;

function logReport( level, message, file, line )
{
    if ( !reportLogs )
        return;

    if ( typeof(MooTools) == "undefined" )
        return;

    if ( arguments && arguments.callee && arguments.callee.caller && arguments.callee.caller.name )
        message += ' - '+arguments.callee.caller.caller.name+'()'; 

    if ( !debugReq )
    {
        debugParms = "view=request&request=log&task=create&browser[name]="+Browser.name+"&browser[version]="+Browser.version+"&browser[platform]="+Browser.Platform.name;
        debugReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, link: 'chain' } );
    }
    var requestParms = debugParms;
    requestParms += "&level="+level+"&message="+encodeURIComponent(message);
    if ( file )
        requestParms += "&file="+file;
    else
        requestParms += "&file="+location.search;
    if ( line )
        requestParms += "&line="+line;
    debugReq.send( requestParms );
}

function Panic( message )
{
    console.error( message );
    logReport( "PNC", message );
    alert( "PANIC: "+message );
}

function Fatal( message )
{
    console.error( message );
    logReport( "FAT", message );
    alert( "FATAL: "+message );
}

function Error( message )
{
    console.error( message );
    logReport( "ERR", message );
}

function Warning( message )
{
    console.warn( message );
    logReport( "WAR", message );
}

function Info( message )
{
    console.info( message );
    logReport( "INF", message );
}

function Debug( message )
{
    console.debug( message );
    //logReport( "DBG", message );
}

function Dump( value, label )
{
    if ( label )
        console.debug( label+" => " );
    console.debug( value );
}

window.onerror =
    function( message, url, line )
    {
        logReport( "ERR", message, url, line );
    }
