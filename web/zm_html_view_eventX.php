<?php
//
// ZoneMinder web event view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

if ( $user['MonitorIds'] )
{
    $mid_sql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
}
else
{
    $mid_sql = '';
}

$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultRate,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '$eid'$mid_sql";
$event = dbFetchOne( $sql );

if ( !isset( $rate ) )
    $rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );
if ( !isset( $scale ) )
    $scale = reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$panel_sections = 40;
$panel_section_width = (int)ceil(reScale($event['Width'],$scale)/$panel_sections);
$panel_width = ($panel_sections*$panel_section_width-1);

$connkey = generateConnKey();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?= ZM_WEB_TITLE_PREFIX ?> - <?= $zmSlangEvent ?></title>
<link rel="stylesheet" href="<?= basename(__FILE__,".php") ?>.css" type="text/css" media="screen"/>
<?php require_once( 'zm_html_js.php' ) ?>
<script type="text/javascript">

var url = "<?= ZM_BASE_URL.$PHP_SELF ?>";

function setButtonState( element, class )
{
    element.className = class;
    element.disabled = (class != 'inactive');
}

function secsToTime( secs )
{
    var timeString;
    if ( secs < 60 )
        timeString = secs;
    else if ( secs < 60*60 )
    {
        var mins = parseInt(secs/60);
        var secs = secs%60;
        if ( secs < 10 )
            secs = '0'+secs.toString().substr( 0, 4 );
        else
            secs = secs.toString().substr( 0, 5 );
        timeString = mins+":"+secs;
    }
    else
    {
        var hours = parseInt(secs/3600);
        var mins = (secs%3600)/60;
        var secs = secs%60;
        if ( mins < 10 )
            mins = '0'+mins.toString().substr( 0, 4 );
        else
            mins = mins.toString().substr( 0, 5 );
        if ( secs < 10 )
            secs = '0'+secs.toString().substr( 0, 4 );
        else
            secs = secs.toString().substr( 0, 5 );
        timeString = hours+":"+mins+":"+secs;
    }
    return( timeString );
}

function changeScale()
{
    var scale = $('scale').getValue();
    var baseWidth = event.Width;
    var baseHeight = event.Height;
    console.log( "Got new scale: "+scale );
    var newWidth = ( baseWidth * scale ) / <?= SCALE_BASE ?>;
    var newHeight = ( baseHeight * scale ) / <?= SCALE_BASE ?>;

    cmdScale( scale );

    var streamImg = $('imageFeed').getElement('img');
    $(streamImg).setStyles( { width: newWidth, height: newHeight } );
}

var cmdParms = "view=request&request=command&connkey=<?= $connkey ?>";
var cmdTimeoutId = 0;

var status;
var event;
var eventId;
var lastEventId = 0;

function getCmdResponse( resp_text, resp_xml )
{
    if ( cmdTimeoutId )
    {
        window.clearTimeout( cmdTimeoutId );
        cmdTimeoutId = 0;
    }
    if ( !resp_text )
        return;
    var resp_func = new Function( "return "+resp_text );
    var resp_obj = resp_func();
    status = resp_obj.status;

    eventId = status.event;
    if ( eventId != lastEventId )
    {
        evtQuery();
        lastEventId = eventId;
    }
    if ( status.paused == true )
    {
        $('modeValue').setHTML( "Paused" );
        $('rate').addClass( 'hidden' );
        cmdPause( false );
    }
    else 
    {
        $('modeValue').setHTML( "Replay" );
        $('rateValue').setHTML( status.rate );
        $('rate').removeClass( 'hidden' );
        cmdPlay( false );
    }
    $('progressValue').setHTML( secsToTime( parseInt(status.progress) ) );
    $('zoomValue').setHTML( status.zoom );
    if ( status.zoom == "1.0" )
        setButtonState( $('zoomOutBtn'), 'unavail' );
    else
        setButtonState( $('zoomOutBtn'), 'inactive' );

    updateProgressBar();

    var cmdTimeout = <?= ZM_WEB_REFRESH_STATUS ?>;
    cmdTimeoutId = window.setTimeout( 'cmdQuery()', 1000 * cmdTimeout );
}

function cmdPause( action )
{
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PAUSE ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdPlay( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), status.rate==1?'active':'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PLAY ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdFastFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_FASTFWD ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdSlowFwd( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SLOWFWD ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
}

function cmdSlowRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'unavail' );
    setButtonState( $('slowFwdBtn'), 'inactive' );
    setButtonState( $('slowRevBtn'), 'active' );
    setButtonState( $('fastRevBtn'), 'unavail' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SLOWREV ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
    setButtonState( $('pauseBtn'), 'active' );
    setButtonState( $('slowRevBtn'), 'inactive' );
}

function cmdFastRev( action )
{
    setButtonState( $('pauseBtn'), 'inactive' );
    setButtonState( $('playBtn'), 'inactive' );
    setButtonState( $('fastFwdBtn'), 'inactive' );
    setButtonState( $('slowFwdBtn'), 'unavail' );
    setButtonState( $('slowRevBtn'), 'unavail' );
    setButtonState( $('fastRevBtn'), 'inactive' );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_FASTREV ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdPrev( action )
{
    cmdPlay( false );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PREV ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdNext( action )
{
    cmdPlay( false );
    if ( action )
    {
        var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_NEXT ?>", onComplete: getCmdResponse } );
        cmdReq.request();
    }
}

function cmdZoomIn( x, y )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_ZOOMIN ?>&x="+x+"&y="+y, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdZoomOut()
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_ZOOMOUT ?>", onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdScale( scale )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SCALE ?>&scale="+scale, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdPan( x, y )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_PAN ?>&x="+x+"&y="+y, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdSeek( offset )
{
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_SEEK ?>&offset="+offset, onComplete: getCmdResponse } );
    cmdReq.request();
}

function cmdQuery()
{       
    var cmdReq = new Ajax( url, { method: 'post', postBody: cmdParms+"&command=<?= CMD_QUERY ?>", onComplete: getCmdResponse } );
    cmdReq.request();
}       

function getEvtResponse( resp_text, resp_xml )
{
    if ( resp_text == 'Ok' )
        return;
    var resp_func = new Function( "return "+resp_text );
    var resp_obj = resp_func();
    console.log( resp_obj );

    event = resp_obj.event;

    $('dataId').setHTML( event.Id );
    if ( event.Notes )
    {
        $('dataCause').setProperty( 'title', event.Notes );
    }
    else
    {
        $('dataCause').setProperty( 'title', '<?= $zmSlangAttrCause ?>' );
    }
    $('dataCause').setHTML( event.Cause );
    $('dataTime').setHTML( event.StartTime );
    $('dataDuration').setHTML( event.Length );
    $('dataFrames').setHTML( event.Frames+"/"+event.AlarmFrames );
    $('dataScore').setHTML( event.TotScore+"/"+event.AvgScore+"/"+event.MaxScore );
    $('eventName').setProperty( 'value', event.Name );

    var link = $('archiveEvent').getElement( 'a' );
    if ( parseInt(event.Archived) )
    {
        $(link).setHTML( '<?= $zmSlangUnarchive ?>' );
        $(link).setProperty( 'href', 'javascript: unarchiveEvent()' );
    }
    else
    {
        $(link).setHTML( '<?= $zmSlangArchive ?>' );
        $(link).setProperty( 'href', 'javascript: archiveEvent()' );
    }
    drawProgressBar();
}

function evtQuery()
{
    var evtParms = "view=request&request=status&entity=event&id="+eventId;
    var evtReq = new Ajax( url, { method: 'post', postBody: evtParms, onComplete: getEvtResponse } );
    evtReq.request();
}

function getActResponse( resp_text, resp_xml )
{
    if ( resp_text == 'Ok' )
        return;
    var resp_func = new Function( "return "+resp_text );
    var resp_obj = resp_func();
    console.log( resp_obj );

    if ( resp_obj.refreshParent )
    {
        window.opener.location.reload();
    }
    evtQuery();
}

function actQuery( action, parms )
{
    var actParms = "view=request&request=event&id="+event.Id+"&action="+action;
    console.log( parms );
    if ( parms != null )
    {
        actParms += "&"+Object.toQueryString( parms );
    }
    console.log( actParms );
    var actReq = new Ajax( url, { method: 'post', postBody: actParms, onComplete: getActResponse } );
    actReq.request();
}

function startRequests()
{
    cmdTimeoutId = window.setTimeout( 'cmdQuery()', 1000 );
}

function deleteEvent()
{
    actQuery( 'delete' );
    cmdNext();
    opener.location.reload(true);
}

function renameEvent()
{
    var newName = $('eventName').getValue();
    actQuery( 'rename', { eventName: newName } );
}

function editEvent()
{
    newWindow( '<?= $PHP_SELF ?>?view=eventdetail&eid='+event.Id, 'zmEventDetail', <?= $jws['eventdetail']['w'] ?>, <?= $jws['eventdetail']['h'] ?> );
}

function exportEvent()
{
    newWindow( '<?= $PHP_SELF ?>?view=export&eid='+event.Id, 'zmExport', <?= $jws['export']['w'] ?>, <?= $jws['export']['h'] ?> );
}

function archiveEvent()
{
    actQuery( 'archive' );
}

function unarchiveEvent()
{
    actQuery( 'unarchive' );
}

function drawProgressBar()
{
    var barWidth = 0;
    $('progressBar').addClass( 'invisible' );
    var cells = $('progressBar').getElements( 'div' );
    var cellWidth = parseInt( event.Width/$$(cells).length );
    $$(cells).forEach(
        function( cell, index )
        {
            $(cell).setStyle( 'left', barWidth );
            $(cell).setStyle( 'width', cellWidth );
            //console.log( "Cell at "+barWidth );
            if ( index == 0 )
                $(cell).setStyle( 'border-left', 0 );
            var offset = parseInt((index*event.Length)/$$(cells).length);
            $(cell).setProperty( 'title', '+'+secsToTime(offset)+'s' );
            $(cell).removeEvent( 'click' );
            $(cell).addEvent( 'click', function(){ cmdSeek( offset ); } );
            $(cell).setProperty( 'title', '+'+secsToTime(offset)+'s' );
            barWidth += $(cell).getCoordinates().width;
        }
    );
    //console.log( "Bar width "+barWidth );
    $('progressBar').setStyle( 'width', barWidth );
    $('progressBar').removeClass( 'invisible' );
}

function updateProgressBar()
{
    if ( event && status )
    {
        var cells = $('progressBar').getElements( 'div' );
        var completeIndex = parseInt((($$(cells).length+1)*status.progress)/event.Length);
        $$(cells).forEach(
            function( cell, index )
            {
                if ( index < completeIndex )
                {
                    if ( !$(cell).hasClass( 'complete' ) )
                    {
                        $(cell).addClass( 'complete' );
                    }
                }
                else
                {
                    if ( $(cell).hasClass( 'complete' ) )
                    {
                        $(cell).removeClass( 'complete' );
                    }
                }
            }
        );
    }
}
</script>
</head>
<body>
  <div id="container">
    <div id="dataBar">
      <table id="dataTable" cellspacing="0">
        <tr>
          <td><span id="dataId" title="<?= $zmSlangId ?>"><?= $event['Id'] ?></span></td>
          <td><span id="dataCause" title="<?= $event['Notes']?htmlentities($event['Notes']):$zmSlangAttrCause ?>"><?= htmlentities($event['Cause']) ?></span></td>
          <td><span id="dataTime" title="<?= $zmSlangTime ?>"><?= strftime( STRF_FMT_DATETIME_SHORT, strtotime($event['StartTime'] ) ) ?></span></td>
          <td><span id="dataDuration" title="<?= $zmSlangDuration ?>"><?= $event['Length'] ?></span>s</td>
          <td><span id="dataFrames" title="<?= $zmSlangAttrFrames."/".$zmSlangAttrAlarmFrames ?>"><?= $event['Frames'] ?>/<?= $event['AlarmFrames'] ?></span></td>
          <td><span id="dataScore" title="<?= $zmSlangAttrTotalScore."/".$zmSlangAttrAvgScore."/".$zmSlangAttrMaxScore ?>"><?= $event['TotScore'] ?>/<?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></span></td>
        </tr>
      </table>
    </div>
    <div id="menuBar1">
      <span><input size="16" id="eventName" name="eventName" value="<?= $event['Name'] ?>"/>&nbsp;&nbsp;<input type="button" value="<?= $zmSlangRename ?>" onclick="renameEvent()"<?php if ( !canEdit( 'Events' ) ) { ?> disabled<?php } ?>/></span>
      <span id="menuControls">
        <span><?= $zmSlangScale ?>: <?= buildSelect( "scale", $scales, "changeScale();" ); ?></span>
      </span>
    </div>
    <div id="menuBar2">
<?php
if ( canEdit( 'Events' ) )
{
?>
      <span id="deleteEvent"><a href="javascript: deleteEvent()"><?= $zmSlangDelete ?></a></span>
      <span id="editEvent"><a href="javascript: editEvent()"><?= $zmSlangEdit ?></a></span>
      <span id="exportEvent"><a href="javascript: exportEvent()"><?= $zmSlangExport ?></a></span>
      <span id="archiveEvent"><a href="javascript: void(0)">&nbsp;</a></span>
<?php
}
if ( ZM_OPT_MPEG != "no" )
{
?>
      <span><a href="javascript: videoEvent()"><?= $zmSlangVideo ?></a></span>
<?php
}
?>
      <span id="closeWindow"><a href="javascript: closeWindow();"><?= $zmSlangClose ?></a></span>
    </div>
    <div id="imageFeed">
<?php
if ( ZM_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $stream_src = getStreamSrc( array( "source=event", "mode=mpeg", "event=".$eid, "frame=".(!empty($fid)?$fid:1), "scale=".$scale, "rate=".$rate, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_REPLAY_FORMAT ) );
    outputVideoStream( $stream_src, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), $event['Name'], ZM_MPEG_LIVE_FORMAT );
}
else
{
    $stream_src = getStreamSrc( array( "source=event", "mode=jpeg", "event=".$eid, "frame=".(!empty($fid)?$fid:1), "scale=".$scale, "rate=".$rate, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
    if ( canStreamNative() )
    {
        outputImageStream( $stream_src, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), $event['Name'] );
    }
    else
    {
        outputHelperStream( $stream_src, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) );
    }
}
?>
    </div>
    <p id="dvrControls">
      <input type="button" value="&lt;+" id="prevBtn" class="inactive" onclick="cmdPrev( true )"/>
      <input type="button" value="&lt;&lt;" id="fastRevBtn" class="inactive" disabled="disabled" onclick="cmdFastRev( true )"/>
      <input type="button" value="&lt;" id="slowRevBtn" class="unavail" disabled="disabled" onclick="cmdSlowRev( true )"/>
      <input type="button" value="||" id="pauseBtn" class="inactive" onclick="cmdPause( true )"/>
      <input type="button" value="|>" id="playBtn" class="active" disabled="disabled" onclick="cmdPlay( true )"/>
      <input type="button" value="&gt;" id="slowFwdBtn" class="unavail" disabled="disabled" onclick="cmdSlowFwd( true )"/>
      <input type="button" value="&gt;&gt;" id="fastFwdBtn" class="inactive" disabled="disabled" onclick="cmdFastFwd( true )"/>
      <input type="button" value="&ndash;" id="zoomOutBtn" class="avail" onclick="cmdZoomOut()"/>
      <input type="button" value="+&gt;" id="nextBtn" class="inactive" onclick="cmdNext( true )"/>
    </p>
    <div id="replayStatus"><span id="mode">Mode: <span id="modeValue">&nbsp;</span></span><span id="rate">&nbsp;&ndash;&nbsp;Rate: <span id="rateValue"></span>x</span><span id="progress">&nbsp;&ndash;&nbsp;Progress: <span id="progressValue"></span>s</span><span id="zoom">&nbsp;&ndash;&nbsp;Zoom: <span id="zoomValue"></span>x</span>
    </div>
    <div id="progressBar" class="invisible">
<?php
        for ( $i = 0; $i < $panel_sections; $i++ )
        {
?>
       <div class="progressBox" id="progressBox<?= $i ?>" title=""></div>
<?php
        }
?>
    </div>
  </div>
<script type="text/javascript">
startRequests();
<?php
if ( canStreamNative() )
{
?>
function handleClick( event )
{
    var target = event.target;
    //console.log( "Target = "+event.target );
    //console.log( "Left = "+$(target).getLeft() );
    var x = event.page.x - $(target).getLeft();
    var y = event.page.y - $(target).getTop();
    
    if ( event.shift )
        cmdPan( x, y );
    else
        cmdZoomIn( x, y );
    //console.log(x+","+y)
}

var streamImg = $('imageFeed').getElement('img');
$(streamImg).addEvent( 'click', handleClick.bindWithEvent( $(streamImg) ) );
<?php
}
?>
</script>
</body>
</html>
