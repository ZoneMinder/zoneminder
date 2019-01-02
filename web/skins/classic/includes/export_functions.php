<?php
//
// ZoneMinder web export function library, $Date$, $Revision$
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

function exportHeader( $title )
{
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?php echo $title ?></title>
  <style type="text/css">
  <!--
<?php include( ZM_SKIN_PATH.'/css/'.ZM_SKIN_NAME.'/export.css' ); ?>
	

ul.tabs {
	margin: 0;
        margin-bottom: -1px;
	padding: 0;
	float: left;
	list-style: none;
	height: 32px;
	border-bottom: 1px solid #7f7fb2;
	border-left: 1px solid #7f7fb2;
	width: 100%;
}
ul.tabs li {
	float: left;
	margin: 0;
	padding: 0;
	height: 31px;
	line-height: 31px;
	border: 1px solid #7f7fb2;
	border-left: none;
	margin-bottom: -1px;
	background: #fff;
	overflow: hidden;
	position: relative;
}
ul.tabs li a {
	text-decoration: none;
	color: #000;
	display: block;
	font-size: 1.2em;
	padding: 0 20px;
	outline: none;
}
ul.tabs li a:hover {
	background: #ccc;
}
html ul.tabs li.active, html ul.tabs li.active a:hover  {
	background: #dddddd;
	border-bottom: 1px solid #e0e0e0;
}
  -->
  </style>
  <script type="text/javascript">
<?php include(ZM_SKIN_PATH.'/js/jquery.js') ?>
</script>
  <script type="text/javascript" language="javascript" charset="utf-8">

  /*==========[tab code]==========*/
 $(document).ready(function() {

	//When page loads...
	$(".tab_content").hide(); //Hide all content
	$("ul.tabs li:first").addClass("active").show(); //Activate first tab
	$(".tab_content:first").show(); //Show first tab content

	//On Click Event
	$("ul.tabs li").click(function() {

		$("ul.tabs li").removeClass("active"); //Remove any "active" class
		$(this).addClass("active"); //Add "active" class to selected tab
		$(".tab_content").hide(); //Hide all tab content

		var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
		$(activeTab).fadeIn(); //Fade in the active ID content
		return false;
	});

});
  // ]]>
  </script>
</head>
<?php
}

function exportEventDetail( $event, $exportFrames, $exportImages )
{
    ob_start();
    exportHeader( translate('Event')." ".$event->Id() );
	
	$otherlinks = '';
	if( $exportFrames ) $otherlinks .= '<a href="zmEventFrames.html">'.translate('Frames').'</a>,';
	if( $exportImages ) $otherlinks .= '<a href="zmEventImages.html">'.translate('Images').'</a>,';
	$otherlinks = substr($otherlinks,0,-1);


?>
<body>
  <div id="page">
    <div id="content">
		<h2><?php echo translate('Event') ?>: <?php echo validHtmlStr($event->Name()) ?><?php if(!empty($otherlinks)) { ?> (<?php echo$otherlinks?>) <?php } ?></h2>
      <table id="eventDetail">
        <tr><th scope="row"><?php echo translate('Id') ?></th><td><?php echo $event->Id() ?></td></tr>
        <tr><th scope="row"><?php echo translate('Name') ?></th><td><?php echo validHtmlStr($event->Name()) ?></td></tr>
        <tr><th scope="row"><?php echo translate('Monitor') ?></th><td><?php echo validHtmlStr($event->MonitorName()) ?> (<?php echo $event->MonitorId() ?>)</td></tr>
        <tr><th scope="row"><?php echo translate('Cause') ?></th><td><?php echo validHtmlStr($event->Cause()) ?></td></tr>
        <tr><th scope="row"><?php echo translate('Notes') ?></th><td><?php echo validHtmlStr($event->Notes()) ?></td></tr>
        <tr><th scope="row"><?php echo translate('Time') ?></th><td><?php echo strftime( STRF_FMT_DATETIME_SHORTER, strtotime($event->StartTime()) ) ?></td></tr>
        <tr><th scope="row"><?php echo translate('Duration') ?></th><td><?php echo $event->Length() ?></td></tr>
        <tr><th scope="row"><?php echo translate('Frames') ?></th><td><?php echo $event->Frames() ?></td></tr>
        <tr><th scope="row"><?php echo translate('AttrAlarmFrames') ?></th><td><?php echo $event->AlarmFrames() ?></td></tr>
        <tr><th scope="row"><?php echo translate('AttrTotalScore') ?></th><td><?php echo $event->TotScore() ?></td></tr>
        <tr><th scope="row"><?php echo translate('AttrAvgScore') ?></th><td><?php echo $event->AvgScore() ?></td></tr>
        <tr><th scope="row"><?php echo translate('AttrMaxScore') ?></th><td><?php echo $event->MaxScore() ?></td></tr>
        <tr><th scope="row"><?php echo translate('Archived') ?></th><td><?php echo $event->Archived()?translate('Yes'):translate('No') ?></td></tr>
      </table>
    </div>
  </div>
</body>
</html>
<?php
    return( ob_get_clean() );
}

