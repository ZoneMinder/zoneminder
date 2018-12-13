<?php
//
// ZoneMinder main web interface file, $Date$, $Revision$
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

error_reporting(E_ALL);

$debug = false;
if ( $debug ) {
  // Use these for debugging, though not both at once!
  phpinfo(INFO_VARIABLES);
  //error_reporting( E_ALL );
}

// Use new style autoglobals where possible
if ( version_compare(phpversion(), '4.1.0', '<') ) {
  $_SESSION = &$HTTP_SESSION_VARS;
  $_SERVER = &$HTTP_SERVER_VARS;
}

// Useful debugging lines for mobile devices
if ( false ) {
  ob_start();
  phpinfo(INFO_VARIABLES);
  $fp = fopen('/tmp/env.html', 'w');
  fwrite($fp, ob_get_contents());
  fclose($fp);
  ob_end_clean();
}

require_once('includes/config.php');
require_once('includes/logger.php');
require_once('includes/Server.php');
require_once('includes/Storage.php');
require_once('includes/Event.php');
require_once('includes/Group.php');
require_once('includes/Monitor.php');


if (
  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
  or
  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
) {
  $protocol = 'https';
} else {
  $protocol = 'http';
}
define('ZM_BASE_PROTOCOL', $protocol);

// Absolute URL's are unnecessary and break compatibility with reverse proxies 
// define( "ZM_BASE_URL", $protocol.'://'.$_SERVER['HTTP_HOST'] );

// Use relative URL's instead
define('ZM_BASE_URL', '');

// Check time zone is set
if (!ini_get('date.timezone') || !date_default_timezone_set(ini_get('date.timezone'))) {
  date_default_timezone_set('UTC');
  Fatal( "ZoneMinder is not installed properly: php's date.timezone is not set to a valid timezone" );
}

if ( isset($_GET['skin']) ) {
  $skin = $_GET['skin'];
} else if ( isset($_COOKIE['zmSkin']) ) {
  $skin = $_COOKIE['zmSkin'];
} else if ( defined('ZM_SKIN_DEFAULT') ) {
  $skin = ZM_SKIN_DEFAULT;
} else {
  $skin = 'classic';
}

$skins = array_map('basename', glob('skins/*', GLOB_ONLYDIR));

if ( ! in_array($skin, $skins) ) {
  Error("Invalid skin '$skin' setting to " . $skins[0]);
  $skin = $skins[0];
}

if ( isset($_GET['css']) ) {
  $css = $_GET['css'];
} elseif ( isset($_COOKIE['zmCSS']) ) {
  $css = $_COOKIE['zmCSS'];
} elseif ( defined('ZM_CSS_DEFAULT') ) {
  $css = ZM_CSS_DEFAULT;
} else {
  $css = 'classic';
}

$css_skins = array_map('basename', glob('skins/'.$skin.'/css/*',GLOB_ONLYDIR));
if ( !in_array($css, $css_skins) ) {
  Error("Invalid skin css '$css' setting to " . $css_skins[0]);
  $css = $css_skins[0];
}

define('ZM_BASE_PATH', dirname($_SERVER['REQUEST_URI']));
define('ZM_SKIN_PATH', "skins/$skin");
define('ZM_SKIN_NAME', $skin);

$skinBase = array(); // To allow for inheritance of skins
if ( !file_exists(ZM_SKIN_PATH) )
  Fatal("Invalid skin '$skin'");
$skinBase[] = $skin;

$currentCookieParams = session_get_cookie_params(); 
//Logger::Debug('Setting cookie parameters to lifetime('.$currentCookieParams['lifetime'].') path('.$currentCookieParams['path'].') domain ('.$currentCookieParams['domain'].') secure('.$currentCookieParams['secure'].') httpOnly(1)');
session_set_cookie_params( 
    $currentCookieParams['lifetime'], 
    $currentCookieParams['path'], 
    $currentCookieParams['domain'],
    $currentCookieParams['secure'], 
    true
); 

ini_set('session.name', 'ZMSESSID');

session_start();

if ( !isset($_SESSION['skin']) || isset($_REQUEST['skin']) || !isset($_COOKIE['zmSkin']) || $_COOKIE['zmSkin'] != $skin ) {
  $_SESSION['skin'] = $skin;
  setcookie('zmSkin', $skin, time()+3600*24*30*12*10);
}

