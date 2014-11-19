<?php
//
// ZoneMinder web function library, $Date$, $Revision$
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

// Compatibility functions
if ( version_compare( phpversion(), "4.3.0", "<") )
{
    function ob_get_clean()
    {
        $buffer = ob_get_contents();
        ob_end_clean();
        return( $buffer );
    }
}

function userLogin( $username, $password="", $passwordHashed=false )
{
    global $user, $cookies;

	$sql = "select * from Users where Enabled = 1";
	$sql_values = NULL;
    if ( ZM_AUTH_TYPE == "builtin" )
    {
        if ( $passwordHashed ) {
            $sql .= " AND Username=? AND Password=?";
        } else {
            $sql .= " AND Username=? AND Password=password(?)";
        }
		$sql_values = array( $username, $password );
    } else {
        $sql .= " AND Username = ?";
		$sql_values = array( $username );
    }
    $_SESSION['username'] = $username;
    if ( ZM_AUTH_RELAY == "plain" )
    {
        // Need to save this in session
        $_SESSION['password'] = $password;
    }
    $_SESSION['remoteAddr'] = $_SERVER['REMOTE_ADDR']; // To help prevent session hijacking
    if ( $dbUser = dbFetchOne( $sql, NULL, $sql_values ) )
    {
        $_SESSION['user'] = $user = $dbUser;
        if ( ZM_AUTH_TYPE == "builtin" )
        {
            $_SESSION['passwordHash'] = $user['Password'];
        }
    }
    else
    {
        unset( $user );
    }
    if ( $cookies )
        session_write_close();
}

function userLogout()
{
    global $user;

    unset( $_SESSION['user'] );
    unset( $user );

    session_destroy();
}

function noCacheHeaders()
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
    header("Last-Modified: ".gmdate( "D, d M Y H:i:s" )." GMT"); // always modified
    header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");         // HTTP/1.0
}

function getAuthUser( $auth )
{
    if ( ZM_OPT_USE_AUTH && ZM_AUTH_RELAY == "hashed" && !empty($auth) )
    {
        $remoteAddr = "";
        if ( ZM_AUTH_HASH_IPS )
        {
            $remoteAddr = $_SERVER['REMOTE_ADDR'];
            if ( !$remoteAddr )
            {
                Error( "Can't determine remote address for authentication, using empty string" );
                $remoteAddr = "";
            }
        }

        $sql = "select Username, Password, Enabled, Stream+0, Events+0, Control+0, Monitors+0, System+0, MonitorIds from Users where Enabled = 1";
        foreach ( dbFetchAll( $sql ) as $user )
        {
            $now = time();
            for ( $i = 0; $i < 2; $i++, $now -= (60*60) ) // Try for last two hours
            {
                $time = localtime( $now );
                $authKey = ZM_AUTH_HASH_SECRET.$user['Username'].$user['Password'].$remoteAddr.$time[2].$time[3].$time[4].$time[5];
                $authHash = md5( $authKey );

                if ( $auth == $authHash )
                {
                    return( $user );
                }
            }
        }
    }
    Error( "Unable to authenticate user from auth hash '$auth'" );
    return( false );
}

function generateAuthHash( $useRemoteAddr )
{
    if ( ZM_OPT_USE_AUTH && ZM_AUTH_RELAY == "hashed" )
    {
        $time = localtime();
        if ( $useRemoteAddr )
        {
            $authKey = ZM_AUTH_HASH_SECRET.$_SESSION['username'].$_SESSION['passwordHash'].$_SESSION['remoteAddr'].$time[2].$time[3].$time[4].$time[5];
        }
        else
        {
            $authKey = ZM_AUTH_HASH_SECRET.$_SESSION['username'].$_SESSION['passwordHash'].$time[2].$time[3].$time[4].$time[5];
        }
        $auth = md5( $authKey );
    }
    else
    {
        $auth = "";
    }
    return( $auth );
}

function getStreamSrc( $args, $querySep='&amp;' )
{
    $streamSrc = ZM_BASE_URL.ZM_PATH_ZMS;

    if ( ZM_OPT_USE_AUTH )
    {
        if ( ZM_AUTH_RELAY == "hashed" )
        {
            $args[] = "auth=".generateAuthHash( ZM_AUTH_HASH_IPS );
        }
        elseif ( ZM_AUTH_RELAY == "plain" )
        {
            $args[] = "user=".$_SESSION['username'];
            $args[] = "pass=".$_SESSION['password'];
        }
        elseif ( ZM_AUTH_RELAY == "none" )
        {
            $args[] = "user=".$_SESSION['username'];
        }
    }
    if ( !in_array( "mode=single", $args ) && !empty($GLOBALS['connkey']) )
    {   
        $args[] = "connkey=".$GLOBALS['connkey'];
    }       
    if ( ZM_RAND_STREAM )
    {
        $args[] = "rand=".time();
    }

    if ( count($args) )
    {
        $streamSrc .= "?".join( $querySep, $args );
    }

    return( $streamSrc );
}

function getMimeType( $file )
{
    if ( function_exists('mime_content_type') )
    {
        return( mime_content_type( $file ) );
    }
    elseif ( function_exists('finfo_file') )
    {
        $finfo = finfo_open( FILEINFO_MIME );
        $mimeType = finfo_file( $finfo, $file );
        finfo_close($finfo);
        return( $mimeType );
    }
    return( trim( exec( 'file -bi '.escapeshellarg( $file ).' 2>/dev/null' ) ) );
}

function outputVideoStream( $id, $src, $width, $height, $format, $title="" )
{
    if ( file_exists( $src ) )
        $mimeType = getMimeType( $src );
    else
    {
        switch( $format )
        {
            case 'asf' :
                $mimeType = "video/x-ms-asf";
                break;
            case 'avi' :
            case 'wmv' :
                $mimeType = "video/x-msvideo";
                break;
            case 'mov' :
                $mimeType = "video/quicktime";
                break;
            case 'mpg' :
            case 'mpeg' :
                $mimeType = "video/mpeg";
                break;
            case 'swf' :
                $mimeType = "application/x-shockwave-flash";
                break;
            case '3gp' :
                $mimeType = "video/3gpp";
                break;
            default :
                $mimeType = "video/$format";
                break;
        }
    }
    if ( !$mimeType || ($mimeType == 'application/octet-stream') )
        $mimeType = 'video/'.$format;
    $objectTag = false;
    if ( ZM_WEB_USE_OBJECT_TAGS )
    {
        switch( $mimeType )
        {
            case "video/x-ms-asf" :
            case "video/x-msvideo" :
            case "video/mp4" :
            {
                if ( isWindows() )
                {
?>
<object id="<?= $id ?>" width="<?= validNum($width) ?>" height="<?= validNum($height) ?>"
classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902"
standby="Loading Microsoft Windows Media Player components..."
type="<?= $mimeType ?>">
<param name="FileName" value="<?= $src ?>"/>
<param name="autoStart" value="1"/>
<param name="showControls" value="0"/>
<embed type="<?= $mimeType ?>"
pluginspage="http://www.microsoft.com/Windows/MediaPlayer/"
src="<?= $src ?>"
name="<?= validHtmlStr($title) ?>"
width="<?= validNum($width) ?>"
height="<?= validInt($height) ?>"
autostart="1"
showcontrols="0">
</embed>
</object>
<?php
                    $objectTag = true;
                }
                break;
            }
            case "video/quicktime" :
            {
?>
<object id="<?= $id ?>" width="<?= $width ?>" height="<?= $height ?>"
classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B"
codebase="http://www.apple.com/qtactivex/qtplugin.cab"
type="<?= $mimeType ?>">
<param name="src" value="<?= $src ?>"/>
<param name="autoplay" VALUE="true"/>
<param name="controller" VALUE="false"/>
<embed type="<?= $mimeType ?>"
src="<?= $src ?>"
pluginspage="http://www.apple.com/quicktime/download/"
name="<?= validHtmlStr($title) ?>"
width="<?= validInt($width) ?>"
height="<?= validInt($height) ?>"
autoplay="true"
controller="true">
</embed>
</object>
<?php
                $objectTag = true;
                break;
            }
            case "application/x-shockwave-flash" :
            {
?>
<object id="<?= $id ?>" width="<?= $width ?>" height="<?= $height ?>"
classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,40,0"
type="<?= $mimeType ?>">
<param name="movie" value="<?= $src ?>"/>
<param name="quality" value="high"/>
<param name="bgcolor" value="#ffffff"/>
<embed type="<?= $mimeType ?>"
pluginspage="http://www.macromedia.com/go/getflashplayer"
src="<?= $src ?>"
name="<?= validHtmlStr($title) ?>"
width="<?= validInt($width) ?>"
height="<?= validInt($height) ?>"
quality="high"
bgcolor="#ffffff">
</embed>
</object>
<?php
                $objectTag = true;
                break;
            }
        }
    }
    if ( !$objectTag )
    {
?>
<embed<?= isset($mimeType)?(' type="'.$mimeType.'"'):"" ?> 
src="<?= $src ?>"
name="<?= validHtmlStr($title) ?>"
width="<?= validInt($width) ?>"
height="<?= validInt($height) ?>"
autostart="1"
autoplay="1"
showcontrols="0"
controller="0">
</embed>
<?php
    }
}

function outputImageStream( $id, $src, $width, $height, $title="" )
{
   if ( canStreamIframe() ) {
?>
<iframe id="<?= $id ?>" src="<?= $src ?>" alt="<?= validHtmlStr($title) ?>" width="<?= $width ?>" height="<?= $height ?>"/>
<?php
   } else {
?>
<img id="<?= $id ?>" src="<?= $src ?>" alt="<?= validHtmlStr($title) ?>" width="<?= $width ?>" height="<?= $height ?>"/>
<?php
   }
}

