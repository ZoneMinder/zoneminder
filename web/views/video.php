<?php
//
// ZoneMinder web video view file, $Date$, $Revision$
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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

if ( !empty($user['MonitorIds']) )
    $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
else
    $midSql = '';

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultRate,M.DefaultScale FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?'.$midSql;
$event = dbFetchOne( $sql, NULL, array( $_REQUEST['eid'] ) );

if ( isset( $_REQUEST['rate'] ) )
    $rate = validInt($_REQUEST['rate']);
else
    $rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );
if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$eventPath = ZM_DIR_EVENTS.'/'.getEventPath( $event );

$videoFormats = array();
$ffmpegFormats = preg_split( '/\s+/', ZM_FFMPEG_FORMATS );
foreach ( $ffmpegFormats as $ffmpegFormat )
{
    if ( preg_match( '/^([^*]+)(\*\*?)$/', $ffmpegFormat, $matches ) )
    {
        $videoFormats[$matches[1]] = $matches[1];
        if ( !isset($videoFormat) && $matches[2] == "*" )
        {
            $videoFormat = $matches[1];
        }
    }
    else
    {
        $videoFormats[$ffmpegFormat] = $ffmpegFormat;
    }
}

$videoFiles = array();
if ( $dir = opendir( $eventPath ) )
{
    while ( ($file = readdir( $dir )) !== false )
    {
        $file = $eventPath.'/'.$file;
        if ( is_file( $file ) )
        {
            if ( preg_match( '/\.(?:'.join( '|', $videoFormats ).')$/', $file ) )
            {
                $videoFiles[] = $file;
            }
        }
    }
    closedir( $dir );
}

if ( isset($_REQUEST['deleteIndex']) )
{
    $deleteIndex = validInt($_REQUEST['deleteIndex']);
    unlink( $videoFiles[$deleteIndex] );
    unset( $videoFiles[$deleteIndex] );
}