if ( !isset($_SESSION['css']) || isset($_REQUEST['css']) || !isset($_COOKIE['zmCSS']) || $_COOKIE['zmCSS'] != $css ) {
  $_SESSION['css'] = $css;
  setcookie('zmCSS', $css, time()+3600*24*30*12*10);
}

if ( ZM_OPT_USE_AUTH ) {
  if ( isset($_SESSION['user']) ) {
    $user = $_SESSION['user'];
  } else {
    unset($user);
  }
} else {
  $user = $defaultUser;
}
# Only one request can open the session file at a time, so let's close the session here to improve concurrency.
# Any file/page that sets session variables must re-open it.
session_write_close();

require_once('includes/lang.php');
require_once('includes/functions.php');

# Running is global but only do the daemonCheck if it is actually needed
$running = null;

# Add Cross domain access headers
CORSHeaders();

// Check for valid content dirs
if ( !is_writable(ZM_DIR_EVENTS) || !is_writable(ZM_DIR_IMAGES) ) {
  Warning("Cannot write to content dirs('".ZM_DIR_EVENTS."','".ZM_DIR_IMAGES."').  Check that these exist and are owned by the web account user");
}

# Globals
$error_message = null;
$redirect = null;
$view = null;
if ( isset($_REQUEST['view']) )
  $view = detaintPath($_REQUEST['view']);

$request = null;
if ( isset($_REQUEST['request']) )
  $request = detaintPath($_REQUEST['request']);

foreach ( getSkinIncludes('skin.php') as $includeFile )
  require_once $includeFile;

# User Login will be performed in auth.php
require_once('includes/auth.php');

if ( isset($_REQUEST['action']) ) {
  $action = detaintPath($_REQUEST['action']);
}

# The only variable we really need to set is action. The others are informal.
isset($view) || $view = NULL;
isset($request) || $request = NULL;
isset($action) || $action = NULL;

Logger::Debug("View: $view Request: $request Action: $action");
if (
  ZM_ENABLE_CSRF_MAGIC &&
  ( $action != 'login' ) &&
  ( $view != 'view_video' ) &&
  ( $view != 'image' ) &&
  ( $request != 'control' ) && 
  ( $view != 'frames' ) && 
  ( $view != 'archive' )
) {
  require_once( 'includes/csrf/csrf-magic.php' );
  #Logger::Debug("Calling csrf_check with the following values: \$request = \"$request\", \$view = \"$view\", \$action = \"$action\"");
  csrf_check();
}

# Need to include actions because it does auth
require_once('includes/actions.php');

# If I put this here, it protects all views and popups, but it has to go after actions.php because actions.php does the actual logging in.
if ( ZM_OPT_USE_AUTH and !isset($user) ) {
  Logger::Debug('Redirecting to login');
  $view = 'login';
  $request = null;
} else if ( ZM_SHOW_PRIVACY && ($action != 'privacy') && ($view != 'options') && (!$request) && canEdit('System') ) {
  Logger::Debug('Redirecting to privacy');
  $view = 'privacy';
  $request = null;
}

if ( $redirect ) {
  header('Location: '.$redirect);
  return;
}

if ( $request ) {
  foreach ( getSkinIncludes('ajax/'.$request.'.php', true, true) as $includeFile ) {
    if ( !file_exists($includeFile) )
      Fatal("Request '$request' does not exist");
    require_once $includeFile;
  }
  return;
} else {
  if ( $includeFiles = getSkinIncludes('views/'.$view.'.php', true, true) ) {
    foreach ( $includeFiles as $includeFile ) {
      if ( !file_exists($includeFile) )
        Fatal("View '$view' does not exist");
      require_once $includeFile;
    }
    // If the view overrides $view to 'error', and the user is not logged in, then the
    // issue is probably resolvable by logging in, so provide the opportunity to do so.
    // The login view should handle redirecting to the correct location afterward.
    if ( $view == 'error' && !isset($user) ) {
      $view = 'login';
      foreach ( getSkinIncludes('views/login.php', true, true) as $includeFile )
        require_once $includeFile;
    }
  }
  // If the view is missing or the view still returned error with the user logged in,
  // then it is not recoverable.
  if ( !$includeFiles || $view == 'error' ) {
    foreach ( getSkinIncludes('views/error.php', true, true) as $includeFile )
      require_once $includeFile;
  }
}
?>