function exportEventFrames( $event, $exportDetail, $exportImages )
{
    $sql = "SELECT *, unix_timestamp( TimeStamp ) AS UnixTimeStamp FROM Frames WHERE EventID = ? ORDER BY FrameId";
    $frames = dbFetchAll( $sql, NULL, array( $event->Id() ) );

    ob_start();
    exportHeader( translate('Frames')." ".$event->Id() );
	
	$otherlinks = '';
	if( $exportDetail ) $otherlinks .= '<a href="zmEventDetail.html">'.translate('Event').'</a>,';
	if( $exportImages ) $otherlinks .= '<a href="zmEventImages.html">'.translate('Images').'</a>,';
	$otherlinks = substr($otherlinks,0,-1);

?>
<body>
  <div id="page">
    <div id="content">
		<h2><?php echo translate('Frames') ?>: <?php echo validHtmlStr($event->Name()) ?><?php if(!empty($otherlinks)) { ?> (<?php echo$otherlinks?>) <?php } ?></h2>
      <table id="eventFrames">
        <tr>
          <th><?php echo translate('FrameId') ?></th>
          <th><?php echo translate('Type') ?></th>
          <th><?php echo translate('TimeStamp') ?></th>
          <th><?php echo translate('TimeDelta') ?></th>
          <th><?php echo translate('Score') ?></th>
<?php
    if ( $exportImages )
    {
?>
          <th><?php echo translate('Image') ?></th>
<?php
    }
?>
        </tr>
<?php
    if ( count($frames) )
    {
        $eventPath = $event->Path();
        foreach ( $frames as $frame )
        {
            $imageFile = sprintf( "%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $frame['FrameId'] );
            $imagePath = $eventPath."/".$imageFile;
            $analImage = preg_replace( "/capture/", "analyse", $imagePath );
            if ( file_exists( $analImage ) )
            {
                $imageFile = preg_replace( "/capture/", "analyse", $imageFile );
            }

            $class = strtolower($frame['Type']);
?>
        <tr class="<?php echo $class ?>">
          <td><?php echo $frame['FrameId'] ?></td>
          <td><?php echo $frame['Type'] ?></td>
          <td><?php echo strftime( STRF_FMT_TIME, $frame['UnixTimeStamp'] ) ?></td>
          <td><?php echo number_format( $frame['Delta'], 2 ) ?></td>
          <td><?php echo $frame['Score'] ?></td>
<?php
            if ( $exportImages )
            {
?>
          <td><a href="<?php echo $imageFile ?>" target="zmExportImage"><img src="<?php echo $imageFile ?>" border="0" class="thumb" alt="Frame <?php echo $frame['FrameId'] ?>"/></a></td>
<?php
            }
?>
        </tr>
<?php
        }
    }
    else
    {
?>
        <tr>
          <td class="monoRow" colspan="<?php echo $exportImages?6:5 ?>"><?php echo translate('NoFramesRecorded') ?></td>
        </tr>
<?php
    }
?>
      </table>
    </div>
  </div>
</body>
</html>
<?php
    return( ob_get_clean() );
}

function exportEventImages( $event, $exportDetail, $exportFrames, $myfilelist )
{
    ob_start();
    exportHeader( translate('Images')." ".$event->Id() );
	
	$otherlinks = '';
	if( $exportDetail ) $otherlinks .= '<a href="zmEventDetail.html">'.translate('Event').'</a>,';
	if( $exportFrames ) $otherlinks .= '<a href="zmEventFrames.html">'.translate('Frames').'</a>,';
	$otherlinks = substr($otherlinks,0,-1);

	$filelist = array_keys($myfilelist);
	sort($filelist,SORT_NUMERIC);
	$slides = '"'.implode('","',$filelist).'"';
	$listcount = count($filelist);
?>
<body>
<style>
*.horizontal_track {background-color: #bbb;width: <?php echo$event->Width()?>px;line-height: 0px;font-size: 0px;text-align: left;padding: 4px;border: 1px solid;border-color: #ddd #999 #999 #ddd;}
*.horizontal_slider {background-color: #666;width: 16px;height: 8px;position: relative;z-index: 2;line-height: 0;margin: 0;border: 2px solid;border-color: #999 #333 #333 #999;}
*.horizontal_slit {background-color: #333;width: <?php echo($event->Width()-10)?>px;height: 2px;margin: 4px 4px 2px 4px;line-height: 0;position: absolute;z-index: 1;border: 1px solid;border-color: #999 #ddd #ddd #999;}
*.vertical_track {background-color: #bbb;padding: 3px 5px 15px 5px;border: 1px solid;border-color: #ddd #999 #999 #ddd;}
*.vertical_slider {background-color: #666;width: 18px;height: 8px;font: 0px;text-align: left;line-height: 0px;position: relative;z-index: 1;border: 2px solid;border-color: #999 #333 #333 #999;}
*.vertical_slit {background-color: #000;width: 2px;height: 100px;position: absolute;margin: 4px 10px 4px 10px;padding: 4px 0 1px 0;line-height: 0;font-size: 0;border: 1px solid;border-color: #666 #ccc #ccc #666;}
*.display_holder {background-color: #bbb;color: #fff;width: 34px;height: 20px;text-align: right;padding: 0;border: 1px solid;border-color: #ddd #999 #999 #ddd;}
.value_display {background-color: #bbb;color: #333;width: 30px;margin: 0 2px;text-align: right;font-size: 8pt;font-face: verdana, arial, helvetica, sans-serif;font-weight: bold;line-height: 12px;border: 0;cursor: default;}
</style>

<h2><?php echo translate('Images') ?>: <?php echo validHtmlStr($event->Name()) ?><?php if(!empty($otherlinks)) { ?> (<?php echo$otherlinks?>) <?php } ?></h2>

<ilayer id="slidensmain" width=&{slidewidth}; height=&{slideheight}; bgColor=&{slidebgcolor}; visibility=hide>
<layer id="slidenssub" width=&{slidewidth}; left=auto top=auto></layer>
</ilayer>
<div id="imagevideo" align="center"></div>
<br>
<div align="center">
<button onclick="stepbackward()">< Step</button><button 
	id="btnrwd" onclick="rewind()" >Rwd</button><button
	id="btnplay" onclick="playstop()">Stop</button><button
	onclick="stepforward()">Step ></button><button
	id="btnspeedup" onclick="speedup()">speedup</button><button
	id="btnspeeddown" onclick="speeddown()">slowdown</button>
</div>
<div align="center"><div class="horizontal_track" >
	<div class="horizontal_slit" >&nbsp;</div>
    <div class="horizontal_slider" id="imageslider_id" style="left: 0px;"
        onmousedown="slide(event,'horizontal', <?php echo($event->Width()-20)?>, 1, <?php echo$listcount?>, <?php echo$listcount?>,0, 'imageslider_display_id');" >&nbsp;</div>
</div></div>
<div align="center"><div class="display_holder" ><input id="imageslider_display_id" class="value_display" type="text" value="0" onfocus="blur(this);" /></div></div>


<script language="JavaScript1.2">

/***********************************************
* Flexi Slideshow- © Dynamic Drive (www.dynamicdrive.com)
* This notice must stay intact for use
* Visit http://www.dynamicdrive.com/ for full source code
***********************************************/

var eventWidth = <?php echo$event->Width()?>;
var eventHeight = <?php echo$event->Height()?>;
var variableslide=[<?php echo$slides?>];

//configure the below 3 variables to set the dimension/background color of the slideshow

var slidewidth=eventWidth+'px' //set to width of LARGEST image in your slideshow
var slideheight=eventHeight+'px' //set to height of LARGEST iamge in your slideshow, plus any text description
var slidebgcolor='#ffffff'

//configure the below variable to determine the delay between image rotations (in miliseconds)
var origslidedelay=100;
var slidedelay=origslidedelay;

////Do not edit pass this line////////////////

var ie=document.all;
var dom=document.getElementById;

for (i=0;i<variableslide.length;i++){
	var cacheimage=new Image()
	cacheimage.src=variableslide[i]
}

var currentslide=-1
var mytimer = null;

//if (ie||dom) document.write('<div id="slidedom" style="width:'+slidewidth+'px;height:'+slideheight+'; background-color:'+slidebgcolor+'"></div>');
if (ie||dom) document.getElementById('imagevideo').innerHTML = '<div id="slidedom" style="width:'+slidewidth+'px;height:'+slideheight+'; background-color:'+slidebgcolor+'"><img src="" name="imageslideframe"></div>';

function rotateimages(){
	if (currentslide==variableslide.length-1) currentslide=0;
	else currentslide++;

	changeimage();
	
	mytimer = setTimeout("rotateimages()",slidedelay);
}

function changeimage() {
	contentcontainer='<center><img src="'+variableslide[currentslide]+'" border="0" vspace="3"></center>';

	if (document.layers){
		crossrotateobj.document.write(contentcontainer);
		crossrotateobj.document.close();
	}
	else if (ie||dom) document.imageslideframe.src = variableslide[currentslide];
	
	slideManual(currentslide+1,eventWidth-20, 1, variableslide.length);
}


function start_slider(){
	crossrotateobj=dom? document.getElementById("slidedom") : ie? document.all.slidedom : document.slidensmain.document.slidenssub;
	if (document.layers) document.slidensmain.visibility="show";
	rotateimages();
}


// seyi_code
function rotateimagesrewind(){
	if (currentslide==0) currentslide=variableslide.length-1;
	else currentslide--;

	changeimage();
	
	mytimer = setTimeout("rotateimagesrewind()",slidedelay);
}

function stepforward() {
	clearTimeout(mytimer);
//	document.getElementById('btnrwd').style.borderTop='2px solid #ffffff';
//	document.getElementById('btnrwd').style.borderBottom='2px solid #848284';
//	document.getElementById('btnrwd').style.borderRight='2px solid #848284';
//	document.getElementById('btnrwd').style.borderLeft='1px solid #ffffff';
	document.getElementById('btnplay').disabled = false;
	document.getElementById('btnplay').innerHTML = 'Play';
	document.getElementById('btnspeedup').disabled = true;
	document.getElementById('btnspeeddown').disabled = true;

	if (currentslide==variableslide.length-1) currentslide=0;
	else currentslide++;

	changeimage();

}
function stepbackward() {
	clearTimeout(mytimer);
//	document.getElementById('btnrwd').style.borderTop='2px solid #ffffff';
//	document.getElementById('btnrwd').style.borderBottom='2px solid #848284';
//	document.getElementById('btnrwd').style.borderRight='2px solid #848284';
//	document.getElementById('btnrwd').style.borderLeft='1px solid #ffffff';
	document.getElementById('btnplay').disabled = false;
	document.getElementById('btnplay').innerHTML = 'Play';
	document.getElementById('btnspeedup').disabled = true;
	document.getElementById('btnspeeddown').disabled = true;

	if (currentslide==0) currentslide=variableslide.length-1;
	else currentslide--;

	changeimage();

}
function speedup() { slidedelay = slidedelay/2; }
function speeddown() { slidedelay = slidedelay*2; }
function playstop() { 	
	if(document.getElementById('btnplay').innerHTML == 'Play') {
		slidedelay = origslidedelay;
		mytimer = setTimeout("rotateimages()",slidedelay); 
		document.getElementById('btnplay').innerHTML = 'Stop';
		document.getElementById('btnspeedup').disabled = false;
		document.getElementById('btnspeeddown').disabled = false;
	} else if(document.getElementById('btnplay').innerHTML == 'Stop') {
		clearTimeout(mytimer);
		document.getElementById('btnplay').innerHTML = 'Play';
		document.getElementById('btnrwd').disabled = false;
		document.getElementById('btnspeedup').disabled = true;
		document.getElementById('btnspeeddown').disabled = true;
	}
}
function rewind() {
	clearTimeout(mytimer);

	if(!document.getElementById('btnplay').disabled) {
		slidedelay = origslidedelay;
		mytimer = setTimeout("rotateimagesrewind()",slidedelay); 
	
//		document.getElementById('btnrwd').style.borderTop = '2px solid #414241'; 
//		document.getElementById('btnrwd').style.borderBottom = '1px solid #ffffff'; 
//		document.getElementById('btnrwd').style.borderLeft = '2px solid #414241'; 
//		document.getElementById('btnrwd').style.borderRight = '1px solid #ffffff';
		document.getElementById('btnplay').disabled = true;
		document.getElementById('btnspeedup').disabled = false;
		document.getElementById('btnspeeddown').disabled = false;
	} else {

//		document.getElementById('btnrwd').style.borderTop='2px solid #ffffff';
//		document.getElementById('btnrwd').style.borderBottom='2px solid #848284';
//		document.getElementById('btnrwd').style.borderRight='2px solid #848284';
//		document.getElementById('btnrwd').style.borderLeft='1px solid #ffffff';
		document.getElementById('btnplay').disabled = false;
		document.getElementById('btnspeedup').disabled = true;
		document.getElementById('btnspeeddown').disabled = true;
	}

}

//---------------------------------+
//  CARPE  S l i d e r        1.3  |
//  2005 - 12 - 10                 |
//  By Tom Hermansson Snickars     |
//  Copyright CARPE Design         |
//  http://carpe.ambiprospect.com/ |
//---------------------------------+

// carpeGetElementByID: Cross-browser version of "document.getElementById()"
function carpeGetElementById(element) {
	if (document.getElementById) element = document.getElementById(element);
	else if (document.all) element = document.all[element];
	else element = null;
	return element;
}
// carpeLeft: Cross-browser version of "element.style.left"
function carpeLeft(elmnt, pos) {
	if (!(elmnt = carpeGetElementById(elmnt))) return 0;
	if (elmnt.style && (typeof(elmnt.style.left) == 'string')) {
		if (typeof(pos) == 'number') elmnt.style.left = pos + 'px';
		else {
			pos = parseInt(elmnt.style.left);
			if (isNaN(pos)) pos = 0;
		}
	}
	else if (elmnt.style && elmnt.style.pixelLeft) {
		if (typeof(pos) == 'number') elmnt.style.pixelLeft = pos;
		else pos = elmnt.style.pixelLeft;
	}
	return pos;
}
// carpeTop: Cross-browser version of "element.style.top"
function carpeTop(elmnt, pos) {
	if (!(elmnt = carpeGetElementById(elmnt))) return 0;
	if (elmnt.style && (typeof(elmnt.style.top) == 'string')) {
		if (typeof(pos) == 'number') elmnt.style.top = pos + 'px';
		else {
			pos = parseInt(elmnt.style.top);
			if (isNaN(pos)) pos = 0;
		}
	}
	else if (elmnt.style && elmnt.style.pixelTop) {
		if (typeof(pos) == 'number') elmnt.style.pixelTop = pos;
		else pos = elmnt.style.pixelTop;
	}
	return pos;
}
// moveSlider: Handles slider and display while dragging
function moveSlider(evnt) {
	var evnt = (!evnt) ? window.event : evnt; // The mousemove event
	if (mouseover) { // Only if slider is dragged
		x = pxLeft + evnt.screenX - xCoord // Horizontal mouse position relative to allowed slider positions
		y = pxTop + evnt.screenY - yCoord // Horizontal mouse position relative to allowed slider positions
		if (x > xMax) x = xMax // Limit horizontal movement
		if (x < 0) x = 0 // Limit horizontal movement
		if (y > yMax) y = yMax // Limit vertical movement
		if (y < 0) y = 0 // Limit vertical movement
		carpeLeft(sliderObj.id, x)  // move slider to new horizontal position
		carpeTop(sliderObj.id, y) // move slider to new vertical position
		sliderVal = x + y // pixel value of slider regardless of orientation
		sliderPos = (sliderObj.pxLen / sliderObj.valCount) * Math.round(sliderObj.valCount * sliderVal / sliderObj.pxLen)
		v = Math.round((sliderPos * sliderObj.scale + sliderObj.fromVal) * // calculate display value
			Math.pow(10, displayObj.dec)) / Math.pow(10, displayObj.dec)
		displayObj.value = v // put the new value in the slider display element
		
		// seyi_code
		currentslide = v-1;
		changeimage();
		return false
	}
	return
}

// moveSlider: Handles the start of a slider move.
function slide(evnt, orientation, length, from, to, count, decimals, display) {
	if (!evnt) evnt = window.event;
	sliderObj = (evnt.target) ? evnt.target : evnt.srcElement; // Get the activated slider element.
	sliderObj.pxLen = length // The allowed slider movement in pixels.
	sliderObj.valCount = count ? count - 1 : length // Allowed number of values in the interval.
	displayObj = carpeGetElementById(display) // Get the associated display element.\
	displayObj.dec = decimals // Number of decimals to be displayed.
	sliderObj.scale = (to - from) / length // Slider-display scale [value-change per pixel of movement].
	if (orientation == 'horizontal') { // Set limits for horizontal sliders.
		sliderObj.fromVal = from
		xMax = length
		yMax = 0
	}
	if (orientation == 'vertical') { // Set limits and scale for vertical sliders.
		sliderObj.fromVal = to
		xMax = 0
		yMax = length
		sliderObj.scale = -sliderObj.scale // Invert scale for vertical sliders. "Higher is more."
	}
	pxLeft = carpeLeft(sliderObj.id) // Sliders horizontal position at start of slide.
	pxTop  = carpeTop(sliderObj.id) // Sliders vertical position at start of slide.
	xCoord = evnt.screenX // Horizontal mouse position at start of slide.
	yCoord = evnt.screenY // Vertical mouse position at start of slide.
	mouseover = true
	document.onmousemove = moveSlider // Start the action if the mouse is dragged.
	document.onmouseup = sliderMouseUp // Stop sliding.
}
// sliderMouseup: Handles the mouseup event after moving a slider.
// Snaps the slider position to allowed/displayed value. 
function sliderMouseUp() {
	mouseover = false // Stop the sliding.
	v = (displayObj.value) ? displayObj.value : 0 // Find last display value.
	pos = (v - sliderObj.fromVal)/(sliderObj.scale) // Calculate slider position (regardless of orientation).
	if (yMax == 0) carpeLeft(sliderObj.id, pos) // Snap horizontal slider to corresponding display position.
	if (xMax == 0) carpeTop(sliderObj.id, pos) // Snap vertical slider to corresponding display position.
	if (document.removeEventListener) { // Remove event listeners from 'document' (Moz&co).
		document.removeEventListener('mousemove', moveSlider)
		document.removeEventListener('mouseup', sliderMouseUp)
	}
	else if (document.detachEvent) { // Remove event listeners from 'document' (IE&co).
		document.detachEvent('onmousemove', moveSlider)
		document.detachEvent('onmouseup', sliderMouseUp)
	}
}

//seyi_code
//slide(event,'horizontal', 300, 1, 22, 22,0, 'imageslider_display_id');
//slide(evnt, orientation, length, from, to, count, decimals, display) {
function slideManual(val,length,from,to) {
	scale = (to - from) / length // Slider-display scale [value-change per pixel of movement].
	fromVal = from
	xMax = length
	yMax = 0
	sliderid = 'imageslider_id';
	
	
	v = (val) ? val : 0 // Find last display value.
	displayobject = carpeGetElementById('imageslider_display_id') // Get the associated display element.\
	displayobject.value = val;
	pos = (v - fromVal)/(scale) // Calculate slider position (regardless of orientation).
	if (yMax == 0) carpeLeft(sliderid, pos) // Snap horizontal slider to corresponding display position.
}

if (ie||dom) start_slider();
else if (document.layers) window.onload=start_slider;

</script>

</body>
</html>
<?php
    return( ob_get_clean() );
}

function exportEventImagesMaster( $eids ) {
  ob_start();
  exportHeader( translate('Images').' Master' );
?>
<body>
<h2><?php echo translate('Images') ?> Master</h2>
<?php
  // TODO: SHould use find to make this 1 db query
	foreach ($eids as $eid) {
		//get monitor id and event id
		$event = new Event( $eid );
		$eventMonitorId[$eid] = $event->MonitorId();
		$eventPath[$eid] = $event->Relative_Path();
	}
	
	$monitors = array_values(array_flip(array_flip($eventMonitorId))); //unique monitors and reindex the array
	$monitorNames = array();
	
	//*
	if(!empty($monitors)) {
		$tmp = dbFetchAll("SELECT Id,Name FROM Monitors WHERE Id IN (".implode(',', $monitors).") ");
		foreach ( $tmp as $row ) { $monitorNames[$row['Id']] = $row['Name']; }
	}
	//*/
	//trigger_error(print_r($monitorNames,1));

?>
<div id="tabs">
	<ul class="tabs">
		<li class="active"><a href="#all"> All </a></li>
		<?php
		foreach ($monitors as $monitor) {
			# code...
			echo "<li><a href='#tab$monitor'>" . $monitorNames[$monitor] . '</a></li>';
		}
		?>
	</ul>
</div>
<table width="100%" height="100%" ><tr>
<td valign="top" bgcolor="#dddddd" style="padding:10px;">
	<div class='tab_content' id='all'>
	<h2> All </h2>
	<?php
	if (!is_array($eids)) {
		echo "<div><a href=\"javascript:switchevent('$eids/zm/EventImages.html');\"> $eids </div>";
	}
	?>
	<?php foreach($eids as $eid) {
	?>
		<div><a href="javascript:switchevent('<?php echo   $eventPath[$eid]; ?>/zmEventImages.html');"><?php echo$eid?></a></div>
		<?php
	} 
	?>
	</div>
	<?php
		foreach ($monitors as $monitor) {
			echo "<div class='tab_content' id='tab$monitor'>";
			echo "<h2>Monitor: " . $monitorNames[$monitor] . " </h2>";
			foreach ($eids as $eid) {
				if ($eventMonitorId[$eid] == $monitor) {
				?>	
				<div><a href="javascript:switchevent('<?php echo   $eventPath[$eid]; ?>/zmEventImages.html');"><?php echo$eid?></a></div>
				<?php
				}
			}
			echo'</div>';
		}
	?>

</td><td>

	<iframe id="myframe" onload="resizeCaller();" name="myframe" src="#" 
		scrolling="no" marginwidth="0" marginheight="0" frameborder="0" 
		vspace="0" hspace="0" style="overflow:visible; width:100%; display:none">
	</iframe>
	
</td>
</tr></table>

<script type="text/javascript">
function switchevent(src) { 
	if(document.all) document.all.myframe.src = src;
	else window.frames['myframe'].location.href = src; 
}

/***********************************************
* IFrame SSI script II- © Dynamic Drive DHTML code library (http://www.dynamicdrive.com)
* Visit DynamicDrive.com for hundreds of original DHTML scripts
* This notice must stay intact for legal use
***********************************************/

//Input the IDs of the IFRAMES you wish to dynamically resize to match its content height:
//Separate each ID with a comma. Examples: ["myframe1", "myframe2"] or ["myframe"] or [] for none:
var iframeids=["myframe"]

//Should script hide iframe from browsers that don't support this script (non IE5+/NS6+ browsers. Recommended):
var iframehide="yes"

var getFFVersion=navigator.userAgent.substring(navigator.userAgent.indexOf("Firefox")).split("/")[1]
var FFextraHeight=parseFloat(getFFVersion)>=0.1? 16 : 0 //extra height in px to add to iframe in FireFox 1.0+ browsers

function resizeCaller() {
	var dyniframe=new Array()
	for (i=0; i<iframeids.length; i++){
		if (document.getElementById) resizeIframe(iframeids[i]);
		//reveal iframe for lower end browsers? (see var above):
		if ((document.all || document.getElementById) && iframehide=="no"){
			var tempobj=document.all? document.all[iframeids[i]] : document.getElementById(iframeids[i])
			tempobj.style.display="block"
		}
	}
}

function resizeIframe(frameid){
	var currentfr=document.getElementById(frameid)
	if (currentfr && !window.opera){
		currentfr.style.display="block"
		if (currentfr.contentDocument && currentfr.contentDocument.body.offsetHeight) //ns6 syntax
			currentfr.height = currentfr.contentDocument.body.offsetHeight+FFextraHeight; 
		else if (currentfr.Document && currentfr.Document.body.scrollHeight) //ie5+ syntax
			currentfr.height = currentfr.Document.body.scrollHeight;
		if (currentfr.addEventListener) currentfr.addEventListener("load", readjustIframe, false);
		else if (currentfr.attachEvent){
			currentfr.detachEvent("onload", readjustIframe) // Bug fix line
			currentfr.attachEvent("onload", readjustIframe)
		}
	}
}

function readjustIframe(loadevt) {
	var crossevt=(window.event)? event : loadevt;
	var iframeroot=(crossevt.currentTarget)? crossevt.currentTarget : crossevt.srcElement
	if (iframeroot) resizeIframe(iframeroot.id);
}

function loadintoIframe(iframeid, url){
	if (document.getElementById) document.getElementById(iframeid).src=url;
}

//if (window.addEventListener) window.addEventListener("load", resizeCaller, false)
//else if (window.attachEvent) window.attachEvent("onload", resizeCaller)
//else window.onload=resizeCaller

</script>

</body>
</html>
<?php
    return( ob_get_clean() );
}

function exportFileList( $eid, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc ) {

  if ( (!canView('Events')) or ! $eid ) {
    return;
  }

  $event = new Event($eid);
  $eventPath =  $event->Path();
  $files = array();
  if ( $dir = opendir($eventPath) ) {
    while ( ($file = readdir($dir)) !== false ) {
      if ( is_file($eventPath.'/'.$file) ) {
        $files[$file] = $file;
      }
    }
    closedir($dir);
  }

  $exportFileList = array();

  if ( $exportDetail ) {
    $file = 'zmEventDetail.html';
    if ( !($fp = fopen( $eventPath.'/'.$file, 'w' )) ) {
      Fatal( "Can't open event detail export file '$file'" );
    }
    fwrite( $fp, exportEventDetail( $event, $exportFrames, $exportImages ) );
    fclose( $fp );
    $exportFileList[$file] = $eventPath."/".$file;
  }
  if ( $exportFrames ) {
    $file = 'zmEventFrames.html';
    if ( !($fp = fopen( $eventPath.'/'.$file, 'w' )) ) {
      Fatal( "Can't open event frames export file '$file'" );
    }
    fwrite( $fp, exportEventFrames( $event, $exportDetail, $exportImages ) );
    fclose( $fp );
    $exportFileList[$file] = $eventPath."/".$file;
  }
  if ( $exportImages ) {
    $filesLeft = array();
    $myfilelist = array();
    foreach ( $files as $file ) {
      if ( preg_match( '/-(?:capture|analyse).jpg$/', $file ) ) {
        $exportFileList[$file] = $eventPath."/".$file;
        $myfilelist[$file] = $eventPath."/".$file;
      } else {
        $filesLeft[$file] = $file;
      }
    }
    $files = $filesLeft;

    // create an image slider
    if ( !empty($myfilelist) ) {
      $file = 'zmEventImages.html';
      if ( !($fp = fopen($eventPath.'/'.$file, 'w')) ) Fatal( "Can't open event images export file '$file'" );
      fwrite( $fp, exportEventImages( $event, $exportDetail, $exportFrames, $myfilelist ) );
      fclose( $fp );
      $exportFileList[$file] = $eventPath."/".$file;
    }
  } # end if exportImages

  if ( $exportVideo ) {
    $filesLeft = array();
    foreach ( $files as $file ) {
      if ( preg_match( '/\.(?:mpg|mpeg|mov|swf|mp4|mkv|avi|asf|3gp)$/', $file ) ) {
        $exportFileList[$file] = $eventPath.'/'.$file;
      } else {
        $filesLeft[$file] = $file;
      }
    }
    $files = $filesLeft;
  } # end if exportVideo

  if ( $exportMisc ) {
    foreach ( $files as $file ) {
      $exportFileList[$file] = $eventPath.'/'.$file;
    }
    $files = array();
  }
  return array_values($exportFileList);
}

function exportEvents( $eids, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc, $exportFormat, $exportStructure = false ) {     

  if ( (!canView('Events')) || empty($eids) ) {
    return false;
  }
  $export_root = 'zmExport';
  $export_listFile = 'zmFileList.txt';
  $exportFileList = array();
  $html_eventMaster = '';

  if ( is_array($eids) ) {
    foreach ( $eids as $eid ) {
      $exportFileList = array_merge( $exportFileList, exportFileList( $eid , $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc ) );
    }
  } else {
    $eid = $eids;
    $exportFileList = exportFileList( $eid, $exportDetail, $exportFrames, $exportImages, $exportVideo, $exportMisc );
  }
      
  // create an master image slider
  if ( $exportImages ) {
    if ( !is_array($eids) ) {
      $eids = array($eids);
    }
    $monitorPath = ZM_DIR_EVENTS.'/';
    $html_eventMaster = 'zmEventImagesMaster_'.date('Ymd_His'). '.html';
    if ( !($fp = fopen( $monitorPath.'/'.$html_eventMaster, 'w' )) ) Fatal( "Can't open event images export file '$html_eventMaster'" );
    fwrite($fp, exportEventImagesMaster($eids));
    fclose($fp);
    $exportFileList[] = $monitorPath.'/'.$html_eventMaster;
  }

  if ( ! file_exists(ZM_DIR_EXPORTS) ) {
    if ( ! mkdir(ZM_DIR_EXPORTS) ) {
      Fatal("Can't create exports dir at '".ZM_DIR_EXPORTS."'");
    }
  }
  $listFile = ZM_DIR_EXPORTS.'/'.$export_listFile;
  if ( !($fp = fopen($listFile, 'w')) ) {
    Fatal( "Can't open event export list file '$listFile'" );
  }
  foreach ( $exportFileList as $exportFile ) {
    fwrite( $fp, "$exportFile\n" );
  }
  fclose( $fp );
  $archive = '';
  if ( $exportFormat == 'tar' ) {
    $archive = ZM_DIR_EXPORTS.'/'.$export_root.'.tar.gz';
    @unlink( $archive );
    if ( $exportStructure == 'flat' ) {  //strip file paths if we choose
      $command = "nice -10 tar --create --gzip --file=".escapeshellarg($archive)." --files-from=".escapeshellarg($listFile)." --xform='s#^.+/##x'";
    } else {
      $command = "nice -10 tar --create --gzip --file=".escapeshellarg($archive)." --files-from=".escapeshellarg($listFile);
    }
    exec( $command, $output, $status );
    if ( $status ) {
      Error( "Command '$command' returned with status $status" );
      if ( $output[0] )
        Error( "First line of output is '".$output[0]."'" );
      return( false );
    }
  } elseif ( $exportFormat == 'zip' ) {
    $archive = ZM_DIR_EXPORTS.'/'.$export_root.'.zip';
    @unlink( $archive );
    if ($exportStructure == 'flat') {
      $command = "cat ".escapeshellarg($listFile)." | nice -10 zip -q -j ".escapeshellarg($archive)." -@";
    } else {
      $command = "cat ".escapeshellarg($listFile)." | nice -10 zip -q ".escapeshellarg($archive)." -@";
    }
    //cat zmFileList.txt | zip -q zm_export.zip -@
    //-bash: zip: command not found

    exec( $command, $output, $status );
    if ( $status ) {
      Error("Command '$command' returned with status $status");
      if ( $output[0] )
        Error("First line of output is '".$output[0]."'");
      return false;
    }
  }

  //clean up temporary files
  if ( !empty($html_eventMaster) ) {
    unlink($monitorPath.'/'.$html_eventMaster);
  }

  return '?view=archive%26type='.$exportFormat;
}