function outputControlStream( $src, $width, $height, $monitor, $scale, $target )
{
?>
<form name="ctrlForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" target="<?= $target ?>">
<input type="hidden" name="view" value="blank">
<input type="hidden" name="mid" value="<?= $monitor['Id'] ?>">
<input type="hidden" name="action" value="control">
<?php
                if ( $monitor['CanMoveMap'] ) 
                {
?>
<input type="hidden" name="control" value="moveMap">
<?php
                }
                elseif ( $monitor['CanMoveRel'] )
                {
?>
<input type="hidden" name="control" value="movePseudoMap">
<?php
                }
                elseif ( $monitor['CanMoveCon'] )
                {
?>
<input type="hidden" name="control" value="moveConMap">
<?php
                }
?>
<input type="hidden" name="scale" value="<?= $scale ?>">
<input type="image" src="<?= $src ?>" width="<?= $width ?>" height="<?= $height ?>">
</form>
<?php
}

function outputHelperStream( $id, $src, $width, $height, $title="" )
{
?>
<applet id="<?= $id ?>" code="com.charliemouse.cambozola.Viewer"
archive="<?= ZM_PATH_CAMBOZOLA ?>"
align="middle"
width="<?= $width ?>"
height="<?= $height ?>"
title="<?= $title ?>">
<param name="accessories" value="none"/>
<param name="url" value="<?= $src ?>"/>
</applet>
<?php
}

function outputImageStill( $id, $src, $width, $height, $title="" )
{
?>
<img id="<?= $id ?>" src="<?= $src ?>" alt="<?= $title ?>" width="<?= $width ?>" height="<?= $height ?>"/>
<?php
}

function outputControlStill( $src, $width, $height, $monitor, $scale, $target )
{
?>
<form name="ctrlForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>" target="<?= $target ?>">
<input type="hidden" name="view" value="blank">
<input type="hidden" name="mid" value="<?= $monitor['Id'] ?>">
<input type="hidden" name="action" value="control">
<?php
                if ( $monitor['CanMoveMap'] ) 
                {
?>
<input type="hidden" name="control" value="moveMap">
<?php
                }
                elseif ( $monitor['CanMoveRel'] )
                {
?>
<input type="hidden" name="control" value="movePseudoMap">
<?php
                }
                elseif ( $monitor['CanMoveCon'] )
                {
?>
<input type="hidden" name="control" value="moveConMap">
<?php
                }
?>
<input type="hidden" name="scale" value="<?= $scale ?>">
<input type="image" src="<?= $src ?>" width="<?= $width ?>" height="<?= $height ?>">
</form>
<?php
}

// Incoming args are shell-escaped. This function must escape any further arguments it cannot guarantee.
function getZmuCommand( $args )
{
    $zmuCommand = ZMU_PATH;

    if ( ZM_OPT_USE_AUTH )
    {
        if ( ZM_AUTH_RELAY == "hashed" )
        {
            $zmuCommand .= " -A ".generateAuthHash( false );
        }
        elseif ( ZM_AUTH_RELAY == "plain" )
        {
			$zmuCommand .= " -U " .escapeshellarg($_SESSION['username'])." -P ".escapeshellarg($_SESSION['password']);
        }
        elseif ( ZM_AUTH_RELAY == "none" )
        {
            $zmuCommand .= " -U ".escapeshellarg($_SESSION['username']);
        }
    }

    $zmuCommand .= $args;

    return( $zmuCommand );
}

function visibleMonitor( $mid )
{
    global $user;

    return( empty($user['MonitorIds']) || in_array( $mid, explode( ',', $user['MonitorIds'] ) ) );
}

function canView( $area, $mid=false )
{
    global $user;

    return( ($user[$area] == 'View' || $user[$area] == 'Edit') && ( !$mid || visibleMonitor( $mid ) ) );
}

function canEdit( $area, $mid=false )
{
    global $user;

    return( $user[$area] == 'Edit' && ( !$mid || visibleMonitor( $mid ) ) );
}

function getEventPath( $event )
{
    if ( ZM_USE_DEEP_STORAGE )
        $eventPath = $event['MonitorId'].'/'.strftime( "%y/%m/%d/%H/%M/%S", strtotime($event['StartTime']) );
    else
        $eventPath = $event['MonitorId'].'/'.$event['Id'];
    return( $eventPath );
}


function deletePath( $path )
{
    if ( is_dir( $path ) )
    {
        system( escapeshellcmd( "rm -rf ".$path ) );
    }
    else
    {
        unlink( $path );
    }
}

function deleteEvent( $eid, $mid=false )
{
    global $user;

    if ( !$mid )
        $mid = '*';
    if ( $user['Events'] == 'Edit' && !empty($eid) )
    {
        dbQuery( 'delete from Events where Id = ?', array($eid) );
        if ( !ZM_OPT_FAST_DELETE )
        {
            dbQuery( 'delete from Stats where EventId = ?', array($eid) );
            dbQuery( 'delete from Frames where EventId = ?', array($eid) );
            if ( ZM_USE_DEEP_STORAGE )
            {
                if ( $id_files = glob( ZM_DIR_EVENTS.'/'.$mid.'/*/*/*/.'.$eid ) )
                    $eventPath = preg_replace( "/\.$eid$/", readlink($id_files[0]), $id_files[0] );
                deletePath( $eventPath );
                deletePath( $id_files[0] );
                $pathParts = explode(  '/', $eventPath );
                for ( $i = count($pathParts)-1; $i >= 2; $i-- )
                {
                    $deletePath = join( '/', array_slice( $pathParts, 0, $i ) );
                    if ( !glob( $deletePath."/*" ) )
                    {
                        deletePath( $deletePath );
                    }
                }
            }
            else
            {
                $eventPath = ZM_DIR_EVENTS.'/'.$mid.'/'.$eid;
                deletePath( $eventPath );
            }
        }
    }
}

function makeLink( $url, $label, $condition=1, $options="" )
{
    $string = "";
    if ( $condition )
    {
        $string .= '<a href="'.$url.'"'.($options?(' '.$options):'').'>';
    }
    $string .= $label;
    if ( $condition )
    {
        $string .= '</a>';
    }
    return( $string );
}

function makePopupLink( $url, $winName, $winSize, $label, $condition=1, $options="" )
{
    $string = "";
    if ( $condition )
    {
        if ( is_array( $winSize ) )
            $popupParms = "'".$url."', '".$winName."', '".$winSize[0]."', ".$winSize[1].", ".$winSize[2];
        else
            $popupParms = "'".$url."', '".$winName."', '".$winSize."'";

        $string .= '<a href="'.$url.'" onclick="createPopup( '.$popupParms.' ); return( false );"'.($options?(' '.$options):'').'>';
    }
    $string .= $label;
    if ( $condition )
    {
        $string .= '</a>';
    }
    return( $string );
}

function makePopupButton( $url, $winName, $winSize, $buttonValue, $condition=1, $options="" )
{
    if ( is_array( $winSize ) )
        $popupParms = "'".$url."', '".$winName."', '".$winSize[0]."', ".$winSize[1].", ".$winSize[2];
    else
        $popupParms = "'".$url."', '".$winName."', '".$winSize."'";
    $string = '<input type="button" value="'.$buttonValue.'" onclick="createPopup( '.$popupParms.' ); return( false );"'.($condition?'':' disabled="disabled"').($options?(' '.$options):'').'/>';
    return( $string );
}

function truncText( $text, $length, $deslash=1 )
{       
    return( preg_replace( "/^(.{".$length.",}?)\b.*$/", "\\1&hellip;", ($deslash?stripslashes($text):$text) ) );       
}               

function buildSelect( $name, $contents, $behaviours=false )
{
    $value = "";
    if ( preg_match( "/^\s*(\w+)\s*(\[.*\])?\s*$/", $name, $matches ) && count($matches) > 2 )
    {
        $arr = $matches[1];
        if ( isset($GLOBALS[$arr]) )
            $value = $GLOBALS[$arr];
        elseif ( isset($_REQUEST[$arr]) )
            $value = $_REQUEST[$arr];
        if ( !preg_match_all( "/\[\s*['\"]?(\w+)[\"']?\s*\]/", $matches[2], $matches ) )
        {
            Fatal( "Can't parse selector '$name'" );
        }
        for ( $i = 0; $i < count($matches[1]); $i++ )
        {
            $idx = $matches[1][$i];
            $value = isset($value[$idx])?$value[$idx]:false;
        }
    }
    else
    {
        if ( isset($GLOBALS[$name]) )
            $value = $GLOBALS[$name];
        elseif ( isset($_REQUEST[$name]) )
            $value = $_REQUEST[$name];
    }
    ob_start();
    $behaviourText = "";
    if ( !empty($behaviours) )
    {
        if ( is_array($behaviours) )
        {
            foreach ( $behaviours as $event=>$action )
            {
                $behaviourText .= ' '.$event.'="'.$action.'"';
            }
        }
        else
        {
            $behaviourText = ' onchange="'.$behaviours.'"';
        }
    }
?>
<select name="<?= $name ?>" id="<?= $name ?>"<?= $behaviourText ?>>
<?php
    foreach ( $contents as $contentValue => $contentText )
    {
?>
<option value="<?= $contentValue ?>"<?php if ( $value == $contentValue ) { ?> selected="selected"<?php } ?>><?= validHtmlStr($contentText) ?></option>
<?php
    }
?>
</select>
<?php
    $html = ob_get_contents();
    ob_end_clean();
    
    return( $html );
}