if ( isset($_REQUEST['downloadIndex']) )
{
    $downloadIndex = validInt($_REQUEST['downloadIndex']);
    header( "Pragma: public" );
    header( "Expires: 0" );
    header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
    header( "Cache-Control: private", false ); // required by certain browsers
    header( "Content-Description: File Transfer" );
    header( 'Content-disposition: attachment; filename="'.basename($videoFiles[$downloadIndex]).'"' ); // basename is required because the video index contains the path and firefox doesn't strip the path but simply replaces the slashes with an underscore.
    header( "Content-Transfer-Encoding: binary" );
    header( "Content-Type: application/force-download" );
    header( "Content-Length: ".filesize($videoFiles[$downloadIndex]) ); 
    readfile( $videoFiles[$downloadIndex] );
    exit;
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Video'] );
?>
<body>
  <div id="page">
    <div id="header">
      <div id="headerButtons">
        <a href="#" onclick="closeWindow()"><?= $SLANG['Close'] ?></a>
      </div>
      <h2><?= $SLANG['Video'] ?></h2>
    </div>
    <div id="content">
<?php
if ( isset($_REQUEST['showIndex']) )
{
    $showIndex = validInt($_REQUEST['showIndex']);
    preg_match( '/([^\/]+)\.([^.]+)$/', $videoFiles[$showIndex], $matches );
    $name = $matches[1];
    $videoFormat = $matches[2];
?>
      <h3 id="videoFile"><?= substr( $videoFiles[$showIndex], strlen(ZM_DIR_EVENTS)+1 ) ?></h3>
      <div id="imageFeed"><?php outputVideoStream( 'videoStream', $videoFiles[$showIndex], validInt($_REQUEST['width']), validInt($_REQUEST['height']), $videoFormat, $name ) ?></div>
<?php
}
else
{
?>
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="id" value="<?= $event['Id'] ?>"/>
        <table id="contentTable" class="minor" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['VideoFormat'] ?></th>
              <td><?= buildSelect( "videoFormat", $videoFormats ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['FrameRate'] ?></th>
              <td><?= buildSelect( "rate", $rates ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['VideoSize'] ?></th>
              <td><?= buildSelect( "scale", $scales ) ?></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['OverwriteExisting'] ?></th>
              <td><input type="checkbox" name="overwrite" value="1"<?php if ( !empty($_REQUEST['overwrite']) ) { ?> checked="checked"<?php } ?>/></td>
            </tr>
          </tbody>
        </table>
        <input type="button" value="<?= $SLANG['GenerateVideo'] ?>" onclick="generateVideo( this.form );"<?php if ( !ZM_OPT_FFMPEG ) { ?> disabled="disabled"<?php } ?>/>
      </form>
<?php
    if ( isset($_REQUEST['generated']) )
    {
?>
      <h2 id="videoProgress" class="<?= $_REQUEST['generated']?'infoText':'errorText' ?>"><span id="videoProgressText"><?= $_REQUEST['generated']?$SLANG['VideoGenSucceeded']:$SLANG['VideoGenFailed'] ?></span><span id="videoProgressTicker"></span></h2>
<?php
    }
    else
    {
?>
      <h2 id="videoProgress" class="hidden warnText"><span id="videoProgressText"><?= $SLANG['GeneratingVideo'] ?></span><span id="videoProgressTicker"></span></h2>
<?php
    }
?>
      <h2 id="videoFilesHeader"><?= $SLANG['VideoGenFiles'] ?></h2>
<?php
    if ( count($videoFiles) == 0 )
    {
?>
      <h3 id="videoNoFiles"><?= $SLANG['VideoGenNoFiles'] ?></h3>
<?php
    }
    else
    {
?>
      <table id="videoTable" class="major" cellspacing="0">
        <thead>
          <tr>
            <th scope="row"><?= $SLANG['Format'] ?></th>
            <th scope="row"><?= $SLANG['Size'] ?></th>
            <th scope="row"><?= $SLANG['Rate'] ?></th>
            <th scope="row"><?= $SLANG['Scale'] ?></th>
            <th scope="row"><?= $SLANG['Action'] ?></th>
          </tr>
        </thead>
        <tbody>
<?php
        $index = 0;
        foreach ( $videoFiles as $file )
        {
            if ( filesize( $file ) > 0 )
            {
                preg_match( '/^(.+)-((?:r[_\d]+)|(?:F[_\d]+))-((?:s[_\d]+)|(?:S[0-9a-z]+))\.([^.]+)$/', $file, $matches );
                if ( preg_match( '/^r(.+)$/', $matches[2], $temp_matches ) )
                {
                    $rate = (int)(100 * preg_replace( '/_/', '.', $temp_matches[1] ) );
                    $rateText = isset($rates[$rate])?$rates[$rate]:($rate."x");
                }
                elseif ( preg_match( '/^F(.+)$/', $matches[2], $temp_matches ) )
                {
                    $rateText = $temp_matches[1]."fps";
                }
                if ( preg_match( '/^s(.+)$/', $matches[3], $temp_matches ) )
                {
                    $scale = (int)(100 * preg_replace( '/_/', '.', $temp_matches[1] ) );
                    $scaleText = isset($scales[$scale])?$scales[$scale]:($scale."x");
                }
                elseif ( preg_match( '/^S(.+)$/', $matches[3], $temp_matches ) )
                {
                    $scaleText = $temp_matches[1];
                }
                $width = $scale?reScale( $event['Width'], $scale ):$event['Width'];
                $height = $scale?reScale( $event['Height'], $scale ):$event['Height'];
?>
        <tr>
          <td><?= $matches[4] ?></td>
          <td><?= filesize( $file ) ?></td>
          <td><?= $rateText ?></td>
          <td><?= $scaleText ?></td>
          <td><?= makePopupLink( '?view='.$view.'&amp;eid='.$event['Id'].'&amp;width='.$width.'&amp;height='.$height.'&amp;showIndex='.$index, 'zmVideo'.$event['Id'].'-'.$scale, array( 'videoview', $width, $height ), $SLANG['View'] ); ?>&nbsp;/&nbsp;<a href="<?= substr( $file, strlen(ZM_DIR_EVENTS)+1 ) ?>" onclick="downloadVideo( <?= $index ?> ); return( false );"><?= $SLANG['Download'] ?></a>&nbsp;/&nbsp;<a href="#" onclick="deleteVideo( <?= $index ?> ); return( false );"><?= $SLANG['Delete'] ?></a></td>
        </tr>
<?php
                $index++;
            }
        }
?>
        </tbody>
      </table>
<?php
    }
}
?>
    </div>
  </div>
</body>
</html>
