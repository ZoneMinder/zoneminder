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
    $_REQUEST['view'] = "error";
    return;
}

if ( $user['MonitorIds'] )
    $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
else
    $midSql = '';

$sql = 'SELECT E.*,M.Name AS MonitorName,M.DefaultRate FROM Events AS E INNER JOIN Monitors AS M ON E.MonitorId = M.Id WHERE E.Id = ?'.$midSql;
$event = dbFetchOne( $sql, NULL, array($_REQUEST['eid']) );

$deviceWidth = (isset($device)&&!empty($device['width']))?$device['width']:DEVICE_WIDTH;
$deviceHeight = (isset($device)&&!empty($device['height']))?$device['height']:DEVICE_HEIGHT;

if ( $deviceWidth >= 352 && $deviceHeight >= 288 )
    $videoSize = "352x288";
elseif ( $deviceWidth >= 176 && $deviceHeight >= 144 )
    $videoSize = "176x144";
else
    $videoSize = "128x96";

$eventWidth = $event['Width'];
$eventHeight = $event['Height'];

if ( !isset( $rate ) )
    $_REQUEST['rate'] = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );

$eventPath = ZM_DIR_EVENTS.'/'.getEventPath( $event );

$videoFormats = array();
$ffmpegFormats = preg_split( '/\s+/', ZM_FFMPEG_FORMATS );
foreach ( $ffmpegFormats as $ffmpegFormat )
{
    preg_match( '/^([^*]+)(\**)$/', $ffmpegFormat, $matches );
    $videoFormats[$matches[1]] = $matches[1];
    if ( $matches[2] == '*' )
        $defaultVideoFormat = $matches[1];
    elseif ( $matches[2] == '**' )
        $defaultPhoneFormat = $matches[1];
}
if ( !isset($_REQUEST['videoFormat']) )
{
    if ( isset($defaultPhoneFormat) )
        $_REQUEST['videoFormat'] = $defaultPhoneFormat;
    elseif ( isset($defaultVideoFormat) )
        $_REQUEST['videoFormat'] = $defaultVideoFormat;
    else
        $videoFormat = $ffmpegFormats[0];
}

if ( !empty($_REQUEST['generate']) )
{
    $videoFile = createVideo( $event, $_REQUEST['videoFormat'], $_REQUEST['rate'], $videoSize, !empty($_REQUEST['overwrite']) );
}

$videoFiles = array();
if ( $dir = opendir( $eventPath ) )
{
    while ( ($file = readdir( $dir )) !== false )
    {
        $file = $eventPath.'/'.$file;
        if ( is_file( $file ) )
        {
            if ( preg_match( '/-S([\da-z]+)\.(?:'.join( '|', $videoFormats ).')$/', $file, $matches ) )
            {
                if ( $matches[1] == $videoSize )
                {
                    $videoFiles[] = $file;
                }
            }
        }
    }
    closedir( $dir );
}

if ( isset($_REQUEST['download']) )
{
    header( "Content-type: ".getMimeType($videoFiles[$_REQUEST['download']]));
    header( "Content-length: ".filesize($videoFiles[$_REQUEST['download']]));
    header( "Content-disposition: attachment; filename=".preg_replace( "/^.*\//", "", $videoFiles[$_REQUEST['download']] )."; size=".filesize($videoFiles[$_REQUEST['download']]) );
    readfile( $videoFiles[$_REQUEST['download']] );
    exit;
}

xhtmlHeaders( __FILE__, translate('Video').' - '.$event['Name'] );
?>
<body>
  <div id="page">
    <div id="content">
      <form method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
        <div class="hidden">
          <fieldset>
            <input type="hidden" name="view" value="<?php echo $_REQUEST['view'] ?>"/>
            <input type="hidden" name="eid" value="<?php echo $_REQUEST['eid'] ?>"/>
            <input type="hidden" name="generate" value="1"/>
          </fieldset>
        </div>
        <table id="contentTable" class="minor">
          <tr>
            <th scope="row"><?php echo translate('VideoFormat') ?></th>
            <td><?php echo buildSelect( "videoFormat", $videoFormats ) ?></td>
          </tr>
          <tr>
            <th scope="row"><?php echo translate('FrameRate') ?></th>
            <td><?php echo buildSelect( "rate", $rates ) ?></td>
          </tr>
          <tr>
            <th scope="row"><?php echo translate('OverwriteExisting') ?></th>
            <td><input type="checkbox" name="overwrite" value="1"<?php if ( isset($overwrite) ) { ?> checked="checked"<?php } ?>/></td>
          </tr>
        </table>
        <div id="contentButtons"><input type="submit" value="<?php echo translate('GenerateVideo') ?>"/></div>
      </form>
<?php
    if ( isset($videoFile) )
    {
        if ( $videoFile )
        {
?>
      <p class="infoText"><?php echo translate('VideoGenSucceeded') ?></p>
<?php
        }
        else
        {
?>
      <p class="errorText"><?php echo translate('VideoGenFailed') ?></p>
<?php
        }
    }
?>
<?php
    if ( count($videoFiles) )
    {
        if ( isset($_REQUEST['delete']) )
        {
            unlink( $videoFiles[$_REQUEST['delete']] );
            unset( $videoFiles[$_REQUEST['delete']] );
        }
    }
    if ( count($videoFiles) )
    {
?>
      <h3><?php echo translate('VideoGenFiles') ?></h3>
      <table class="major">
        <tr>
          <th><?php echo translate('Format') ?></th>
          <th><?php echo translate('Size') ?></th>
          <th><?php echo translate('Rate') ?></th>
          <th><?php echo translate('Scale') ?></th>
          <th><?php echo translate('Action') ?></th>
        </tr>
<?php
        if ( count($videoFiles) > 0 )
        {
            $index = 0;
            foreach ( $videoFiles as $file )
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
?>
        <tr>
          <td><?php echo $matches[4] ?></td>
          <td><?php echo filesize( $file ) ?></td>
          <td><?php echo $rateText ?></td>
          <td><?php echo $scaleText ?></td>
          <td><a href="?view=<?php echo $_REQUEST['view'] ?>&amp;eid=<?php echo $_REQUEST['eid'] ?>&amp;download=<?php echo $index ?>"><?php echo translate('View') ?></a>&nbsp;/&nbsp;<a href="?view=<?php echo $_REQUEST['view'] ?>&amp;eid=<?php echo $_REQUEST['eid'] ?>&amp;delete=<?php echo $index ?>"><?php echo translate('Delete') ?></a></td>
        </tr>
<?php
                $index++;
            }
        }
?>
      </table>
<?php
    }
    else
    {
?>
      <p class="warnText"><?php echo translate('VideoGenNoFiles') ?></p>
<?php
    }
?>
    </div>
  </div>
</body>
</html>
