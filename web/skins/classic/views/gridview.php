<?php
if ( !canView( 'Stream' ) )
{
    $view = "error";
    return;
}

require_once( 'includes/Monitor.php' );

if ( !empty($_REQUEST['group']) )
{
    $row = dbFetchOne( 'select * from Groups where Id = ?', NULL, array($_REQUEST['group']) );
	$sql = "select * from Monitors where Function != 'None' and find_in_set( Id, '".$row['MonitorIds']."' ) order by Sequence";
} else { 
	$sql = "select * from Monitors where Function != 'None' order by Sequence";
}

$index = 0;
$monitors = array();
foreach( dbFetchAll( $sql ) as $row )
{
    if ( !visibleMonitor( $row['Id'] ) )
        continue;
    
    $scale = reScale( SCALE_BASE, $row['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

    $scaleWidth = reScale( $row['Width'], $scale );
    $scaleHeight = reScale( $row['Height'], $scale );
    $row['index'] = $index++;
    $row['scaleWidth'] = $scaleWidth;
    $row['scaleHeight'] = $scaleHeight;
    $row['connKey'] = generateConnKey();
    $monitors[] = new Monitor( $row );
}

$columns = isset( $_GET['columns'] ) ? validInt($_GET['columns']) : 3;

xhtmlHeaders(__FILE__, translate('GridView') );
?>
<body>
<table id="viewTable" width="100%" height="900">
<?php
$counter = 0;
foreach ( $monitors as $monitor )
{
	if ($counter == 0)
		print("<tr height=\"".strval(100/ceil((count($monitors)/$columns)))."%\">");
	print("<td width=\"".strval(100/$columns)."%\">");
    $scale = reScale( SCALE_BASE, $monitor->DefaultScale(), ZM_WEB_DEFAULT_SCALE );
?>
        <div id="monitorFrame<?php echo $monitor->index() ?>" class="monitorFrame">
          <div id="monitor<?php echo $monitor->index() ?>" class="monitor idle">
            <div id="imageFeed<?php echo $monitor->index() ?>" class="imageFeed" onclick="createPopup( '?view=watch&amp;mid=<?php echo $monitor->Id() ?>', 'zmWatch<?php echo $monitor->Id() ?>', 'watch', <?php echo $monitor->scaleWidth() ?>, <?php echo $monitor->scaleHeight() ?> );">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $streamSrc = $monitor->getStreamSrc( array( "mode=mpeg", "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
    outputVideoStream( "liveStream".$monitor->Id(), $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), ZM_MPEG_LIVE_FORMAT );
}
else
{
    $streamSrc = $monitor->getStreamSrc( array( "mode=jpeg", "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS ) );
    if ( canStreamNative() )
    {
        outputImageStream( "liveStream".$monitor->Id(), $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ), validHtmlStr($monitor->Name()) );
    }
    else
    {
        outputHelperStream( "liveStream".$monitor->Id(), $streamSrc, reScale( $monitor->Width(), $scale ), reScale( $monitor->Height(), $scale ) );
    }
}
?>
<?php
print("</td>");
if ($counter == ($columns - 1)){
	print("</tr>");
	$counter = 0;
} else {
$counter = $counter + 1;
}
}
?>
</table>
      </div>
    </div>
  </div>
</body>
<script>
function updateURLParameter(url, param, paramVal)
{
    var TheAnchor = null;
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";

    if (additionalURL) 
    {
        var tmpAnchor = additionalURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];
        if(TheAnchor)
            additionalURL = TheParams;

        tempArray = additionalURL.split("&");

        for (i=0; i<tempArray.length; i++)
        {
            if(tempArray[i].split('=')[0] != param)
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }        
    }
    else
    {
        var tmpAnchor = baseURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor  = tmpAnchor[1];

        if(TheParams)
            baseURL = TheParams;
    }

    if(TheAnchor)
        paramVal += "#" + TheAnchor;

    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}

function getParameterByName(name, url) {
    if (!url) {
      url = window.location.href;
    }
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function resizeTable() {
	var viewTable = document.getElementById("viewTable");
	viewTable.setAttribute("height", window.innerHeight - 1);
	viewTable.setAttribute("width", window.innerWidth);
	
	var rowHeight = (window.innerHeight - 1) / viewTable.rows.length;
	var cellWidth = (window.innerWidth) / (100 / parseInt(viewTable.rows[0].cells[0].getAttribute('width')));
	
	var monitorFrameCounter = 0;
	Array.from(viewTable.rows).forEach(function(row) {
		Array.from(row.cells).forEach(function(cell) {
			var liveStream = document.getElementById("monitorFrame" + monitorFrameCounter).getElementsByTagName('img')[0];
			var origHeight = parseInt(liveStream.getAttribute('origheight'));
			var origWidth = parseInt(liveStream.getAttribute('origwidth'));
			if (isNaN(origHeight)){
				origHeight = parseInt(liveStream.getAttribute('height'));
				liveStream.setAttribute("origheight", origHeight);
			}
			if (isNaN(origWidth)){
				origWidth = parseInt(liveStream.getAttribute('width'));
				liveStream.setAttribute("origwidth", origWidth);
			}
			var heightScale = (rowHeight/origHeight);	
			var widthScale = (cellWidth/origWidth);
			if (heightScale < widthScale){
				liveStream.setAttribute("height", rowHeight);
				liveStream.setAttribute("width", heightScale * parseInt(origWidth));
			}else{
				liveStream.setAttribute("width", cellWidth);
				liveStream.setAttribute("height", widthScale * parseInt(origHeight));
			}
			++monitorFrameCounter;
		});
	});
}
window.onresize = function(event) {resizeTable();};

document.onkeyup = function(e) {
	e = e || window.event;
	
	if (e.keyCode == '39' || e.keyCode == '37') {
		var columns = getParameterByName('columns', window.location.href);
		columns = (columns == '' || columns == null) ? 3 : parseInt(columns);
		e.keyCode == '37' ?  --columns : ++columns;
		if (columns <= 0)
			return;
		window.location.href = updateURLParameter(window.location.href, 'columns', columns);
	}
};

resizeTable();
</script>
</html>