function getFormChanges( $values, $newValues, $types=false, $columns=false )
{
    $changes = array();
    if ( !$types )
        $types = array();

    foreach( $newValues as $key=>$value )
    {
        if ( $columns && !$columns[$key] )
            continue;

        if ( !isset($types[$key]) )
            $types[$key] = false;
        switch( $types[$key] )
        {
            case 'set' :
            {
                if ( is_array( $newValues[$key] ) )
                {
                    if ( join(',',$newValues[$key]) != $values[$key] )
                    {
                        $changes[$key] = "$key = ".dbEscape(join(',',$newValues[$key]));
                    }
                }
                elseif ( $values[$key] )
                {
                    $changes[$key] = "$key = ''";
                }
                break;
            }
            case 'image' :
            {
                if ( is_array( $newValues[$key] ) )
                {
                    $imageData = getimagesize( $newValues[$key]['tmp_name'] );
                    $changes[$key.'Width'] = $key."Width = ".$imageData[0];
                    $changes[$key.'Height'] = $key."Height = ".$imageData[1];
                    $changes[$key.'Type'] = $key."Type = '".$newValues[$key]['type']."'";
                    $changes[$key.'Size'] = $key."Size = ".$newValues[$key]['size'];
                    ob_start();
                    readfile( $newValues[$key]['tmp_name'] );
                    $changes[$key] = $key." = ".dbEscape( ob_get_contents() );
                    ob_end_clean();
                }
                else
                {
                    $changes[$key] = "$key = ".dbEscape($value);
                }
                break;
            }
            case 'document' :
            {
                if ( is_array( $newValues[$key] ) )
                {
                    $imageData = getimagesize( $newValues[$key]['tmp_name'] );
                    $changes[$key.'Type'] = $key."Type = '".$newValues[$key]['type']."'";
                    $changes[$key.'Size'] = $key."Size = ".$newValues[$key]['size'];
                    ob_start();
                    readfile( $newValues[$key]['tmp_name'] );
                    $changes[$key] = $key." = ".dbEscape( ob_get_contents() );
                    ob_end_clean();
                }
                else
                {
                    $changes[$key] = "$key = ".dbEscape($value);
                }
                break;
            }
            case 'file' :
            {
                $changes[$key.'Type'] = $key."Type = ".dbEscape($newValues[$key]['type']);
                $changes[$key.'Size'] = $key."Size = ".dbEscape($newValues[$key]['size']);
                ob_start();
                readfile( $newValues[$key]['tmp_name'] );
                $changes[$key] = $key." = '".dbEscape( ob_get_contents() )."'";
                ob_end_clean();
                break;
            }
            case 'raw' :
            {
                if ( $values[$key] != $value )
                {
                    $changes[$key] = "$key = ".dbEscape($value);
                }
                break;
            }
            default :
            {
                if ( !isset($values[$key]) || ($values[$key] != $value) )
                {
                    $changes[$key] = "$key = ".dbEscape($value);
                }
                break;
            }
        }
    }
    foreach( $values as $key=>$value )
    {
        if ( !empty($columns[$key]) )
        {
            if ( !empty($types[$key]) )
            {
                if ( $types[$key] == 'toggle' )
                {
                    if ( !isset($newValues[$key]) && !empty($value) )
                    {
                        $changes[$key] = "$key = 0";
                    }
                }
                else if ( $types[$key] == 'set' )
                {
                    $changes[$key] = "$key = ''";
                }
            }
        }
    }
    return( $changes );
}

function getBrowser( &$browser, &$version )
{
    if ( isset($_SESSION['browser']) )
    {
        $browser = $_SESSION['browser'];
        $version = $_SESSION['version'];
    }
    else
    {
	if (( preg_match( '/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $logVersion)) || (preg_match( '/.*Trident.*rv:(.*?)(;|\))/', $_SERVER['HTTP_USER_AGENT'], $logVersion)))
        {
            $version = $logVersion[1];
            $browser = 'ie';
        }
        elseif ( preg_match( '/Chrome\/([0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $logVersion) )
        {
            $version = $logVersion[1];
            // Check for old version of Chrome with bug 5876
            if ( $version < 7 )
            {
                $browser = 'oldchrome';
            }
            else
            {
                $browser = 'chrome';
            }
        }
        elseif ( preg_match( '/Safari\/([0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $logVersion) )
        {
            $version = $logVersion[1];
            $browser = 'safari';
        }
        elseif ( preg_match( '/Opera[ \/]([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'], $logVersion) )
        {
            $version = $logVersion[1];
            $browser = 'opera';
        }
        elseif ( preg_match( '/Konqueror\/([0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $logVersion) )
        {
            $version = $logVersion[1];
            $browser = 'konqueror';
        }
        elseif ( preg_match( '/Mozilla\/([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT'], $logVersion) )
        {
            $version = $logVersion[1];
            $browser = 'mozilla';
        }
        else
        {
            $version = 0;
            $browser = 'unknown';
        }
        $_SESSION['browser'] = $browser;
        $_SESSION['version'] = $version;
    }
}

function isMozilla()
{
    getBrowser( $browser, $version );

    return( $browser == "mozilla" );
}

function isKonqueror()
{
   getBrowser( $browser, $version );

   return( $browser == "konqueror" );
}

function isInternetExplorer()
{
    getBrowser( $browser, $version );

    return( $browser == "ie" );
}

function isOldChrome()
{
    getBrowser( $browser, $version );

    return( $browser == "oldchrome" );
}

function isChrome()
{
    getBrowser( $browser, $version );

    return( $browser == "chrome" );
}

function isOpera()
{
    getBrowser( $browser, $version );

    return( $browser == "opera" );
}

function isSafari()
{
    getBrowser( $browser, $version );

    return( $browser == "safari" );
}

function isWindows()
{
    return ( preg_match( '/Win/', $_SERVER['HTTP_USER_AGENT'] ) );
}

function canStreamIframe()
{
   return( isKonqueror() );
}

function canStreamNative()
{
   // Old versions of Chrome can display the stream, but then it blocks everything else (Chrome bug 5876)
   return( ZM_WEB_CAN_STREAM == "yes" || ( ZM_WEB_CAN_STREAM == "auto" && (!isInternetExplorer() && !isOldChrome()) ) );
}

function canStreamApplet()
{
    if ( (ZM_OPT_CAMBOZOLA && !file_exists( ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA )) )
    {
        Warning ( "ZM_OPT_CAMBOZOLA is enabled, but the system cannot find ".ZM_PATH_WEB."/".ZM_PATH_CAMBOZOLA );
    }

    return( (ZM_OPT_CAMBOZOLA && file_exists( ZM_PATH_WEB.'/'.ZM_PATH_CAMBOZOLA )) );
}

function canStream()
{
    return( canStreamNative() | canStreamApplet() );
}

function packageControl( $command )
{
    $string = ZM_PATH_BIN.'/zmpkg.pl '.escapeshellarg( $command );
    $string .= " 2>/dev/null >&- <&- >/dev/null";
    exec( $string );
}

function daemonControl( $command, $daemon=false, $args=false )
{
    $string = ZM_PATH_BIN."/zmdc.pl $command";
    if ( $daemon )
    {
        $string .= " $daemon";
        if ( $args )
        {
            $string .= " $args";
        }
    }
    $string .= " 2>/dev/null >&- <&- >/dev/null";
    exec( $string );
}

function zmcControl( $monitor, $mode=false )
{
	$row = NULL;
    if ( $monitor['Type'] == "Local" )
    {
		$row = dbFetchOne( "select count(if(Function!='None',1,NULL)) as ActiveCount from Monitors where Device = ?", NULL, array($monitor['Device']) );
        $zmcArgs = "-d ".$monitor['Device'];
    }
    else
    {
		$row = dbFetchOne( "select count(if(Function!='None',1,NULL)) as ActiveCount from Monitors where Id = ?", NULL, array($monitor['Id']) );
        $zmcArgs = "-m ".$monitor['Id'];
    }
    $activeCount = $row['ActiveCount'];

    if ( !$activeCount || $mode == "stop" )
    {
        daemonControl( "stop", "zmc", $zmcArgs );
    }
    else
    {
        if ( $mode == "restart" )
        {
            daemonControl( "stop", "zmc", $zmcArgs );
        }
        daemonControl( "start", "zmc", $zmcArgs );
    }
}

function zmaControl( $monitor, $mode=false )
{
    if ( !is_array( $monitor ) )
    {
        $monitor = dbFetchOne( "select C.*, M.* from Monitors as M left join Controls as C on (M.ControlId = C.Id ) where M.Id=?", NULL, array($monitor) );
    }
    if ( !$monitor || $monitor['Function'] == 'None' || $monitor['Function'] == 'Monitor' || $mode == "stop" )
    {
        if ( ZM_OPT_CONTROL )
        {
            daemonControl( "stop", "zmtrack.pl", "-m ".$monitor['Id'] );
        }
        daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
        if ( ZM_OPT_FRAME_SERVER )
        {
            daemonControl( "stop", "zmf", "-m ".$monitor['Id'] );
        }
    }
    else
    {
        if ( $mode == "restart" )
        {
            if ( ZM_OPT_CONTROL )
            {
                daemonControl( "stop", "zmtrack.pl", "-m ".$monitor['Id'] );
            }
            daemonControl( "stop", "zma", "-m ".$monitor['Id'] );
            if ( ZM_OPT_FRAME_SERVER )
            {
                daemonControl( "stop", "zmf", "-m ".$monitor['Id'] );
            }
        }
        if ( ZM_OPT_FRAME_SERVER )
        {
            daemonControl( "start", "zmf", "-m ".$monitor['Id'] );
        }
        daemonControl( "start", "zma", "-m ".$monitor['Id'] );
        if ( ZM_OPT_CONTROL && $monitor['Controllable'] && $monitor['TrackMotion'] && ( $monitor['Function'] == 'Modect' || $monitor['Function'] == 'Mocord' ) )
        {
            daemonControl( "start", "zmtrack.pl", "-m ".$monitor['Id'] );
        }
        if ( $mode == "reload" )
        {
            daemonControl( "reload", "zma", "-m ".$monitor['Id'] );
        }
    }
}

function initDaemonStatus()
{
    global $daemon_status;

    if ( !isset($daemon_status) )
    {
        if ( daemonCheck() )
        {
            $string = ZM_PATH_BIN."/zmdc.pl status";
            $daemon_status = shell_exec( $string );
        }
        else
        {
            $daemon_status = "";
        }
    }
}

function daemonStatus( $daemon, $args=false )
{
    global $daemon_status;

    initDaemonStatus();
    
    $string = "$daemon";
    if ( $args )
        $string .= " $args";
    return( strpos( $daemon_status, "'$string' running" ) !== false );
}

function zmcStatus( $monitor )
{
    if ( $monitor['Type'] == 'Local' )
    {
        $zmcArgs = "-d ".$monitor['Device'];
    }
    else
    {
        $zmcArgs = "-m ".$monitor['Id'];
    }
    return( daemonStatus( "zmc", $zmcArgs ) );
}

function zmaStatus( $monitor )
{
    if ( is_array( $monitor ) )
    {
        $monitor = $monitor['Id'];
    }
    return( daemonStatus( "zma", "-m $monitor" ) );
}

function daemonCheck( $daemon=false, $args=false )
{
    $string = ZM_PATH_BIN."/zmdc.pl check";
    if ( $daemon )
    {
        $string .= " $daemon";
        if ( $args )
            $string .= " $args";
    }
    $result = exec( $string );
    return( preg_match( '/running/', $result ) );
}

function zmcCheck( $monitor )
{
    if ( $monitor['Type'] == 'Local' )
    {
        $zmcArgs = "-d ".$monitor['Device'];
    }
    else
    {
        $zmcArgs = "-m ".$monitor['Id'];
    }
    return( daemonCheck( "zmc", $zmcArgs ) );
}

function zmaCheck( $monitor )
{
    if ( is_array( $monitor ) )
    {
        $monitor = $monitor['Id'];
    }
    return( daemonCheck( "zma", "-m $monitor" ) );
}

function getImageSrc( $event, $frame, $scale=SCALE_BASE, $captureOnly=false, $overwrite=false )
{
    $eventPath = getEventPath( $event );

    if ( !is_array($frame) )
        $frame = array( 'FrameId'=>$frame, 'Type'=>'' );

    //echo "S:$scale, CO:$captureOnly<br>";
    $captImage = sprintf( "%0".ZM_EVENT_IMAGE_DIGITS."d-capture.jpg", $frame['FrameId'] );
    $captPath = $eventPath.'/'.$captImage;
    $thumbCaptPath = ZM_DIR_IMAGES.'/'.$event['Id'].'-'.$captImage;
    //echo "CI:$captImage, CP:$captPath, TCP:$thumbCaptPath<br>";

    $analImage = sprintf( "%0".ZM_EVENT_IMAGE_DIGITS."d-analyse.jpg", $frame['FrameId'] );
    $analPath = $eventPath.'/'.$analImage;
    $analFile =  ZM_DIR_EVENTS."/".$analPath;
    $thumbAnalPath = ZM_DIR_IMAGES.'/'.$event['Id'].'-'.$analImage;
    //echo "AI:$analImage, AP:$analPath, TAP:$thumbAnalPath<br>";

    $alarmFrame = $frame['Type']=='Alarm';

    $hasAnalImage = $alarmFrame && file_exists( $analFile ) && filesize( $analFile );
    $isAnalImage = $hasAnalImage && !$captureOnly;

    if ( !ZM_WEB_SCALE_THUMBS || $scale >= SCALE_BASE || !function_exists( 'imagecreatefromjpeg' ) )
    {
        $imagePath = $thumbPath = $isAnalImage?$analPath:$captPath;
        $imageFile = ZM_DIR_EVENTS."/".$imagePath;
        $thumbFile = ZM_DIR_EVENTS."/".$thumbPath;
    }
    else
    {
        if ( version_compare( phpversion(), "4.3.10", ">=") )
            $fraction = sprintf( "%.3F", $scale/SCALE_BASE );
        else
            $fraction = sprintf( "%.3f", $scale/SCALE_BASE );
        $scale = (int)round( $scale );

        $thumbCaptPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $thumbCaptPath );
        $thumbAnalPath = preg_replace( "/\.jpg$/", "-$scale.jpg", $thumbAnalPath );

        if ( $isAnalImage )
        {
            $imagePath = $analPath;
            $thumbPath = $thumbAnalPath;
        }
        else
        {
            $imagePath = $captPath;
            $thumbPath = $thumbCaptPath;
        }

        $imageFile = ZM_DIR_EVENTS."/".$imagePath;
        //$thumbFile = ZM_DIR_EVENTS."/".$thumbPath;
        $thumbFile = $thumbPath;
        if ( $overwrite || !file_exists( $thumbFile ) || !filesize( $thumbFile ) )
        {
            // Get new dimensions
            list( $imageWidth, $imageHeight ) = getimagesize( $imageFile );
            $thumbWidth = $imageWidth * $fraction;
            $thumbHeight = $imageHeight * $fraction;

            // Resample
            $thumbImage = imagecreatetruecolor( $thumbWidth, $thumbHeight );
            $image = imagecreatefromjpeg( $imageFile );
            imagecopyresampled( $thumbImage, $image, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $imageWidth, $imageHeight );

            if ( !imagejpeg( $thumbImage, $thumbFile ) )
                Error( "Can't create thumbnail '$thumbPath'" );
        }
    }

    $imageData = array(
        'eventPath' => $eventPath,
        'imagePath' => $imagePath,
        'thumbPath' => $thumbPath,
        'imageFile' => $imageFile,
        'thumbFile' => $thumbFile,
        'imageClass' => $alarmFrame?"alarm":"normal",
        'isAnalImage' => $isAnalImage,
        'hasAnalImage' => $hasAnalImage,
    );

    //echo "IP:$imagePath<br>";
    //echo "TP:$thumbPath<br>";
    return( $imageData );
}

function viewImagePath( $path, $querySep='&amp;' )
{
    if ( strncmp( $path, ZM_DIR_IMAGES, strlen(ZM_DIR_IMAGES) ) == 0 )
    {
        // Thumbnails
        return( $path );
    }
    elseif ( strpos( ZM_DIR_EVENTS, '/' ) === 0 )
    {
        return( '?view=image'.$querySep.'path='.$path );
    }
    return( ZM_DIR_EVENTS.'/'.$path );
}

function createListThumbnail( $event, $overwrite=false )
{
    if ( !($frame = dbFetchOne( "SELECT * FROM Frames WHERE EventId=? AND Score=? ORDER BY FrameId LIMIT 1", NULL, array( $event['Id'], $event['MaxScore'] ) )) )
        return( false );

    $frameId = $frame['FrameId'];

    if ( ZM_WEB_LIST_THUMB_WIDTH )
    {
        $thumbWidth = ZM_WEB_LIST_THUMB_WIDTH;
        $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_WIDTH)/$event['Width'];
        $thumbHeight = reScale( $event['Height'], $scale );
    }
    elseif ( ZM_WEB_LIST_THUMB_HEIGHT )
    {
        $thumbHeight = ZM_WEB_LIST_THUMB_HEIGHT;
        $scale = (SCALE_BASE*ZM_WEB_LIST_THUMB_HEIGHT)/$event['Height'];
        $thumbWidth = reScale( $event['Width'], $scale );
    }
    else
    {
        Fatal( "No thumbnail width or height specified, please check in Options->Web" );
    }

    $imageData = getImageSrc( $event, $frame, $scale, false, $overwrite );
    $thumbData = $frame;
    $thumbData['Path'] = $imageData['thumbPath'];
    $thumbData['Width'] = (int)$thumbWidth;
    $thumbData['Height'] = (int)$thumbHeight;

    return( $thumbData );
}

function createVideo( $event, $format, $rate, $scale, $overwrite=false )
{
    $command = ZM_PATH_BIN."/zmvideo.pl -e ".$event['Id']." -f ".$format." -r ".sprintf( "%.2F", ($rate/RATE_BASE) );
    if ( preg_match( '/\d+x\d+/', $scale ) )
        $command .= " -S ".$scale;
    else
        if ( version_compare( phpversion(), "4.3.10", ">=") )
            $command .= " -s ".sprintf( "%.2F", ($scale/SCALE_BASE) );
        else
            $command .= " -s ".sprintf( "%.2f", ($scale/SCALE_BASE) );
    if ( $overwrite )
        $command .= " -o";
    $result = exec( escapeshellcmd( $command ), $output, $status );
    return( $status?"":rtrim($result) );
}

function executeFilter( $filter )
{
    $command = ZM_PATH_BIN."/zmfilter.pl --filter ".escapeshellarg($filter);
    $result = exec( $command, $output, $status );
    dbQuery( "delete from Filters where Name like '_TempFilter%'" );
    return( $status );
}

function reScale( $dimension, $dummy )
{
    for ( $i = 1; $i < func_num_args(); $i++ )
    {
        $scale = func_get_arg( $i );
        if ( !empty($scale) && $scale != SCALE_BASE )
            $dimension = (int)(($dimension*$scale)/SCALE_BASE);
    }
    return( $dimension );
}

function deScale( $dimension, $dummy )
{
    for ( $i = 1; $i < func_num_args(); $i++ )
    {
        $scale = func_get_arg( $i );
        if ( !empty($scale) && $scale != SCALE_BASE )
            $dimension = (int)(($dimension*SCALE_BASE)/$scale);
    }
    return( $dimension );
}

function monitorLimitSql()
{
    global $user;
    if ( !empty($user['MonitorIds']) )
        $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', $user['MonitorIds'] ) ).")";
    else
        $midSql = '';
    return( $midSql );
}

function parseSort( $saveToSession=false, $querySep='&amp;' )
{
    global $sortQuery, $sortColumn, $sortOrder; // Outputs

    if ( empty($_REQUEST['sort_field']) )
    {
        $_REQUEST['sort_field'] = ZM_WEB_EVENT_SORT_FIELD;
        $_REQUEST['sort_asc'] = (ZM_WEB_EVENT_SORT_ORDER == "asc");
    }
    switch( $_REQUEST['sort_field'] )
    {
        case 'Id' :
            $sortColumn = "E.Id";
            break;
        case 'MonitorName' :
            $sortColumn = "M.Name";
            break;
        case 'Name' :
            $sortColumn = "E.Name";
            break;
        case 'Cause' :
            $sortColumn = "E.Cause";
            break;
        case 'DateTime' :
            $_REQUEST['sort_field'] = 'StartTime';
        case 'StartTime' :
            $sortColumn = "E.StartTime";
            break;
        case 'Length' :
            $sortColumn = "E.Length";
            break;
        case 'Frames' :
            $sortColumn = "E.Frames";
            break;
        case 'AlarmFrames' :
            $sortColumn = "E.AlarmFrames";
            break;
        case 'TotScore' :
            $sortColumn = "E.TotScore";
            break;
        case 'AvgScore' :
            $sortColumn = "E.AvgScore";
            break;
        case 'MaxScore' :
            $sortColumn = "E.MaxScore";
            break;
        default:
            $sortColumn = "E.StartTime";
            break;
    }
    $sortOrder = $_REQUEST['sort_asc']?"asc":"desc";
    if ( !$_REQUEST['sort_asc'] )
        $_REQUEST['sort_asc'] = 0;
    $sortQuery = $querySep."sort_field=".validHtmlStr($_REQUEST['sort_field']).$querySep."sort_asc=".validHtmlStr($_REQUEST['sort_asc']);
    if ( !isset($_REQUEST['limit']) )
        $_REQUEST['limit'] = "";
    if ( $saveToSession )
    {
        $_SESSION['sort_field'] = validHtmlStr($_REQUEST['sort_field']);
        $_SESSION['sort_asc'] = validHtmlStr($_REQUEST['sort_asc']);
    }
}

function parseFilter( &$filter, $saveToSession=false, $querySep='&amp;' )
{
    $filter['query'] = ''; 
    $filter['sql'] = '';
    $filter['fields'] = '';

    if ( isset($filter['terms']) && count($filter['terms']) )
    {
        for ( $i = 0; $i < count($filter['terms']); $i++ )
        {
            if ( isset($filter['terms'][$i]['cnj']) )
            {
                $filter['query'] .= $querySep."filter[terms][$i][cnj]=".urlencode($filter['terms'][$i]['cnj']);
                $filter['sql'] .= " ".$filter['terms'][$i]['cnj']." ";
                $filter['fields'] .= "<input type=\"hidden\" name=\"filter[terms][$i][cnj]\" value=\"".htmlspecialchars($filter['terms'][$i]['cnj'])."\"/>\n";
            }
            if ( isset($filter['terms'][$i]['obr']) )
            {
                $filter['query'] .= $querySep."filter[terms][$i][obr]=".urlencode($filter['terms'][$i]['obr']);
                $filter['sql'] .= " ".str_repeat( "(", $filter['terms'][$i]['obr'] )." ";
                $filter['fields'] .= "<input type=\"hidden\" name=\"filter[terms][$i][obr]\" value=\"".htmlspecialchars($filter['terms'][$i]['obr'])."\"/>\n";
            }
            if ( isset($filter['terms'][$i]['attr']) )
            {
                $filter['query'] .= $querySep."filter[terms][$i][attr]=".urlencode($filter['terms'][$i]['attr']);
                $filter['fields'] .= "<input type=\"hidden\" name=\"filter[terms][$i][attr]\" value=\"".htmlspecialchars($filter['terms'][$i]['attr'])."\"/>\n";
                switch ( $filter['terms'][$i]['attr'] )
                {
                    case 'MonitorName':
                        $filter['sql'] .= 'M.'.preg_replace( '/^Monitor/', '', $filter['terms'][$i]['attr'] );
                        break;
                    case 'DateTime':
                        $filter['sql'] .= "E.StartTime";
                        break;
                    case 'Date':
                        $filter['sql'] .= "to_days( E.StartTime )";
                        break;
                    case 'Time':
                        $filter['sql'] .= "extract( hour_second from E.StartTime )";
                        break;
                    case 'Weekday':
                        $filter['sql'] .= "weekday( E.StartTime )";
                        break;
                    case 'Id':
                    case 'Name':
                    case 'MonitorId':
                    case 'Length':
                    case 'Frames':
                    case 'AlarmFrames':
                    case 'TotScore':
                    case 'AvgScore':
                    case 'MaxScore':
                    case 'Cause':
                    case 'Notes':
                    case 'Archived':
                        $filter['sql'] .= 'E.'.$filter['terms'][$i]['attr'];
                        break;
                    case 'DiskPercent':
                        $filter['sql'] .= getDiskPercent();
                        break;
                    case 'DiskBlocks':
                        $filter['sql'] .= getDiskBlocks();
                        break;
                    case 'SystemLoad':
                        $filter['sql'] .= getLoad();
                        break;
                }
                $valueList = array();
                foreach ( preg_split( '/["\'\s]*?,["\'\s]*?/', preg_replace( '/^["\']+?(.+)["\']+?$/', '$1', $filter['terms'][$i]['val'] ) ) as $value )
                {
                    switch ( $filter['terms'][$i]['attr'] )
                    {
                        case 'MonitorName':
                        case 'Name':
                        case 'Cause':
                        case 'Notes':
                            $value = dbEscape($value);
                            break;
                        case 'DateTime':
                            $value = "'".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."'";
                            break;
                        case 'Date':
                            $value = "to_days( '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
                            break;
                        case 'Time':
                            $value = "extract( hour_second from '".strftime( STRF_FMT_DATETIME_DB, strtotime( $value ) )."' )";
                            break;
                        default :
                            $value = dbEscape($value);
                            break;
                    }
                    $valueList[] = $value;
                }

                switch ( $filter['terms'][$i]['op'] )
                {
                    case '=' :
                    case '!=' :
                    case '>=' :
                    case '>' :
                    case '<' :
                    case '<=' :
                        $filter['sql'] .= " ".$filter['terms'][$i]['op']." $value";
                        break;
                    case '=~' :
                        $filter['sql'] .= " regexp ".$value;
                        break;
                    case '!~' :
                        $filter['sql'] .= " not regexp ".$value;
                        break;
                    case '=[]' :
                        $filter['sql'] .= " in (".join( ",", $valueList ).")";
                        break;
                    case '![]' :
                        $filter['sql'] .= " not in (".join( ",", $valueList ).")";
                        break;
                }

                $filter['query'] .= $querySep."filter[terms][$i][op]=".urlencode($filter['terms'][$i]['op']);
                $filter['fields'] .= "<input type=\"hidden\" name=\"filter[terms][$i][op]\" value=\"".htmlspecialchars($filter['terms'][$i]['op'])."\"/>\n";
                $filter['query'] .= $querySep."filter[terms][$i][val]=".urlencode($filter['terms'][$i]['val']);
                $filter['fields'] .= "<input type=\"hidden\" name=\"filter[terms][$i][val]\" value=\"".htmlspecialchars($filter['terms'][$i]['val'])."\"/>\n";
            }
            if ( isset($filter['terms'][$i]['cbr']) )
            {
                $filter['query'] .= $querySep."filter[terms][$i][cbr]=".urlencode($filter['terms'][$i]['cbr']);
                $filter['sql'] .= " ".str_repeat( ")", $filter['terms'][$i]['cbr'] )." ";
                $filter['fields'] .= "<input type=\"hidden\" name=\"filter[terms][$i][cbr]\" value=\"".htmlspecialchars($filter['terms'][$i]['cbr'])."\"/>\n";
            }
        }
        if ( $filter['sql'] )
            $filter['sql'] = " and ( ".$filter['sql']." )";
        if ( $saveToSession )
        {
            $_SESSION['filter'] = $filter;
        }
    }
}

function addFilterTerm( $filter, $position, $term=false )
{
    if ( $position < 0 )
        $position = 0;
    elseif( $position > count($filter['terms']) )
        $position = count($filter['terms']);
    if ( $term && $position == 0 )
        unset( $term['cnj'] );
    array_splice( $filter['terms'], $position, 0, array( $term?$term:array() ) );
    
    return( $filter );
}

function delFilterTerm( $filter, $position )
{
    if ( $position < 0 )
        $position = 0;
    elseif( $position >= count($filter['terms']) )
        $position = count($filter['terms']);
    array_splice( $filter['terms'], $position, 1 );
    
    return( $filter );
}

function getPagination( $pages, $page, $maxShortcuts, $query, $querySep='&amp;' )
{
    global $view;

    $pageText = "";
    if ( $pages > 1 )
    {
        if ( $page )
        {
            if ( $page < 0 )
                $page = 1;
            if ( $page > $pages )
                $page = $pages;

            if ( $page > 1 )
            {
                if ( false && $page > 2 )
                {
                    $pageText .= '<a href="?view='.$view.$querySep.'page=1'.$query.'">&lt;&lt;</a>';
                }
                $pageText .= '<a href="?view='.$view.$querySep.'page='.($page-1).$query.'">&lt;</a>';

                $newPages = array();
                $pagesUsed = array();
                $lo_exp = max(2,log($page-1)/log($maxShortcuts));
                for ( $i = 0; $i < $maxShortcuts; $i++ )
                {
                    $newPage = round($page-pow($lo_exp,$i));
                    if ( isset($pagesUsed[$newPage]) )
                        continue;
                    if ( $newPage <= 1 )
                        break;
                    $pagesUsed[$newPage] = true;
                    array_unshift( $newPages, $newPage );
                }
                if ( !isset($pagesUsed[1]) )
                    array_unshift( $newPages, 1 );

                foreach ( $newPages as $newPage )
                {
                    $pageText .= '<a href="?view='.$view.$querySep.'page='.$newPage.$query.'">'.$newPage.'</a>&nbsp;';
                }

            }
            $pageText .= '-&nbsp;'.$page.'&nbsp;-';
            if ( $page < $pages )
            {
                $newPages = array();
                $pagesUsed = array();
                $hi_exp = max(2,log($pages-$page)/log($maxShortcuts));
                for ( $i = 0; $i < $maxShortcuts; $i++ )
                {
                    $newPage = round($page+pow($hi_exp,$i));
                    if ( isset($pagesUsed[$newPage]) )
                        continue;
                    if ( $newPage > $pages )
                        break;
                    $pagesUsed[$newPage] = true;
                    array_push( $newPages, $newPage );
                }
                if ( !isset($pagesUsed[$pages]) )
                    array_push( $newPages, $pages );

                foreach ( $newPages as $newPage )
                {
                    $pageText .= '&nbsp;<a href="?view='.$view.$querySep.'page='.$newPage.$query.'">'.$newPage.'</a>';
                }
                $pageText .= '<a href="?view='.$view.$querySep.'page='.($page+1).$query.'">&gt;</a>';
                if ( false && $page < ($pages-1) )
                {
                    $pageText .= '<a href="?view='.$view.$querySep.'page='.$pages.$query.'">&gt;&gt;</a>';
                }
            }
        }
    }
    return( $pageText );
}

function sortHeader( $field, $querySep='&amp;' )
{
    global $view;
    return( '?view='.$view.$querySep.'page=1'.$_REQUEST['filter']['query'].$querySep.'sort_field='.$field.$querySep.'sort_asc='.($_REQUEST['sort_field'] == $field?!$_REQUEST['sort_asc']:0).$querySep.'limit='.$_REQUEST['limit'] );
}

function sortTag( $field )
{
    if ( $_REQUEST['sort_field'] == $field )
        if ( $_REQUEST['sort_asc'] )
            return( "(^)" );
        else
            return( "(v)" );
    return( false );
}

function getLoad()
{
    $uptime = shell_exec( 'uptime' );
    $load = '';
    if ( preg_match( '/load average: ([\d.]+)/', $uptime, $matches ) )
        $load = $matches[1];
    return( $load );
}

function getDiskPercent()
{
    $df = shell_exec( 'df '.ZM_DIR_EVENTS );
    $space = -1;
    if ( preg_match( '/\s(\d+)%/ms', $df, $matches ) )
        $space = $matches[1];
    return( $space );
}

function getDiskBlocks()
{
    $df = shell_exec( 'df '.ZM_DIR_EVENTS );
    $space = -1;
    if ( preg_match( '/\s(\d+)\s+\d+\s+\d+%/ms', $df, $matches ) )
        $space = $matches[1];
    return( $space );
}

// Function to fix a problem whereby the built in PHP session handling 
// features want to put the sid as a hidden field after the form or 
// fieldset tag, neither of which will work with strict XHTML Basic.
function sidField()
{
    if ( SID )
    {
        list( $sessname, $sessid ) = explode( "=", SID );
?>
<input type="hidden" name="<?= $sessname ?>" value="<?= $sessid ?>"/>
<?php
    }
}

function verNum( $version )
{
    $vNum = "";
    $maxFields = 3;
    $vFields = explode( ".", $version );
    array_splice( $vFields, $maxFields );
    while ( count($vFields) < $maxFields )
    {
        $vFields[] = 0;
    }
    foreach ( $vFields as $vField )
    {
        $vField = sprintf( "%02d", $vField );
        while ( strlen($vField) < 2 )
        {
            $vField = "0".$vField;
        }
        $vNum .= $vField;
    }
    return( $vNum );
}

function fixSequences()
{
    $sequence = 1;
    $sql = "select * from Monitors order by Sequence asc, Id asc";
    foreach( dbFetchAll( $sql ) as $monitor )
    {
        if ( $monitor['Sequence'] != $sequence )
        {
            dbQuery( 'update Monitors set Sequence = ? WHERE Id=?', array( $sequence, $monitor['Id'] ) );
        }
        $sequence++;
    }
}

function firstSet()
{
    foreach ( func_get_args() as $arg )
    {
        if ( !empty( $arg ) )
            return( $arg );
    }
}

function linesIntersect( $line1, $line2 )
{
    global $debug;

    $min_x1 = min( $line1[0]['x'], $line1[1]['x'] );
    $max_x1 = max( $line1[0]['x'], $line1[1]['x'] );
    $min_x2 = min( $line2[0]['x'], $line2[1]['x'] );
    $max_x2 = max( $line2[0]['x'], $line2[1]['x'] );
    $min_y1 = min( $line1[0]['y'], $line1[1]['y'] );
    $max_y1 = max( $line1[0]['y'], $line1[1]['y'] );
    $min_y2 = min( $line2[0]['y'], $line2[1]['y'] );
    $max_y2 = max( $line2[0]['y'], $line2[1]['y'] );

    // Checking if bounding boxes intersect
    if ( $max_x1 < $min_x2 || $max_x2 < $min_x1 ||$max_y1 < $min_y2 || $max_y2 < $min_y1 )
    {
        if ( $debug ) echo "Not intersecting, out of bounds<br>";
        return( false );
    }

    $dx1 = $line1[1]['x'] - $line1[0]['x'];
    $dy1 = $line1[1]['y'] - $line1[0]['y'];
    $dx2 = $line2[1]['x'] - $line2[0]['x'];
    $dy2 = $line2[1]['y'] - $line2[0]['y'];

    if ( $dx1 )
    {
        $m1 = $dy1/$dx1;
        $b1 = $line1[0]['y'] - ($m1 * $line1[0]['x']);
    }
    else
    {
        $b1 = $line1[0]['y'];
    }
    if ( $dx2 )
    {
        $m2 = $dy2/$dx2;
        $b2 = $line2[0]['y'] - ($m2 * $line2[0]['x']);
    }
    else
    {
        $b2 = $line2[0]['y'];
    }

    if ( $dx1 && $dx2 ) // Both not vertical
    {
        if ( $m1 != $m2 ) // Not parallel or colinear
        {
            $x = ( $b2 - $b1 ) / ( $m1 - $m2 );

            if ( $x >= $min_x1 && $x <= $max_x1 && $x >= $min_x2 && $x <= $max_x2 )
            {
                if ( $debug ) echo "Intersecting, at x $x<br>";
                return( true );
            }
            else
            {
                if ( $debug ) echo "Not intersecting, out of range at x $x<br>";
                return( false );
            }
        }
        elseif ( $b1 == $b2 )
        {
            // Colinear, must overlap due to box check, intersect? 
            if ( $debug ) echo "Intersecting, colinear<br>";
            return( true );
        }
        else
        {
            // Parallel
            if ( $debug ) echo "Not intersecting, parallel<br>";
            return( false );
        }
    }
    elseif ( !$dx1 ) // Line 1 is vertical
    {
        $y = ( $m2 * $line1[0]['x'] ) * $b2;
        if ( $y >= $min_y1 && $y <= $max_y1 )
        {
            if ( $debug ) echo "Intersecting, at y $y<br>";
            return( true );
        }
        else
        {
            if ( $debug ) echo "Not intersecting, out of range at y $y<br>";
            return( false );
        }
    }
    elseif ( !$dx2 ) // Line 2 is vertical
    {
        $y = ( $m1 * $line2[0]['x'] ) * $b1;
        if ( $y >= $min_y2 && $y <= $max_y2 )
        {
            if ( $debug ) echo "Intersecting, at y $y<br>";
            return( true );
        }
        else
        {
            if ( $debug ) echo "Not intersecting, out of range at y $y<br>";
            return( false );
        }
    }
    else // Both lines are vertical
    {
        if ( $line1[0]['x'] == $line2[0]['x'] )
        {
            // Colinear, must overlap due to box check, intersect? 
            if ( $debug ) echo "Intersecting, vertical, colinear<br>";
            return( true );
        }
        else
        {
            // Parallel
            if ( $debug ) echo "Not intersecting, vertical, parallel<br>";
            return( false );
        }
    }
    if ( $debug ) echo "Whoops, unexpected scenario<br>";
    return( false );
}

function isSelfIntersecting( $points )
{
    global $debug;

    $n_coords = count($points);
    $edges = array();
    for ( $j = 0, $i = $n_coords-1; $j < $n_coords; $i = $j++ )
    {
        $edges[] = array( $points[$i], $points[$j] );
    }

    for ( $i = 0; $i <= ($n_coords-2); $i++ )
    {
        for ( $j = $i+2; $j < $n_coords+min(0,$i-1); $j++ )
        {
            if ( $debug ) echo "Checking $i and $j<br>";
            if ( linesIntersect( $edges[$i], $edges[$j] ) )
            {
                if ( $debug ) echo "Lines $i and $j intersect<br>";
                return( true );
            }
        }
    }
    return( false );
}

function getPolyCentre( $points, $area=0 )
{
    $cx = 0.0;
    $cy = 0.0;
    if ( !$area )
        $area = getPolyArea( $points );
    for ( $i = 0, $j = count($points)-1; $i < count($points); $j = $i++ )
    {
        $ct = ($points[$i]['x'] * $points[$j]['y']) - ($points[$j]['x'] * $points[$i]['y']);
        $cx += ($points[$i]['x'] + $points[$j]['x']) * ct;
        $cy += ($points[$i]['y'] + $points[$j]['y']) * ct;
    }
    $cx = intval(round(abs($cx/(6.0*$area))));
    $cy = intval(round(abs($cy/(6.0*$area))));
    printf( "X:%cx, Y:$cy<br>" );
    return( array( 'x'=>$cx, 'y'=>$cy ) );
}

function _CompareXY( $a, $b )
{
    if ( $a['min_y'] == $b['min_y'] )
        return( intval($a['min_x'] - $b['min_x']) );
    else
        return( intval($a['min_y'] - $b['min_y']) );
}

function _CompareX( $a, $b )
{
    return( intval($a['min_x'] - $b['min_x']) );
}

function getPolyArea( $points )
{
    global $debug;

    $n_coords = count($points);
    $global_edges = array();
    for ( $j = 0, $i = $n_coords-1; $j < $n_coords; $i = $j++ )
    {
        $x1 = $points[$i]['x'];
        $x2 = $points[$j]['x'];
        $y1 = $points[$i]['y'];
        $y2 = $points[$j]['y'];

        //printf( "x1:%d,y1:%d x2:%d,y2:%d\n", x1, y1, x2, y2 );
        if ( $y1 == $y2 )
            continue;

        $dx = $x2 - $x1;
        $dy = $y2 - $y1;

        $global_edges[] = array(
            "min_y" => $y1<$y2?$y1:$y2,
            "max_y" => ($y1<$y2?$y2:$y1)+1,
            "min_x" => $y1<$y2?$x1:$x2,
            "_1_m" => $dx/$dy,
        );
    }

    usort( $global_edges, "_CompareXY" );

    if ( $debug )
    {
        for ( $i = 0; $i < count($global_edges); $i++ )
        {
            printf( "%d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f<br>", $i, $global_edges[$i]['min_y'], $global_edges[$i]['max_y'], $global_edges[$i]['min_x'], $global_edges[$i]['_1_m'] );
        }
    }

    $area = 0.0;
    $active_edges = array();
    $y = $global_edges[0]['min_y'];
    do 
    {
        for ( $i = 0; $i < count($global_edges); $i++ )
        {
            if ( $global_edges[$i]['min_y'] == $y )
            {
                if ( $debug ) printf( "Moving global edge<br>" );
                $active_edges[] = $global_edges[$i];
                array_splice( $global_edges, $i, 1 );
                $i--;
            }
            else
            {
                break;
            }
        }
        usort( $active_edges, "_CompareX" );
        if ( $debug )
        {
            for ( $i = 0; $i < count($active_edges); $i++ )
            {
                printf( "%d - %d: min_y: %d, max_y:%d, min_x:%.2f, 1/m:%.2f<br>", $y, $i, $active_edges[$i]['min_y'], $active_edges[$i]['max_y'], $active_edges[$i]['min_x'], $active_edges[$i]['_1_m'] );
            }
        }
        $last_x = 0;
        $row_area = 0;
        $parity = false;
        for ( $i = 0; $i < count($active_edges); $i++ )
        {
            $x = intval(round($active_edges[$i]['min_x']));
            if ( $parity )
            {
                $row_area += ($x - $last_x)+1;
                $area += $row_area;
            }
            if ( $active_edges[$i]['max_y'] != $y )
                $parity = !$parity;
            $last_x = $x;
        }
        if ( $debug ) printf( "%d: Area:%d<br>", $y, $row_area );
        $y++;
        for ( $i = 0; $i < count($active_edges); $i++ )
        {
            if ( $y >= $active_edges[$i]['max_y'] ) // Or >= as per sheets
            {
                if ( $debug ) printf( "Deleting active_edge<br>" );
                array_splice( $active_edges, $i, 1 );
                $i--;
            }
            else
            {
                $active_edges[$i]['min_x'] += $active_edges[$i]['_1_m'];
            }
        }
    } while ( count($global_edges) || count($active_edges) );
    if ( $debug ) printf( "Area:%d<br>", $area );
    return( $area );
}

function getPolyAreaOld( $points )
{
    $area = 0.0;
    $edge = 0.0;
    for ( $i = 0, $j = count($points)-1; $i < count($points); $j = $i++ )
    {
        $x_diff = ($points[$i]['x'] - $points[$j]['x']);
        $y_diff = ($points[$i]['y'] - $points[$j]['y']);
        $y_sum = ($points[$i]['y'] + $points[$j]['y']);
        $trap_edge = sqrt(pow(abs($x_diff)+1,2) + pow(abs($y_diff)+1,2) );
        $edge += $trap_edge;
        $trap_area = ($x_diff * $y_sum );
        $area += $trap_area;
        printf( "%d->%d, %d-%d=%.2f, %d+%d=%.2f(%.2f), %.2f, %.2f<br>", i, j, $points[$i]['x'], $points[$j]['x'], $x_diff, $points[$i]['y'], $points[$j]['y'], $y_sum, $y_diff, $trap_area, $trap_edge );
    }
    $edge = intval(round(abs($edge)));
    $area = intval(round((abs($area)+$edge)/2));
    echo "E:$edge<br>";
    echo "A:$area<br>";
    return( $area );
}

function mapCoords( $a )
{
    return( $a['x'].",".$a['y'] );
}

function pointsToCoords( $points )
{
    return( join( " ", array_map( "mapCoords", $points ) ) );
}

function coordsToPoints( $coords )
{
    $points = array();
    if ( preg_match_all( '/(\d+,\d+)+/', $coords, $matches ) )
    {
        for ( $i = 0; $i < count($matches[1]); $i++ )
        {
            if ( preg_match( '/(\d+),(\d+)/', $matches[1][$i], $cmatches ) )
            {
                $points[] = array( 'x'=>$cmatches[1], 'y'=>$cmatches[2] );
            }
            else
            {
                echo( "Bogus coordinates '".$matches[$i]."'" );
                return( false );
            }
        }
    }
    else
    {
        echo( "Bogus coordinate string '$coords'" );
        return( false );
    }
    return( $points );
}

function getLanguages()
{
    $langs = array();
    foreach ( glob("lang/*_*.php") as $file )
    {
        preg_match( '/([^\/]+_.+)\.php/', $file, $matches );
        $langs[$matches[1]] = $matches[1];
    }
    return( $langs );
}

function trimString( $string, $length )
{
    return( preg_replace( '/^(.{'.$length.',}?)\b.*$/', '\\1&hellip;', $string ) );
}

function monitorIdsToNames( $ids )
{
    global $mITN_monitors;
    if ( !$mITN_monitors )
    {
        $sql = "select Id, Name from Monitors";
        foreach( dbFetchAll( $sql ) as $monitor )
        {
            $mITN_monitors[$monitor['Id']] = $monitor;
        }
    }
    $names = array();
    foreach ( preg_split( '/\s*,\s*/', $ids ) as $id )
    {
        if ( visibleMonitor( $id ) )
        {
            if ( isset($mITN_monitors[$id]) )
            {
                $names[] = $mITN_monitors[$id]['Name'];
            }
        }
    }
    $name_string = join( ', ', $names );
    return( $name_string );
}

function initX10Status()
{
    global $x10_status;

    if ( !isset($x10_status) )
    {
        $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
        if ( $socket < 0 )
        {
            Fatal( "socket_create() failed: ".socket_strerror($socket) );
        }
        $sock_file = ZM_PATH_SOCKS.'/zmx10.sock';
        if ( @socket_connect( $socket, $sock_file ) )
        {
            $command = "status";
            if ( !socket_write( $socket, $command ) )
            {
                Fatal( "Can't write to control socket: ".socket_strerror(socket_last_error($socket)) );
            }
            socket_shutdown( $socket, 1 );
            $x10Output = "";
            while ( $x10Response = socket_read( $socket, 256 ) )
            {
                $x10Output .= $x10Response;
            }
            socket_close( $socket );
        }
        else
        {
            // Can't connect so use script
            $command = ZM_PATH_BIN."/zmx10.pl --command status";
            //$command .= " 2>/dev/null >&- <&- >/dev/null";

            $x10Output = exec( escapeshellcmd( $command ) );
        }
        foreach ( explode( "\n", $x10Output ) as $x10Response )
        {
            if ( preg_match( "/^(\d+)\s+(.+)$/", $x10Response, $matches ) )
            {
                $x10_status[$matches[1]] = $matches[2];
            }
        }
    }
}

function getDeviceStatusX10( $key )
{
    global $x10_status;

    initX10Status();

    if ( empty($x10_status[$key]) || !($status = $x10_status[$key]) )
        $status = "unknown";
    return( $status );
}

function setDeviceStatusX10( $key, $status )
{
    $socket = socket_create( AF_UNIX, SOCK_STREAM, 0 );
    if ( $socket < 0 )
    {
        Fatal( "socket_create() failed: ".socket_strerror($socket) );
    }
    $sock_file = ZM_PATH_SOCKS.'/zmx10.sock';
    if ( @socket_connect( $socket, $sock_file ) )
    {
        $command = "$status;$key";
        if ( !socket_write( $socket, $command ) )
        {
            Fatal( "Can't write to control socket: ".socket_strerror(socket_last_error($socket)) );
        }
        socket_shutdown( $socket, 1 );
        $x10Response = socket_read( $socket, 256 );
        socket_close( $socket );
    }
    else
    {
        // Can't connect so use script
        $command = ZM_PATH_BIN.'/zmx10.pl --command '.escapeshellarg( $status );
        $command .= ' --unit-code '.escapeshellarg( $key );
        //$command .= " 2>/dev/null >&- <&- >/dev/null";
        $x10Response = exec( $command );
    }
    if ( preg_match( '/^'.$key.'\s+(.*)/', $x10Response, $matches ) )
        $status = $matches[1];
    else
        $status = "unknown";
    return( $status );
}

function logState()
{
    $state = 'ok';

    $levelCounts = array(
        Logger::FATAL => array( ZM_LOG_ALERT_FAT_COUNT, ZM_LOG_ALARM_FAT_COUNT ),
        Logger::ERROR => array( ZM_LOG_ALERT_ERR_COUNT, ZM_LOG_ALARM_ERR_COUNT ),
        Logger::WARNING => array( ZM_LOG_ALERT_WAR_COUNT, ZM_LOG_ALARM_WAR_COUNT ),
    );

    $sql = "select Level, count(Level) as LevelCount from Logs where Level < ".Logger::INFO." and TimeKey > unix_timestamp(now() - interval ".ZM_LOG_CHECK_PERIOD." second) group by Level order by Level asc";
    $counts = dbFetchAll( $sql );

    foreach ( $counts as $count )
    {
        if ( $count['Level'] <= Logger::PANIC )
            $count['Level'] = Logger::FATAL;
        if ( !($levelCount = $levelCounts[$count['Level']]) )
        {
            Error( "Unexpected Log level ".$count['Level'] );
            next;
        }
        if ( $levelCount[1] && $count['LevelCount'] >= $levelCount[1] )
        {
            $state = 'alarm';
            break;
        }
        elseif ( $levelCount[0] && $count['LevelCount'] >= $levelCount[0] )
        {
            $state = 'alert';
        }
    }
    return( $state );
}

function isVector ( &$array )
{
    $next_key = 0;
    foreach ( array_keys($array) as $key )
    {
        if ( !is_int( $key ) )
            return( false );
        if ( $key != $next_key++ )
            return( false );
    }
    return( true );
}

function checkJsonError($value)
{
    if ( function_exists('json_last_error') )
    {
        $value = var_export($value,true);
        switch( json_last_error() )
        {
            case JSON_ERROR_DEPTH :
                Fatal( "Unable to decode JSON string '$value', maximum stack depth exceeded" );
            case JSON_ERROR_CTRL_CHAR :
                Fatal( "Unable to decode JSON string '$value', unexpected control character found" );
            case JSON_ERROR_STATE_MISMATCH :
                Fatal( "Unable to decode JSON string '$value', invalid or malformed JSON" );
            case JSON_ERROR_SYNTAX :
                Fatal( "Unable to decode JSON string '$value', syntax error" );
            default :
                Fatal( "Unable to decode JSON string '$value', unexpected error ".json_last_error() );
            case JSON_ERROR_NONE:
                break;
        }
    }
}

function jsonEncode( &$value )
{
    if ( function_exists('json_encode') )
    {
        $string = json_encode( $value );
        checkJsonError($value);
        return( $string );
    }

    switch ( gettype($value) )
    {
        case 'double':
        case 'integer':
            return( $value );
        case 'boolean':
            return( $value?'true':'false' );
        case 'string':
            return( '"'.preg_replace( "/\r?\n/", '\\n', addcslashes($value,'"\\/') ).'"' );
        case 'NULL':
            return( 'null' );
        case 'object':
            return( '"Object '.addcslashes(get_class($value),'"\\/').'"' );
        case 'array':
            if ( isVector( $value ) )
                return( '['.join( ',', array_map( 'jsonEncode', $value) ).']' );
            else
            {
                $result = '{';
                foreach ($value as $subkey => $subvalue )
                {
                    if ( $result != '{' )
                        $result .= ',';
                    $result .= '"'.$subkey.'":'.jsonEncode( $subvalue );
                }
                return( $result.'}' );
            }
        default:
            return( '"'.addcslashes(gettype($value),'"\\/').'"' );
    }
}

function jsonDecode( $value )
{
    if ( function_exists('json_decode') )
    {
        $object = json_decode( $value, true );
        checkJsonError($value);
        return( $object );
    }

    $comment = false;
    $unescape = false;
    $out = '$result=';
    for ( $i = 0; $i < strlen($value); $i++ )
    {
        if ( !$comment )
        {
            if ( ($value[$i] == '{') || ($value[$i] == '[') )
                $out .= ' array(';
            else if ( ($value[$i] == '}') || ($value[$i] == ']') )
                $out .= ')';
            else if ( $value[$i] == ':' )
                $out .= '=>';
            else
                $out .= $value[$i];         
        }
        else if ( !$unescape )
        {
            if ( $value[$i] == '\\' )
                $unescape = true;
            else
                $out .= $value[$i];
        }
        else
        {
            if ( $value[$i] != '/' )
                $out .= '\\';
            $out .= $value[$i];
            $unescape = false;
        }
        if ( $value[$i] == '"' )
            $comment = !$comment;
    }
    eval( $out.';' );
    return( $result );
}

define( 'HTTP_STATUS_OK', 200 );
define( 'HTTP_STATUS_BAD_REQUEST', 400 );
define( 'HTTP_STATUS_FORBIDDEN', 403 );

function ajaxError( $message, $code=HTTP_STATUS_OK )
{
    Error( $message );
    if ( function_exists( 'ajaxCleanup' ) )
        ajaxCleanup();
    if ( $code == HTTP_STATUS_OK )
    {
        $response = array( 'result'=>'Error', 'message'=>$message );
        header( "Content-type: text/plain" );
        exit( jsonEncode( $response ) );
    }
    header( "HTTP/1.0 $code $message" );
    exit();
}

function ajaxResponse( $result=false )
{
    if ( function_exists( 'ajaxCleanup' ) )
        ajaxCleanup();
    $response = array( 'result'=>'Ok' );
    if ( is_array( $result ) )
        $response = array_merge( $response, $result );
    elseif ( !empty($result) )
        $response['message'] = $result;
    header( "Content-type: text/plain" );
    exit( jsonEncode( $response ) );
}

function generateConnKey()
{
    return( rand( 1, 999999 ) );
}

function detaintPath( $path )
{
    // Remove any absolute paths, or relative ones that want to go up
    $path = preg_replace( '/\.(?:\.+[\\/][\\/]*)+/', '', $path );
    $path = preg_replace( '/^[\\/]+/', '', $path );
    return( $path );
}

function getSkinFile( $file )
{
    global $skinBase;
    $skinFile = false;
    foreach ( $skinBase as $skin )
    {
        $tempSkinFile = detaintPath( 'skins'.'/'.$skin.'/'.$file );
        if ( file_exists( $tempSkinFile ) )
            $skinFile = $tempSkinFile;
    }
    return( $skinFile );
}

function getSkinIncludes( $file, $includeBase=false, $asOverride=false )
{
    global $skinBase;
    $skinFile = false;
    foreach ( $skinBase as $skin )
    {
        $tempSkinFile = detaintPath( 'skins'.'/'.$skin.'/'.$file );
        if ( file_exists( $tempSkinFile ) )
            $skinFile = $tempSkinFile;
    }
    $includeFiles = array();
    if ( $asOverride )
    {
        if ( $skinFile )
            $includeFiles[] = $skinFile;
        else if ( $includeBase )
            $includeFiles[] = $file;
    }
    else
    {
        if ( $includeBase )
            $includeFiles[] = $file;
        if ( $skinFile )
            $includeFiles[] = $skinFile;
    }
    return( $includeFiles );
}

function requestVar( $name, $default="" )
{
    return( isset($_REQUEST[$name])?validHtmlStr($_REQUEST[$name]):$default );
}

// For numbers etc in javascript or tags etc
function validInt( $input )
{
    return( preg_replace( '/\D/', '', $input ) );
}

function validNum( $input )
{
    return( preg_replace( '/[^\d.-]/', '', $input ) );
}

// For general strings
function validStr( $input )
{
    return( strip_tags( $input ) );
}

// For strings in javascript or tags etc, expected to be in quotes so further quotes escaped rather than converted
function validJsStr( $input )
{
    return( strip_tags( addslashes( $input ) ) );
}

// For general text in pages outside of tags or quotes so quotes converted to entities
function validHtmlStr( $input )
{
    return( htmlspecialchars( $input, ENT_QUOTES ) );
}

?>
